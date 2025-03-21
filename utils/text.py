# utils/text.py
"""
Utilidades para el procesamiento de texto.

Este módulo proporciona funciones para normalizar, formatear
y manipular texto para la documentación.
"""

import re
import logging
import unicodedata
from typing import List, Dict, Any, Optional, Union
from pathlib import Path

logger = logging.getLogger(__name__)

def normalize_text(text: str) -> str:
    """
    Normaliza un texto eliminando caracteres especiales y normalizando espacios.
    
    Args:
        text: Texto a normalizar
        
    Returns:
        str: Texto normalizado
    """
    if not text:
        return ""
    
    # Normalizar caracteres Unicode
    text = unicodedata.normalize('NFKC', text)
    
    # Reemplazar múltiples espacios con uno solo
    text = re.sub(r'\s+', ' ', text)
    
    # Eliminar espacios al inicio y final
    return text.strip()

def extract_summary(text: str, max_length: int = 150) -> str:
    """
    Extrae un resumen de un texto más largo.
    
    Args:
        text: Texto completo
        max_length: Longitud máxima del resumen
        
    Returns:
        str: Resumen del texto
    """
    if not text:
        return ""
    
    # Normalizar el texto primero
    text = normalize_text(text)
    
    if len(text) <= max_length:
        return text
    
    # Intentar cortar por una frase completa
    sentences = re.split(r'(?<=[.!?])\s+', text)
    summary = ""
    
    for sentence in sentences:
        if len(summary + sentence) <= max_length:
            summary += sentence + " "
        else:
            break
    
    if not summary:
        # Si no pudimos obtener una frase completa, cortar por palabras
        summary = text[:max_length].rsplit(' ', 1)[0]
    
    return summary.strip() + "..."

def format_code_block(code: str, language: str = "") -> str:
    """
    Formatea un bloque de código para Markdown.
    
    Args:
        code: Código a formatear
        language: Lenguaje de programación
        
    Returns:
        str: Bloque de código formateado
    """
    # Eliminar líneas en blanco al inicio y final
    code = code.strip()
    
    # Formatear como bloque de código Markdown
    return f"```{language}\n{code}\n```"

def clean_whitespace(text: str) -> str:
    """
    Limpia espacios en blanco redundantes en un texto.
    
    Args:
        text: Texto a limpiar
        
    Returns:
        str: Texto limpio
    """
    if not text:
        return ""
    
    # Reemplazar tabs con espacios
    text = text.replace('\t', '    ')
    
    # Normalizar finales de línea
    text = text.replace('\r\n', '\n')
    
    # Eliminar espacios al final de cada línea
    text = re.sub(r' +$', '', text, flags=re.MULTILINE)
    
    # Reducir múltiples líneas en blanco a máximo dos
    text = re.sub(r'\n{3,}', '\n\n', text)
    
    return text.strip()

def slugify(text: str) -> str:
    """
    Convierte un texto en un slug válido para URLs.
    
    Args:
        text: Texto a convertir
        
    Returns:
        str: Slug generado
    """
    if not text:
        return ""
    
    # Normalizar Unicode
    slug = unicodedata.normalize('NFKD', text.lower())
    
    # Quitar acentos
    slug = ''.join(c for c in slug if not unicodedata.combining(c))
    
    # Reemplazar caracteres no alfanuméricos con guiones
    slug = re.sub(r'[^a-z0-9]+', '-', slug)
    
    # Eliminar guiones iniciales y finales
    slug = slug.strip('-')
    
    return slug

def remove_comments(content: str, language: str = 'js') -> str:
    """
    Elimina comentarios del código según el lenguaje especificado.
    
    Args:
        content: Código fuente
        language: Lenguaje ('js' o 'php')
    """
    # Guardar strings para evitar eliminar comentarios dentro de ellos
    strings = {}
    
    def save_string(match):
        placeholder = f"__STRING_{len(strings)}__"
        strings[placeholder] = match.group(0)
        return placeholder
    
    # Preservar strings
    content = re.sub(r'([\'"])((?:\\\1|.)*?)\1', save_string, content)
    
    # Eliminar comentarios según el lenguaje
    if language.lower() == 'js':
        # Comentarios de una línea
        content = re.sub(r'//.*$', '', content, flags=re.MULTILINE)
        # Comentarios multilínea
        content = re.sub(r'/\*[\s\S]*?\*/', '', content)
    else:  # PHP
        # Comentarios de una línea
        content = re.sub(r'(?<!:)//.*$|#.*$', '', content, flags=re.MULTILINE)
        # Comentarios multilínea y PHPDoc
        content = re.sub(r'/\*\*?[\s\S]*?\*/', '', content)
    
    # Restaurar strings
    for placeholder, string in strings.items():
        content = content.replace(placeholder, string)
    
    return content

def list_php_files(directory: Path) -> List[Path]:
    """Lista todos los archivos PHP en un directorio y sus subdirectorios"""
    return list(directory.rglob("*.php"))

def detect_encoding(file_path: Path) -> str:
    """Detecta la codificación de un archivo"""
    encodings = ['utf-8', 'latin-1', 'iso-8859-1', 'cp1252']
    
    for encoding in encodings:
        try:
            with open(file_path, 'r', encoding=encoding) as f:
                f.read()
                return encoding
        except UnicodeDecodeError:
            continue
    
    return 'utf-8'  # Default fallback