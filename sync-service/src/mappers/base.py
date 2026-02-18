"""Abstract base class for network response mappers."""

import hashlib
import json
from abc import ABC, abstractmethod


class Mapper(ABC):
    """Abstract base class for mapping network API responses to canonical schema.

    Each network (FlexOffers, Awin, CJ, Impact) implements this interface to
    transform their specific API response format into our canonical schema.
    """

    @property
    @abstractmethod
    def network_name(self) -> str:
        """Return the network identifier (e.g., 'flexoffers', 'awin')."""
        ...

    @abstractmethod
    def map_advertiser(self, raw: dict) -> dict:
        """Map raw API advertiser response to canonical advertiser dict.

        Args:
            raw: Raw API response for an advertiser/program.

        Returns:
            Dict with keys: network, network_program_id, network_program_name, status
        """
        ...

    @abstractmethod
    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        """Map raw API ad/creative response to canonical ad dict.

        Args:
            raw: Raw API response for an ad/creative.
            advertiser_id: Database ID of the parent advertiser.

        Returns:
            Dict with keys matching the ads table schema.
        """
        ...

    @staticmethod
    def compute_hash(raw: dict) -> str:
        """Compute SHA-256 hash of raw API response for change detection.

        Args:
            raw: Raw API response dictionary.

        Returns:
            Hex-encoded SHA-256 hash string.
        """
        serialized = json.dumps(raw, sort_keys=True, default=str)
        return hashlib.sha256(serialized.encode()).hexdigest()
