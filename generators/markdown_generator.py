import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, Any, List
import os
import json

logger = logging.getLogger(__name__)

class MarkdownGenerator:
    """Generador de documentación en formato Markdown"""
    
    def __init__(self, output_dir: Path = None):
        """Inicializa el generador de Markdown"""
        self.output_dir = output_dir or Path("documentador/docs/generated")
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.logger = logging.getLogger(__name__)
        self.logger.debug(f"Directorio de salida: {self.output_dir}")
        self.template = """<!-- filepath: {path} -->
# **TICKET:** #{ticket}
### **Fecha de creación/modificación:** {fecha}
## **Nombre Aplicativo:** {file_name}

### **Descripcion Aplicativo:**
```
{description}
```

### **Librerias**
{libraries}

### **Servicios**
{services}

### **Lenguaje de programación utilizado:**
{language}

### **URL**
* {file_name} -> [Ruta archivo: {file_name}]({relative_path}\{file_name})

### **URL / .md Documentación Archivos Implementados**
* {doc_md} -> [Ruta archivo: {doc_md}]({reference_path}\{doc_md})

# **PHP:**

### **Listado de Variables Globales**
{globals}

### **Listado de Variables**
```
{locals}
```
### **Tablas Base de datos consultadas / Entidad relacion**
{tables}

### **Tipo de Conector Bases de Datos**
{connections}

## **Conexión a base de datos**
{databases}

### **Funciones**
{functions}

## Rutas
{routes}

### **Realizado por:**
{author}
"""

    def _generate_functions_section(self, functions: List[str]) -> str:
        """Genera la sección de funciones."""
        if not functions:
            return ""
            
        content = ["## Funciones PHP\n"]
        
        for function in sorted(functions):
            content.append(f"* {function}")
            
        return "\n".join(content) + "\n\n"

    def generate(self, data: Dict[str, Any]) -> None:
        """Genera el archivo Markdown con la documentación"""
        try:
            if not data:
                raise ValueError("No hay datos para generar documentación")

            # Preparar datos para la plantilla
            template_data = {
                'ticket': data.get('ticket', '#'),
                'fecha': data.get('fecha', datetime.now().strftime('%Y-%m-%d')),
                'file_name': data.get('file_name', ''),
                'path': data.get('path', ''),
                'variables': data.get('variables', {'globals': [], 'locals': []}),
                'tables': data.get('tables', []),
                'queries': data.get('queries', []),
                'connections': data.get('connections', {}),
                'functions': data.get('functions', []),
                'routes': data.get('routes', []),
                'libraries': data.get('libraries', []),
                'services': data.get('services', []),
                'author': data.get('author', 'José Abel Carvajal')
            }

            # Validar datos mínimos necesarios
            if not template_data['file_name'] or not template_data['path']:
                raise ValueError("Faltan datos básicos del archivo")

            # Generar contenido usando el método interno
            output = self._generate_content(template_data)
            
            # Obtener ruta de salida y crear directorios si no existen
            output_path = self.output_dir / f"{Path(template_data['file_name']).stem}.md"
            output_path.parent.mkdir(parents=True, exist_ok=True)
            
            # Escribir archivo
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(output)
                
            self.logger.info(f"Documentación generada en: {output_path}")

        except Exception as e:
            self.logger.error(f"Error generando documentación: {str(e)}")
            raise

    def _get_relative_path(self, file_path: Path, reference_path: Path) -> str:
        """
        Obtiene la ruta relativa entre dos paths
        """
        try:
            return os.path.relpath(file_path, reference_path)
        except ValueError:
            # Si no se puede obtener la ruta relativa, devolver la ruta absoluta
            return str(file_path)
        
    def _format_libraries(self, libraries: List[str]) -> str:
        """Formatea la sección de librerías"""
        if not libraries:
            return "*Sin librerías*"
        
        #Ordenar y formatear bibliotecas
        formatted = []
        for library in sorted(libraries):
            formatted.append(f"* {library}")
            
        return "\n".join(formatted)
    
    def _format_globals(self, globals_vars: List[str]) -> str:
        """Formatea la sección de variables globales"""
        if not globals_vars:
            return "*Sin variables globales*"
        
        # Ordenar y formatear variables globales
        formatted = []
        for var in sorted(globals_vars):
            formatted.append(f"* {var}")
        
        return "\n".join(formatted)
    
    def _format_locals(self, locals_vars: List[str]) -> str:
        """Formatea la sección de variables locales"""
        if not locals_vars:
            return "*Sin variables locales*"
        
        # Ordenar y formatear variables locales
        formatted = []
        for var in sorted(locals_vars):
            formatted.append(f"* {var}")
            
        return "\n".join(formatted) 
    
    # Ordenar y formatear tablas
    def _format_tables(self, tables: List[str]) -> str:
        if not tables:
            return "*Sin tablas*"
        
        # Ordenar y formatear tablas
        formatted = []
        for table in sorted(tables):
            formatted.append(f"* {table}")
            
        return "\n".join(formatted)

    def _format_connections(self, connections: List[str]) -> str:
        """Formatea la sección de conexiones"""
        if not connections:
            return "*Sin conexiones*"
        
        # Ordenar y formatear conexiones
        formatted = []
        for conn in sorted(connections):
            formatted.append(f"* {conn}")
            
        return "\n".join(formatted)
    
    def _format_databases(self, databases: List[str]) -> str:
        """Formatea la sección de bases de datos"""
        if not databases:
            return "*Sin bases de datos*"
        
        # Ordenar y formatear bases de datos
        formatted = []
        for db in sorted(databases):
            formatted.append(f"* {db}")
            
        return "\n".join(formatted)
    
    def _format_functions(self, functions: List[str]) -> str:
        """Formatea la sección de funciones"""
        if not functions:
            return "*Sin funciones*"
        
        # Ordenar y formatear funciones
        formatted = []
        for func in sorted(functions):
            formatted.append(f"* {func}")
            
        return "\n".join(formatted)
    
    def _format_routes(self, routes: List[str]) -> str:
        """Formatea la sección de rutas"""
        if not routes:
            return "*Sin rutas*"
        
        # Ordenar y formatear rutas
        formatted = []
        for route in routes:
            name = route.get('name', '')
            path = route.get('path', '')
            methods = route.get('methods', ['GET'])
            controller = route.get('controller', '')
            
            methods_str = f" ({', '.join(methods)})"
            formatted.append(f"* {name}:")
            formatted.append(f"  - **Path:** `{path}`")
            formatted.append(f"  - **Methods:** {methods_str}")
            formatted.append(f"  - **Controller:** `{controller}`")
            
        return "\n".join(formatted)

    def _format_services(self, services: List[str]) -> str:
        """Formatea la sección de servicios"""
        if not services:
            return "*Sin servicios*"
            
        # Ordenar y formatear servicios
        formatted = []
        for service in sorted(services):
            # Agregar un marcador para servicios de la aplicación
            if 'App\\Services' in service:
                formatted.append(f"* {service} (Servicio interno)")
            else:
                formatted.append(f"* {service}")
                
        return "\n".join(formatted)

    def _generate_content(self, data: Dict[str, Any]) -> str:
        """Genera el contenido del archivo Markdown"""
        self.logger.debug("Iniciando generación de contenido...")
        self.logger.debug(f"Datos recibidos: {data}")
        
        # Verificar y formatear cada sección
        try:
            formatted_data = {
                'path': data.get('path', ''),
                'ticket': data.get('ticket', ''),
                'fecha': data.get('fecha', datetime.now().strftime('%Y-%m-%d')),
                'file_name': data.get('file_name', ''),
                'description': data.get('description', ''),
                'libraries': self._format_libraries(data.get('libraries', [])),
                'services': self._format_services(data.get('services', [])),
                'language': data.get('language', 'PHP'),
                'doc_md': f"{Path(data.get('file_name', '')).stem}.md",
                'globals': self._format_globals(data.get('variables', {}).get('globals', [])),
                'locals': self._format_locals(data.get('variables', {}).get('locals', [])),
                'tables': self._format_tables(data.get('tables', [])),
                'connections': self._format_connections(data.get('connections', {}).get('conectores', [])),
                'databases': self._format_databases(data.get('connections', {}).get('databases', [])),
                'functions': self._format_functions(data.get('functions', [])),
                'routes': self._format_routes(data.get('routes', [])),
                'relative_path': self._get_relative_path(data.get('path', ''), self.output_dir),
                'reference_path': self._get_relative_path(self.output_dir, data.get('doc_md')),
                'author': data.get('author', 'José Abel Carvajal')
            }
            
            self.logger.debug(f"Datos formateados: {formatted_data}")
            return self.template.format(**formatted_data)
            
        except KeyError as e:
            self.logger.error(f"Falta la clave requerida: {e}")
            raise
        except Exception as e:
            self.logger.error(f"Error generando contenido: {e}")
            raise