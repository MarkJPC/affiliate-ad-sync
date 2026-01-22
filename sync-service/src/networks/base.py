"""Abstract base class for network clients."""

from abc import ABC, abstractmethod


class NetworkClient(ABC):
    """Abstract base class for affiliate network API clients.

    Each network (FlexOffers, Awin, CJ, Impact) implements this interface
    to fetch advertisers and ads from their respective APIs.
    """

    @property
    @abstractmethod
    def network_name(self) -> str:
        """Return the network identifier (e.g., 'flexoffers', 'awin')."""
        ...

    @abstractmethod
    def fetch_advertisers(self) -> list[dict]:
        """Fetch all advertisers/programs from the network.

        Returns:
            List of raw advertiser dicts from the API.
        """
        ...

    @abstractmethod
    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        """Fetch all ads/creatives for an advertiser.

        Args:
            advertiser_id: The network-specific advertiser/program ID.

        Returns:
            List of raw ad dicts from the API.
        """
        ...

    # TODO: Add sync() method after db.py upsert functions are implemented
