import logging
import os
from datetime import datetime
from pathlib import Path
import sys

def setup_logging(level=logging.DEBUG):
    """Configura el sistema de logging con handlers para archivo y consola."""
    # Crear directorio de logs si no existe
    log_dir = Path("logs")
    log_dir.mkdir(exist_ok=True)
    
    # Definir formato y nombre de archivo
    today = datetime.now().strftime('%Y-%m-%d')
    log_file = log_dir / f"documentador_{today}.log"
    
    # Crear el logger raíz
    logger = logging.getLogger()
    logger.setLevel(level)
    
    # Eliminar handlers existentes para evitar duplicación
    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
    
    # Crear handler para archivos
    file_handler = logging.FileHandler(log_file, encoding='utf-8')
    file_handler.setLevel(level)
    file_formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    file_handler.setFormatter(file_formatter)
    
    # Crear handler para la consola
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setLevel(logging.INFO)  # Menos detallado para la consola
    console_formatter = logging.Formatter('%(levelname)s: %(message)s')
    console_handler.setFormatter(console_formatter)
    
    # Agregar handlers al logger
    logger.addHandler(file_handler)
    logger.addHandler(console_handler)
    
    logging.info(f"Logging configurado (nivel: {logging.getLevelName(level)})")
    logging.debug(f"Archivo de log: {log_file}")
    
    return logger
