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
        # TODO: Implement in Commit 2
        raise NotImplementedError

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        """Fetch all links/creatives for an advertiser.

        Args:
            advertiser_id: The CJ advertiser CID.

        Returns:
            List of raw link dicts from the API.
            Includes banners (with dimensions) and text links (0x0).
            May return partial results if some pages fail after retries.
        """
        # TODO: Implement in Commit 3
        raise NotImplementedError

    def close(self) -> None:
        """Close the HTTP client."""
        self._client.close()

    def __enter__(self) -> "CJClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
