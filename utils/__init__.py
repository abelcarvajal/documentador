# utils/__init__.py
"""
Utilidades para el sistema de documentación.

Este módulo contiene funciones y clases de utilidad para el procesamiento
de archivos, manejo de texto y depuración.
"""

from .encoding import detect_encoding, ensure_utf8
from .text import (
    normalize_text, 
    extract_summary, 
    format_code_block,
    clean_whitespace,
    slugify
)
from .debug import (
    setup_logging, 
    log_execution_time, 
    dump_object, 
    debug_mode
)

__all__ = [
    'detect_encoding',
    'ensure_utf8',
    'normalize_text',
    'extract_summary',
    'format_code_block',
    'clean_whitespace',
    'slugify',
    'setup_logging',
    'log_execution_time',
    'dump_object',
    'debug_mode'
]