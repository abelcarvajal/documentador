import sys
from pathlib import Path

# Add the parent directory to the Python path
sys.path.append(str(Path(__file__).resolve().parent.parent))

# -*- coding: windows-1252 -*-
from datetime import datetime
import re
import chardet
import traceback
import logging
import argparse
from typing import List, Dict

# Importaciones absolutas correctas
from documentador.parsers.php_parser import PHPParser
from documentador.config.settings import config
from documentador.generators.markdown_generator import MarkdownGenerator
from documentador.utils.logging_setup import setup_logging

logger = setup_logging()

# Configuracion basica
OUTPUT_DIR = config.get('paths.output')
logging.basicConfig(level=logging.DEBUG, format='%(message)s')

def read_file(file_path: Path):
    """Lee el contenido de un archivo y detecta su codificacion"""
    with open(file_path, 'rb') as f:
        raw_data = f.read()
    result = chardet.detect(raw_data)
    encoding = result['encoding']
    return raw_data.decode(encoding), encoding

def process_php_file(file_path: Path) -> None:
    """Procesa un archivo PHP y genera su documentacion"""
    try:
        logger.info(f"Procesando archivo: {file_path}")
        
        # Instanciar parser y generador
        parser = PHPParser(str(file_path))
        generator = MarkdownGenerator(output_dir=OUTPUT_DIR)  # Pass the output directory
        
        # Obtener resultados del parser
        result = parser.parse()
        logger.debug(f"Resultado del parser: {result}")  # Log the parsed result
        logger.debug(f"Datos a pasar al generador: {result}")  # Log the data being passed to the generator
        
        # Generar documentacion
        generator.generate(result)
        
        logger.info(f"Documentacion generada exitosamente para {file_path}")
        logger.info(f"Generando documentacion en: {OUTPUT_DIR}")  # Log the output directory
        
    except Exception as e:
        logger.error(f"Error procesando archivo: {str(e)}")
        logger.error(f"Detalles del error:\n{traceback.format_exc()}")
        raise

def generate_changes_report(file_path: Path, changes: List[Dict]) -> None:
    """Genera un informe especifico de cambios sin documentacion completa"""
    if not changes:
        logger.info("No se encontraron cambios para documentar")
        return
    
    output_dir = Path(config.get('paths.output', 'documentador/docs/changes'))
    output_dir.mkdir(parents=True, exist_ok=True)
    
    output_file = output_dir / f"{file_path.stem}_changes.md"
    
    with open(output_file, 'w', encoding='windows-1252') as f:
        f.write(f"# Cambios en {file_path.name}\n\n")
        f.write(f"Generado: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n\n")
        
        for change in changes:
            f.write(f"## Commit: {change['hash'][:8]}\n")
            f.write(f"**Autor:** {change['author']}\n")
            f.write(f"**Fecha:** {change['date']}\n")
            f.write(f"**Mensaje:** {change['message']}\n\n")
            
            if 'diff' in change and change['diff']:
                f.write("### Cambios:\n")
                f.write("```diff\n")
                f.write(change['diff'])
                f.write("\n```\n\n")
                
            # Analizar elementos afectados (opcional)
            if 'affected' in change:
                if change['affected'].get('functions'):
                    f.write("#### Funciones modificadas:\n")
                    for func in sorted(change['affected']['functions']):
                        f.write(f"* `{func}`\n")
                    f.write("\n")
                    
                if change['affected'].get('variables'):
                    f.write("#### Variables modificadas:\n") 
                    for var in sorted(change['affected']['variables']):
                        f.write(f"* `{var}`\n")
                    f.write("\n")
    
    logger.info(f"Informe de cambios generado: {output_file}")

def main():
    """Funcion principal del documentador"""
    parser = argparse.ArgumentParser(description='Documentador de archivos PHP')
    parser.add_argument('-i', '--input', required=True, help='Archivo PHP a documentar')
    parser.add_argument('-o', '--output', required=True, help='Ruta de salida para la documentacion')
    parser.add_argument('-c', '--changes-only', action='store_true',
                        help='Documentar solo los cambios Git (sin documentacion completa)')
    parser.add_argument('-g', '--with-git', action='store_true', 
                        help='Incluir informacion Git en la documentacion completa')
    parser.add_argument('-n', '--num-commits', type=int, default=1, 
                        help='Numero de commits a documentar')
    parser.add_argument('-d', '--since-date', 
                        help='Documentar cambios desde una fecha (formato YYYY-MM-DD)')
    parser.add_argument('-v', '--verbose', action='store_true', help='Mostrar informacion detallada')
    
    args = parser.parse_args()
    
    try:
        # Configurar nivel de logging
        log_level = logging.DEBUG if args.verbose else logging.INFO
        logging.basicConfig(level=log_level, format='%(levelname)s: %(message)s')
        
        # Procesar archivo
        file_path = Path(args.input)
        
        # Instanciar parser y generador
        parser = PHPParser(str(file_path))
        generator = MarkdownGenerator(output_dir=OUTPUT_DIR)  # Pass the output directory
        
        if args.changes_only:
            # Modo de solo cambios - generar un resumen de cambios
            logger.info("Generando resumen de cambios Git...")
            
            # Determinar parametros de consulta Git
            git_params = {'num_commits': args.num_commits}
            if args.since_date:
                git_params['since_date'] = args.since_date
            
            changes = parser.get_git_changes(**git_params)
            
            # Generar un archivo Markdown con solo los cambios
            generator.generate_changes_report(changes)
            
        else:
            # Modo documentacion completa
            result = parser.parse()
            
            # Opcionalmente agnadir informacion Git
            if args.with_git:
                logger.info("Incluyendo historial Git en la documentacion...")
                result['changes'] = parser.get_git_changes(args.num_commits)
            
            # Generar documentacion
            generator.generate(result)
        
        logger.info(f"Documentacion generada exitosamente para {file_path}")
        
    except Exception as e:
        logger.error(f"Error: {str(e)}")


if __name__ == "__main__":
    main()

#</create_file>
