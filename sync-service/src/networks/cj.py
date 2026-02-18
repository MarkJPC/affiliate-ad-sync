"""CJ (Commission Junction) API client - Rag's responsibility."""

import logging
import xml.etree.ElementTree as ET

import httpx

from .base import NetworkClient

logger = logging.getLogger(__name__)

MAX_RETRIES = 3


class CJClient(NetworkClient):
    """Client for the CJ Affiliate network API v2.

    CJ uses two separate REST endpoints (advertiser-lookup and link-search)
    and returns XML responses. Requires a CID (Company ID) for advertiser
    lookup and a PID (Website/Property ID) for link search.
    """

    ADVERTISER_URL = "https://advertiser-lookup.api.cj.com/v2/advertiser-lookup"
    LINK_SEARCH_URL = "https://link-search.api.cj.com/v2/link-search"

    def __init__(self, api_token: str, cid: str, website_id: str):
        """Initialize the CJ client.

        Args:
            api_token: CJ personal access token (Bearer token).
            cid: Publisher Company ID (CID) for advertiser lookup.
            website_id: Website/Property ID (PID) for link search.
        """
        self.api_token = api_token
        self.cid = cid
        self.website_id = website_id
        self._client = httpx.Client(timeout=30.0)

    @property
    def network_name(self) -> str:
        return "cj"

    def _get_headers(self) -> dict[str, str]:
        """Return headers with Bearer token authentication."""
        return {"Authorization": f"Bearer {self.api_token}"}

    def _parse_xml_elements(self, xml_text: str, tag: str) -> tuple[list[dict], dict]:
        """Parse CJ XML response into a list of dicts for the given tag.

        CJ returns XML like:
            <cj-api>
              <advertisers total-matched="50" records-returned="25" page-number="1">
                <advertiser>...</advertiser>
                ...
              </advertisers>
            </cj-api>

        This method finds all elements matching `tag` (e.g., "advertiser" or "link")
        and converts each one to a flat dict. Also returns the wrapper element's
        attributes (total-matched, records-returned, page-number) for pagination.

        Args:
            xml_text: Raw XML response string from CJ API.
            tag: The element tag to extract (e.g., "advertiser" or "link").

        Returns:
            Tuple of (list of record dicts, wrapper attributes dict).
        """
        root = ET.fromstring(xml_text)

        # Find the wrapper element (e.g., <advertisers> or <links>)
        # Convention: wrapper tag is plural of record tag (advertiser -> advertisers, link -> links)
        wrapper_tag = f"{tag}s"
        wrapper = root.find(f".//{wrapper_tag}")
        wrapper_attribs = dict(wrapper.attrib) if wrapper is not None else {}

        # Find all record elements
        elements = root.findall(f".//{tag}")
        records = []

        for elem in elements:
            record = self._element_to_dict(elem)
            records.append(record)

        return records, wrapper_attribs

    def _element_to_dict(self, elem: ET.Element) -> dict:
        """Convert an XML element and its children to a flat dict.

        Handles nested elements by joining parent and child tag names.
        For example:
            <primary-category>
                <parent>Home & Garden</parent>
                <child>Utilities</child>
            </primary-category>

        Becomes:
            {"primary-category/parent": "Home & Garden", "primary-category/child": "Utilities"}

        Simple elements like <advertiser-id>1234</advertiser-id> become:
            {"advertiser-id": "1234"}

        Special case: elements with multiple children of the same tag (like <link-types>)
        are collected into a list.

        Args:
            elem: An XML Element to convert.

        Returns:
            Dict mapping tag names to text values.
        """
        record: dict = {}

        for child in elem:
            # Check if this child has sub-elements (nested)
            sub_children = list(child)

            if not sub_children:
                # Simple element: <tag>value</tag>
                record[child.tag] = (child.text or "").strip()
            else:
                # Check if all sub-children have the same tag (list-like)
                sub_tags = [sc.tag for sc in sub_children]
                if len(set(sub_tags)) == 1:
                    # List of same-type elements (e.g., <link-types><link-type>...</link-type>...)
                    record[child.tag] = [(sc.text or "").strip() for sc in sub_children]
                else:
                    # Nested element with different children (e.g., <primary-category>)
                    for sub_child in sub_children:
                        key = f"{child.tag}/{sub_child.tag}"
                        record[key] = (sub_child.text or "").strip()

        return record

    def fetch_advertisers(self) -> list[dict]:
        """Fetch all joined advertisers, handling pagination.

        Returns:
            List of raw advertiser dicts from the API. May return partial
            results if some pages fail after retries.
        """
        advertisers: list[dict] = []
        page = 1
        page_size = 100  # Max allowed by CJ API

        logger.info(f"Fetching CJ advertisers for CID {self.cid}")

        while True:
            params = {
                "requestor-cid": self.cid,
                "advertiser-ids": "joined",
                "page-number": page,
                "records-per-page": page_size,
            }

            # Retry logic for this page
            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.get(
                        self.ADVERTISER_URL,
                        headers=self._get_headers(),
                        params=params,
                    )
                    break  # Success, exit retry loop
                except httpx.RequestError as e:
                    logger.warning(
                        f"Request error on page {page} (attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        continue
                    # All retries exhausted
                    logger.warning(
                        f"Failed to fetch page {page} after {MAX_RETRIES} retries, "
                        f"returning {len(advertisers)} partial results"
                    )
                    return advertisers

            # Handle specific error codes
            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Invalid CJ API token or incorrect CID",
                    request=response.request,
                    response=response,
                )

            try:
                response.raise_for_status()
            except httpx.HTTPStatusError as e:
                logger.warning(
                    f"HTTP error on page {page}: {e}, "
                    f"returning {len(advertisers)} partial results"
                )
                return advertisers

            # Parse XML response
            try:
                page_advertisers, attribs = self._parse_xml_elements(
                    response.text, "advertiser"
                )
            except ET.ParseError as e:
                logger.warning(f"XML parse error on page {page}: {e}")
                return advertisers

            if not page_advertisers:
                logger.debug(f"Page {page}: empty response, stopping pagination")
                break

            # Log each advertiser at DEBUG level
            for adv in page_advertisers:
                logger.debug(
                    f"Advertiser: id={adv.get('advertiser-id')}, "
                    f"name={adv.get('advertiser-name')}, "
                    f"status={adv.get('account-status')}"
                )

            advertisers.extend(page_advertisers)
            logger.debug(f"Page {page}: fetched {len(page_advertisers)} advertisers")

            # Check if we've fetched all results
            total_matched = int(attribs.get("total-matched", 0))
            if len(page_advertisers) < page_size:
                break
            if page * page_size >= total_matched:
                break

            page += 1

        logger.info(f"Fetched {len(advertisers)} total CJ advertisers")
        return advertisers

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        """Fetch all links/creatives for an advertiser.

        Args:
            advertiser_id: The CJ advertiser CID.

        Returns:
            List of raw link dicts from the API.
            Includes banners (with dimensions) and text links (0x0).
            May return partial results if some pages fail after retries.
        """
        ads: list[dict] = []
        page = 1
        page_size = 100  # Default/max for CJ link search

        logger.debug(f"Fetching ads for CJ advertiser {advertiser_id}")

        while True:
            params = {
                "website-id": self.website_id,
                "advertiser-ids": advertiser_id,
                "page-number": page,
                "records-per-page": page_size,
            }

            # Retry logic for this page
            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.get(
                        self.LINK_SEARCH_URL,
                        headers=self._get_headers(),
                        params=params,
                    )
                    break  # Success, exit retry loop
                except httpx.RequestError as e:
                    logger.warning(
                        f"Request error fetching ads for advertiser {advertiser_id} "
                        f"page {page} (attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        continue
                    # All retries exhausted
                    logger.warning(
                        f"Failed to fetch ads for advertiser {advertiser_id} page {page} "
                        f"after {MAX_RETRIES} retries, returning {len(ads)} partial results"
                    )
                    return ads

            # Handle specific error codes
            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Invalid CJ API token",
                    request=response.request,
                    response=response,
                )

            try:
                response.raise_for_status()
            except httpx.HTTPStatusError as e:
                logger.warning(
                    f"HTTP error fetching ads for advertiser {advertiser_id} page {page}: {e}, "
                    f"returning {len(ads)} partial results"
                )
                return ads

            # Parse XML response
            try:
                page_ads, attribs = self._parse_xml_elements(response.text, "link")
            except ET.ParseError as e:
                logger.warning(
                    f"XML parse error for advertiser {advertiser_id} page {page}: {e}"
                )
                return ads

            if not page_ads:
                logger.debug(
                    f"Advertiser {advertiser_id} page {page}: empty response, stopping"
                )
                break

            # Log each link at DEBUG level
            for ad in page_ads:
                link_type = ad.get("link-type", "unknown")
                width = ad.get("creative-width", "0")
                height = ad.get("creative-height", "0")
                logger.debug(
                    f"Link: id={ad.get('link-id')}, name={ad.get('link-name')}, "
                    f"type={link_type}, {width}x{height}"
                )

            ads.extend(page_ads)
            logger.debug(
                f"Advertiser {advertiser_id} page {page}: fetched {len(page_ads)} links"
            )

            # Check if we've fetched all results
            total_matched = int(attribs.get("total-matched", 0))
            if len(page_ads) < page_size:
                break
            if page * page_size >= total_matched:
                break

            page += 1

        logger.debug(
            f"Fetched {len(ads)} total links for CJ advertiser {advertiser_id}"
        )
        return ads

    def close(self) -> None:
        """Close the HTTP client."""
        self._client.close()

    def __enter__(self) -> "CJClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
