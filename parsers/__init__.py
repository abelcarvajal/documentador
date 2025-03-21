"""
Módulo de parsers para análisis de código en diferentes lenguajes
Este archivo exporta todas las funciones de análisis disponibles
"""

# Parsers PHP
from documentador.parsers.php_parser import PHPParser
from documentador.parsers.js_parser import JSParser
from documentador.parsers.sql_parser import SQLParser

__all__ = ['PHPParser', 'JSParser', 'SQLParser']