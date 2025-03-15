"""
Módulo de parsers para análisis de código en diferentes lenguajes
Este archivo exporta todas las funciones de análisis disponibles
"""

# Parsers PHP
from .php_parser import PHPParser

__all__ = ['PHPParser']