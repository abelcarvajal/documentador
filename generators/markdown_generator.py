import logging
from pathlib import Path
from typing import Dict, Any
import os

logger = logging.getLogger(__name__)

class MarkdownGenerator:
    """Generador de documentación en formato Markdown"""
    
    def __init__(self, output_dir: Path = None):
        """
        Inicializa el generador de Markdown.
        
        Args:
            output_dir: Directorio de salida. Si es None, usa el directorio actual.
        """
        self.output_dir = Path(output_dir) if output_dir else Path.cwd()
        self.output_dir.mkdir(parents=True, exist_ok=True)
        logger.debug(f"Directorio de salida: {self.output_dir}")

    def generate(self, data: Dict[str, Any]) -> str:
        """Genera el contenido Markdown"""
        if not data:
            raise ValueError("No hay datos para generar documentación")
            
        return self._generate_content(data)

    def _get_relative_path(self, file_path: Path, reference_path: Path) -> str:
        """
        Obtiene la ruta relativa entre dos paths
        """
        try:
            return os.path.relpath(file_path, reference_path)
        except ValueError:
            # Si no se puede obtener la ruta relativa, devolver la ruta absoluta
            return str(file_path)

    def _generate_content(self, data: Dict[str, Any]) -> str:
        """Genera el contenido del archivo Markdown"""
        template = """# Documentación {filename}

## Lenguajes detectados
{languages}

## Librerías PHP
{libraries}

## Servicios
{services}

## Variables PHP
### Variables Globales
{php_globals}

### Variables Locales
{php_locals}

## Variables JavaScript
{js_section}

### URL
{file_paths}

### URL / .md Documentación Archivos Implementados
{doc_paths}
"""
        # Formatear lenguajes
        languages_md = "\n".join([f"* {lang}" for lang in data.get('languages', [])])
        if not languages_md:
            languages_md = "*No se detectaron lenguajes*"
        
        # Formatear librerías PHP
        libraries_md = "\n".join([f"* {lib}" for lib in data.get('libraries', [])]) 
        if not libraries_md:
            libraries_md = "*No se encontraron librerías PHP*"
            
        # Formatear servicios
        services_md = "\n".join([f"* {service}" for service in data.get('services', [])])
        if not services_md:
            services_md = "*No se encontraron servicios*"
            
        # Sección JavaScript
        if data.get('has_js'):
            js_libraries = data.get('js_libraries', [])
            js_section = "\n\n## Librerías JavaScript\n"
            if js_libraries:
                js_section += "\n".join([f"* {lib}" for lib in js_libraries])
            else:
                js_section += "*No se encontraron librerías JavaScript*"
        else:
            js_section = ""

        # Formatear variables PHP
        php_globals = "\n".join([f"* ${var}" for var in sorted(data.get('php_variables', {}).get('globales', []))]) 
        php_locals = "\n".join([f"* ${var}" for var in sorted(data.get('php_variables', {}).get('locales', []))])

        if not php_globals:
            php_globals = "*No se encontraron variables globales*"
        if not php_locals:
            php_locals = "*No se encontraron variables locales*"

        # Si hay JavaScript, agregar sección de variables JS
        if data.get('has_js'):
            js_section = "\n\n## Variables JavaScript"
            js_globals = data.get('js_variables', {}).get('globales', [])
            js_locals = data.get('js_variables', {}).get('locales', [])
            
            if js_globals:
                js_section += "\n### Variables Globales\n" + "\n".join([f"* {var}" for var in sorted(js_globals)])
            if js_locals:
                js_section += "\n### Variables Locales\n" + "\n".join([f"* {var}" for var in sorted(js_locals)])
        else:
            js_section = ""

        # Formatear rutas de archivos usando la ruta real del archivo
        source_path = Path(data['file_path'])  # Ruta completa del archivo fuente
        doc_output_path = self.output_dir  # Ruta donde se generará la documentación
        
        # Obtener ruta relativa desde la documentación al archivo fuente
        relative_source_path = self._get_relative_path(source_path, doc_output_path)
        file_paths = f"* {data['file_name']} -> [Ruta Archivo: {data['file_name']}]({relative_source_path})"
        
        # Formatear ruta de la documentación
        doc_name = f"{Path(data['file_name']).stem}.md"
        doc_paths = f"* {doc_name} -> [Ruta Archivo: {doc_name}]({doc_name})"
            
        return template.format(
            filename=data['file_name'],
            languages=languages_md,
            libraries=libraries_md,
            services=services_md,
            php_globals=php_globals,
            php_locals=php_locals,
            js_section=js_section,
            file_paths=file_paths,
            doc_paths=doc_paths
        )