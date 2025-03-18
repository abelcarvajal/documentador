import logging
from pathlib import Path
from typing import Dict, Any, List
import os

logger = logging.getLogger(__name__)

class MarkdownGenerator:
    """Generador de documentación en formato Markdown"""
    
    def __init__(self, output_dir: Path = None):
        self.output_dir = output_dir or Path("docs/generated")
        self.output_dir.mkdir(parents=True, exist_ok=True)
        logger.debug(f"Directorio de salida: {self.output_dir}")

    def _generate_functions_section(self, functions: List[str]) -> str:
        """Genera la sección de funciones."""
        if not functions:
            return ""
            
        content = ["## Funciones PHP\n"]
        
        for function in sorted(functions):
            content.append(f"* {function}")
            
        return "\n".join(content) + "\n\n"

    def generate(self, data: Dict[str, Any]) -> None:
        """Genera el archivo de documentación en Markdown."""
        if not data:
            raise ValueError("No hay datos para generar documentación")
        sql_info = data.get('sql_info', {})
        tables = sql_info.get('tables', [])
        logger.info(f"Generando documentación con {len(tables)} tablas SQL: {tables}")

        # Formatear contenido
        content = [
            f"# Documentación {data['file_name']}",
            "",
            "## Lenguajes detectados",
            *[f"* {lang}" for lang in sorted(data.get('languages', []))],
            "",
            "## Librerías PHP",
            *[f"* {lib}" for lib in sorted(data.get('libraries', []))],
            "",
            "## Servicios",
            *[f"* {service}" for service in sorted(data.get('services', []))],
            "",
            "## Variables PHP",
            "### Variables Globales",
            *[f"* {var}" for var in sorted(data.get('variables', {}).get('globales', []))],
            "",
            "### Variables Locales",
            *[f"* {var}" for var in sorted(data.get('variables', {}).get('locales', []))],
            "",
            "### Tablas Base de datos consultadas / Entidad relacion",
            *[f"* {table}" for table in data.get('sql_info', {}).get('tables', [])],
            "",
            "### Tipo de Conector Bases de Datos",
            *[f"* {conector}" for conector in data.get('sql_info', {}).get('conectores', [])],
            "",
            "## Conexión a base de datos",
            *[f"* {db}" for db in data.get('sql_info', {}).get('databases', [])],
            "",
            "## Funciones PHP",
            *[f"* {func}" for func in sorted(data.get('functions', []))],
        ]

        # Guardar archivo
        output_file = self.output_dir / f"{Path(data['file_name']).stem}.md"
        output_file.write_text('\n'.join(content), encoding='utf-8')
        logger.info(f"Archivo generado: {output_file}")

        # Si hay JavaScript, agregar secciones
        if data.get('has_js'):
            js_content = [
                "",
                "## Variables JavaScript",
                *[f"* {var}" for var in sorted(data.get('js_variables', {}).get('locales', []))],
                "",
                "## Funciones JavaScript",
                *[f"* {func}" for func in sorted(data.get('js_functions', []))],
            ]
            content.extend(js_content)

        # Guardar archivo
        output_file = self.output_dir / f"{Path(data['file_name']).stem}.md"
        output_file.write_text('\n'.join(content), encoding='utf-8')
        logger.info(f"Archivo generado: {output_file}")

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