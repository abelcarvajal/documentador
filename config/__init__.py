"""
Paquete de configuración para el documentador PHP.

Este módulo contiene todas las configuraciones necesarias para el 
funcionamiento de la aplicación de documentación automatizada.
"""

from .settings import (
    BASE_DIR,
    OUTPUT_DIR,
    TEMPLATES_DIR,
    LOG_LEVEL,
    LOG_FORMAT,
    LOG_FILE,
    SUPPORTED_ENCODINGS,
    PHP_PATTERNS,
    TEMPLATE_VARS,
    EXCLUDED_DIRS,
    EXCLUDED_FILES
)

__all__ = [
    'BASE_DIR',
    'OUTPUT_DIR',
    'TEMPLATES_DIR',
    'LOG_LEVEL',
    'LOG_FORMAT',
    'LOG_FILE',
    'SUPPORTED_ENCODINGS',
    'PHP_PATTERNS',
    'TEMPLATE_VARS',
    'EXCLUDED_DIRS',
    'EXCLUDED_FILES'
]