# documentador/generators/__init__.py
"""
Paquete de generadores de documentación.

Este módulo contiene generadores para diferentes formatos de salida
como Markdown, HTML, PDF, etc., a partir de la información extraída
del código fuente.
"""

from documentador.generators.markdown_generator import MarkdownGenerator

__all__ = ['MarkdownGenerator']