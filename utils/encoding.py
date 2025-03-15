# utils/encoding.py
"""
Utilidades para manejo de codificación de archivos y texto.

Este módulo proporciona funciones para detectar y convertir 
entre diferentes codificaciones de texto.
"""

import logging
import chardet
from pathlib import Path
from typing import Union, Tuple, Optional, BinaryIO

logger = logging.getLogger(__name__)

def detect_encoding(content: bytes) -> Tuple[str, float]:
    """
    Detecta la codificación de un contenido binario.
    
    Args:
        content: Contenido binario a analizar
        
    Returns:
        Tuple[str, float]: Codificación detectada y nivel de confianza
    """
    if not content:
        return 'utf-8', 1.0
    
    result = chardet.detect(content)
    encoding = result['encoding'] or 'utf-8'
    confidence = result['confidence']
    
    logger.debug(f"Codificación detectada: {encoding} (confianza: {confidence:.2f})")
    return encoding, confidence

def ensure_utf8(content: Union[str, bytes], 
                source_encoding: Optional[str] = None) -> str:
    """
    Asegura que el contenido esté en UTF-8.
    
    Args:
        content: Contenido a convertir a UTF-8
        source_encoding: Codificación de origen si se conoce
        
    Returns:
        str: Contenido en UTF-8
    """
    if isinstance(content, str):
        return content
    
    if not source_encoding:
        source_encoding, _ = detect_encoding(content)
    
    try:
        return content.decode(source_encoding)
    except UnicodeDecodeError:
        logger.warning(f"Error decodificando con {source_encoding}, intentando con utf-8...")
        try:
            return content.decode('utf-8', errors='replace')
        except Exception as e:
            logger.error(f"Error al convertir a UTF-8: {e}")
            return content.decode('latin-1', errors='replace')

def read_file_with_encoding(file_path: Union[str, Path]) -> Tuple[str, str]:
    """
    Lee un archivo detectando automáticamente su codificación.
    
    Args:
        file_path: Ruta al archivo a leer
        
    Returns:
        Tuple[str, str]: Contenido del archivo y codificación detectada
    """
    path = Path(file_path)
    if not path.exists():
        raise FileNotFoundError(f"El archivo {file_path} no existe")
    
    # Leer como binario para detectar codificación
    with open(path, 'rb') as f:
        content = f.read()
    
    encoding, confidence = detect_encoding(content)
    text_content = ensure_utf8(content, encoding)
    
    logger.debug(f"Leído {path.name} ({len(content)} bytes) con codificación {encoding}")
    return text_content, encoding

def write_file_utf8(file_path: Union[str, Path], content: str) -> None:
    """
    Escribe contenido a un archivo en codificación UTF-8.
    
    Args:
        file_path: Ruta donde guardar el archivo
        content: Contenido a escribir
    """
    path = Path(file_path)
    path.parent.mkdir(parents=True, exist_ok=True)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    logger.debug(f"Escrito {path.name} ({len(content)} caracteres) en UTF-8")