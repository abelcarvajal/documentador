from datetime import datetime
import re
import chardet
import traceback
import logging
import argparse
import sys
from pathlib import Path
import logging

# Importaciones absolutas correctas
from documentador.parsers.php_parser import PHPParser
from documentador.config.settings import config
from documentador.generators.markdown_generator import MarkdownGenerator
from documentador.utils.logging_setup import setup_logging

logger = setup_logging()

# Configuración básica
OUTPUT_DIR = config.get('paths.output')
logging.basicConfig(level=logging.DEBUG, format='%(message)s')

def read_file(file_path: Path):
    """Lee el contenido de un archivo y detecta su codificación"""
    with open(file_path, 'rb') as f:
        raw_data = f.read()
    result = chardet.detect(raw_data)
    encoding = result['encoding']
    return raw_data.decode(encoding), encoding

def process_php_file(file_path: Path) -> None:
    """Procesa un archivo PHP y genera su documentación"""
    try:
        logger.info(f"Procesando archivo: {file_path}")
        
        # Instanciar parser y generador
        parser = PHPParser(str(file_path))
        generator = MarkdownGenerator()
        
        # Obtener resultados del parser
        result = parser.parse()
        logger.debug(f"Resultado del parser: {result}")
        
        # Generar documentación
        generator.generate(result)
        
        logger.info(f"Documentación generada exitosamente para {file_path}")
        
    except Exception as e:
        logger.error(f"Error procesando archivo: {str(e)}")
        logger.error(f"Detalles del error:\n{traceback.format_exc()}")
        raise

def main():
    parser = argparse.ArgumentParser(description='Documentador de código PHP')
    parser.add_argument('-i', '--input', required=True, help='Archivo PHP a procesar')
    parser.add_argument('-v', '--verbose', action='store_true', help='Mostrar información detallada')
    
    args = parser.parse_args()
    
    if args.verbose:
        logging.basicConfig(level=logging.DEBUG)
    
    try:
        process_php_file(args.input)
    except Exception as e:
        logging.error(f"Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
