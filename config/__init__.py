"""
Paquete de configuración para el documentador PHP.
"""

from .settings import (
    config,
    BASE_DIR,
    OUTPUT_DIR,
    TEMPLATES_DIR,
    LOG_DIR
)

__all__ = [
    'config',
    'BASE_DIR',
    'OUTPUT_DIR',
    'TEMPLATES_DIR',
    'LOG_DIR'
]