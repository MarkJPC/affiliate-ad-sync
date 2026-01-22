# Mappers to transform network API responses to canonical schema
from .awin import AwinMapper
from .base import Mapper
from .cj import CJMapper
from .flexoffers import FlexOffersMapper
from .impact import ImpactMapper

_MAPPERS: dict[str, Mapper] = {
    "flexoffers": FlexOffersMapper(),
    "awin": AwinMapper(),
    "cj": CJMapper(),
    "impact": ImpactMapper(),
}


def get_mapper(network: str) -> Mapper:
    """Get the mapper for a network."""
    if network not in _MAPPERS:
        raise ValueError(f"Unknown network: {network}")
    return _MAPPERS[network]


__all__ = ["Mapper", "FlexOffersMapper", "AwinMapper", "CJMapper", "ImpactMapper", "get_mapper"]
