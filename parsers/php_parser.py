from datetime import datetime
import re
import logging
from pathlib import Path
from typing import Dict, List, Any, Set

# Cambiar las importaciones relativas por absolutas
from documentador.parsers.language_detector import LanguageDetector
from documentador.parsers.js_parser import JSParser
from documentador.parsers.sql_parser import SQLParser
from documentador.config.settings import config
from documentador.utils.events import event_system

logger = logging.getLogger(__name__)

class PHPParser:
    def __init__(self, file_path: str = None):
        """Inicializa el parser PHP"""
        self.logger = logging.getLogger(__name__)
        self.file_path = file_path
        self.content = ''
        
        if file_path:
            # Lista de codificaciones a probar
            encodings = ['windows-1252', 'latin-1', 'utf-8', 'ISO-8859-1']
            
            for encoding in encodings:
                try:
                    with open(file_path, 'r', encoding=encoding) as f:
                        self.content = f.read()
                    self.logger.info(f"Archivo leído correctamente usando codificación: {encoding}")
                    break
                except UnicodeDecodeError:
                    continue
                except Exception as e:
                    self.logger.error(f"Error leyendo archivo {file_path}: {str(e)}")
                    raise

            if not self.content:
                self.logger.error(f"No se pudo leer el archivo con ninguna codificación")
                raise ValueError("No se pudo leer el archivo correctamente")

        self.services = set()
        self.libraries = set()
        self.encodings = config.get('parsers.encoding', ['utf-8', 'latin-1'])
        self.sql_parser = SQLParser()
        self.logger = logging.getLogger(__name__)

    def _load_file(self) -> bool:
        """Carga y preprocesa el contenido del archivo"""
        try:
            for encoding in self.encodings:
                try:
                    self.raw_content = self.file_path.read_text(encoding=encoding)
                    self.content = self._remove_comments(self.raw_content)
                    return True
                except UnicodeDecodeError:
                    continue
            logger.error(f"No se pudo leer el archivo {self.file_path}")
            return False
        except Exception as e:
            logger.error(f"Error leyendo archivo: {e}")
            return False

    def _remove_comments(self, code: str) -> str:
        """Elimina comentarios del código PHP"""
        # Preservar strings
        strings = {}
        
        def save_string(match):
            placeholder = f"__STRING_{len(strings)}__"
            strings[placeholder] = match.group(0)
            return placeholder

        # Guardar strings
        code = re.sub(r'([\'"])((?:\\\1|.)*?)\1', save_string, code)
        
        # Eliminar comentarios
        code = re.sub(r'(?<!:)//.*$', '', code, flags=re.MULTILINE)  # Comentarios //
        code = re.sub(r'#.*$', '', code, flags=re.MULTILINE)  # Comentarios #
        code = re.sub(r'/\*[\s\S]*?\*/', '', code)  # Comentarios /* */
        
        # Restaurar strings
        for placeholder, string in strings.items():
            code = code.replace(placeholder, string)
        
        return code

    def _extract_libraries(self) -> List[str]:
        """Extrae librerías excluyendo servicios"""
        libraries = set()
        
        # Patrón para detectar use statements
        pattern = r'use\s+([\w\\]+)(?:\s+as\s+[\w\\]+)?;'
        
        matches = re.finditer(pattern, self.content)
        for match in matches:
            lib = match.group(1).strip()
            # Excluir servicios
            if (
                lib and 
                not 'Services' in lib and
                not any(service in lib for service in ['Log', 'Conexion', 'ConsultaParametro', 'HttpClient', 'Herramientas'])
            ):
                libraries.add(lib)
                self.logger.debug(f"Librería encontrada: {lib}")
        
        return sorted(libraries)

    def _extract_sql_queries(self, content: str) -> Set[str]:
        """Extrae consultas SQL del código PHP"""
        queries = set()
        
        # Patrones para encontrar consultas SQL
        sql_patterns = [
            r'\$(?:this->)?cnn->query\s*\(\s*[^,]*,\s*[\'"]([^\'"]+)[\'"]',
            r'\$sql\s*=\s*[\'"]([^\'"]+)[\'"]',
            r'->execute\s*\(\s*[\'"]([^\'"]+)[\'"]'
        ]
        
        for pattern in sql_patterns:
            matches = re.finditer(pattern, content)
            for match in matches:
                query = match.group(1)
                if any(keyword in query.upper() for keyword in ['SELECT', 'INSERT', 'UPDATE', 'DELETE']):
                    queries.add(query)
                    
        return queries

    def parse_tables(self) -> Set[str]:
        """Extrae todas las tablas SQL referenciadas"""
        tables = set()
        
        # Extraer queries
        queries = self._extract_sql_queries(self.content)
        
        # Procesar cada query
        for query in queries:
            tables.update(self.sql_parser.extract_tables(query))
            
        return tables

    def parse(self) -> Dict[str, Any]:
        """Analiza el archivo PHP completo"""
        try:
            self.logger.debug("Iniciando parseo del archivo PHP...")
            
            # Estructura base del resultado
            result = {
                'file_name': Path(self.file_path).name,
                'path': str(Path(self.file_path).absolute()),  # Asegurar que path esté presente
                'ticket': '#',
                'fecha': datetime.now().strftime('%Y-%m-%d'),
                'author': 'José Abel Carvajal',
                'description': '',  # Campo vacío para descripción
                'language': 'PHP',
                'doc_md': Path(self.file_path).with_suffix('.md').name,
                'variables': {
                    'globals': self._extract_global_variables(),
                    'locals': self._extract_local_variables()
                },
                'tables': [],
                'queries': [],
                'connections': self._extract_db_connections(),
                'functions': self._extract_functions(),
                'routes': self._extract_routes(),
                'libraries': self._extract_libraries(),
                'services': self._extract_services(),
                'databases': []
            }
            
            # Analizar SQL usando SQLParser
            sql_info = self.sql_parser.parse_sql_queries(self.content)
            
            # Procesar tablas encontradas 
            all_tables = set()
            for query in sql_info.get('queries', []):
                if query and isinstance(query, dict):
                    tables = query.get('tables', [])
                    if tables:
                        all_tables.update(tables)
                        result['queries'].append(query.get('query', ''))

            result['tables'] = sorted(list(all_tables))
            
            self.logger.debug(f"Resultado final del parseo: {result}")
            return result

        except Exception as e:
            self.logger.error(f"Error en parse: {str(e)}")
            raise

    def _extract_methods(self) -> List[str]:
        """Extrae métodos de clase"""
        methods = []
        patterns = [
            r'(?:public|private|protected)\s+function\s+(\w+)\s*\([^)]*\)',
            r'function\s+(\w+)\s*\([^)]*\)'
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            methods.extend(match.group(1) for match in matches)
        
        return sorted(set(methods))

    def _extract_namespaces(self) -> List[str]:
        """Extrae namespaces y uses"""
        namespaces = []
        patterns = [
            r'namespace\s+([\w\\]+)',
            r'use\s+([\w\\]+)(?:\s+as\s+[\w\\]+)?;'
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            namespaces.extend(match.group(1) for match in matches)
        
        return sorted(set(namespaces))

    def _extract_additional_info(self) -> Dict[str, Any]:
        """Extrae información adicional del archivo"""
        info = {
            'annotations': [],
            'dependencies': [],
            'constants': [],
            'interfaces': []
        }
        
        # Buscar anotaciones
        annotations = re.finditer(r'@(\w+)(?:\([^)]+\))?', self.content)
        info['annotations'].extend(match.group(1) for match in annotations)
        
        # Buscar dependencias
        dependencies = re.finditer(r'(?:require|include)(?:_once)?\s*[\'"]([^\'"]+)[\'"]', self.content)
        info['dependencies'].extend(match.group(1) for match in dependencies)
        
        # Buscar constantes
        constants = re.finditer(r'const\s+(\w+)\s*=', self.content)
        info['constants'].extend(match.group(1) for match in constants)
        
        # Buscar interfaces
        interfaces = re.finditer(r'implements\s+([\w,\s\\]+)', self.content)
        for match in interfaces:
            info['interfaces'].extend(i.strip() for i in match.group(1).split(','))
        
        return {k: sorted(set(v)) for k, v in info.items()}

    def parse_libraries(self, code: str) -> Set[str]:
        """Identifica todas las librerías incluidas en el código PHP."""
        if not code:
            return set()
        
        libraries = set()
        
        # Eliminar comentarios para evitar falsos positivos
        code_sin_comentarios = self._remove_comments(code)
        
        # Patrones para detectar librerías
        include_patterns = [
            r'use\s+([^;]+);',
            r'(?:include|require|include_once|require_once)\s*\(\s*[\'"](.+?)[\'"]\s*\)\s*;',
            r'(?:include|require|include_once|require_once)\s+[\'"](.+?)[\'"]\s*;',
            r'<script\s+[^>]*?src=[\'"](.*?)[\'"].*?>',
            r'<link\s+[^>]*?href=[\'"](.*?)[\'"].*?>'
        ]

        for pattern in include_patterns:
            matches = re.finditer(pattern, code_sin_comentarios)
            for match in matches:
                lib = match.group(1).strip()
                if self._validate_library(lib):
                    self.libraries.add(lib)
                    logging.debug(f"Librería encontrada: {lib}")

        return sorted(self.libraries)

    def _read_file(self):
        """Lee el contenido del archivo."""
        try:
            self.content = self.file_path.read_text(encoding='utf-8')
        except UnicodeDecodeError:
            # Intentar con otras codificaciones
            for encoding in ['ISO-8859-1', 'windows-1252', 'latin-1']:
                try:
                    self.content = self.file_path.read_text(encoding=encoding)
                    break
                except UnicodeDecodeError:
                    continue

    def format_library_name(self, lib: str) -> str:
        """Formatea el nombre de la librería."""
        parts = lib.split('/')
        
        # Mantener namespaces de PHP
        if '\\' in lib:
            return lib
            
        # Para rutas de archivos
        if len(parts) <= 2:
            return lib
            
        if 'lib' in parts:
            lib_index = parts.index('lib')
            return '/'.join(parts[lib_index:])
            
        return '/'.join(parts[-3:])

    def _extract_global_variables(self) -> Set[str]:
        pattern = r'\$(?:GLOBALS|_GET|_POST|_SESSION|_COOKIE|_REQUEST|_SERVER|_FILES|_ENV)\[[\'"](.*?)[\'"]\]'
        matches = re.finditer(pattern, self.content)
        return {match.group(1) for match in matches}

    def _extract_local_variables(self) -> Set[str]:
        pattern = r'\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*='
        matches = re.finditer(pattern, self.content)
        return {match.group(1) for match in matches}

    def _extract_functions(self) -> List[str]:
        """Extrae funciones PHP definidas en el código"""
        functions = []
        patterns = [
            r'function\s+(\w+)\s*\(',
            r'public\s+function\s+(\w+)\s*\(',
            r'private\s+function\s+(\w+)\s*\(',
            r'protected\s+function\s+(\w+)\s*\('
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            functions.extend(match.group(1) for match in matches)
        
        return sorted(functions)

    def _extract_tables(self) -> Set[str]:
        # Patrones para detectar tablas en consultas SQL
        patterns = [
            r'FROM\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?',
            r'JOIN\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?',
            r'UPDATE\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?',
            r'INTO\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?'
        ]
        
        tables = set()
        for pattern in patterns:
            matches = re.finditer(pattern, self.content, re.IGNORECASE)
            tables.update(match.group(1) for match in matches)
        
        return tables

    def _extract_conectores(self) -> Set[str]:
        patterns = [
            r'new\s+PDO\s*\(',
            r'mysqli_connect\s*\(',
            r'mysql_connect\s*\(',
            r'doctrine\s*\.',
            r'entityManager\s*\.',
            r'\$em\s*->'
        ]
        
        conectores = set()
        for pattern in patterns:
            if re.search(pattern, self.content, re.IGNORECASE):
                conectores.add(pattern.split(r'\s')[0])
        
        return conectores

    def parse_file(self, file_path: str) -> Dict[str, Any]:
        """Parsea un archivo PHP y extrae su información."""
        data = {
            'file_name': Path(file_path).name,
            'path': str(Path(file_path).absolute()),  # Aseguramos que se guarde la ruta completa
        }
        try:
            # Convertir el path a Path object si es string
            self.file_path = Path(file_path) if isinstance(file_path, str) else file_path
            
            # Cargar el archivo
            if not self._load_file():
                raise ValueError(f"No se pudo cargar el archivo: {file_path}")

            # Realizar el parsing
            data.update({
                'languages': {'PHP'},  # Por ahora solo PHP
                'libraries': self._extract_libraries(),
                'services': self._extract_services(),
                'variables': {
                    'globales': self._extract_global_variables(),
                    'locales': self._extract_local_variables()
                },
                'functions': self._extract_functions()
            })

            # Extraer información SQL
            sql_info = self.sql_parser.parse_sql_queries(self.content)
            data['sql_info'] = sql_info

            # Agregar información de conexiones a base de datos
            db_connections = self.sql_parser.extract_db_connections(self.content)
            data['sql_info']['conectores'] = db_connections.get('conectores', set())
            data['sql_info']['databases'] = db_connections.get('databases', set())

            self.logger.info(f"Parsing completado para {self.file_path}")
            return data

        except Exception as e:
            self.logger.error(f"Error en parse_file: {str(e)}")
            raise

    def _extract_routes(self) -> List[str]:
        """Extrae rutas definidas en el código"""
        routes = []
        patterns = [
            r'@Route\(["\']([^"\']+)["\']',
            r'->add\(["\']([^"\']+)["\']',
            r'path=["\']([^"\']+)["\']'
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            routes.extend(match.group(1) for match in matches)
        
        return sorted(routes)

    def _extract_routes(self) -> List[Dict[str, str]]:
        """Extrae las rutas definidas en el controlador."""
        routes = []
        
        # Patrones para detectar rutas de Symfony
        patterns = [
            # Formato de anotación
            r'@Route\s*\(\s*["\']([^"\']+)["\']\s*,\s*name\s*=\s*["\']([^"\']+)["\']\s*(?:,\s*methods\s*=\s*{([^}]+)})?\s*\)\s*(?:public\s+)?function\s+(\w+)',
            
            # Formato de atributos (PHP 8+)
            r'#\[Route\s*\(\s*["\']([^"\']+)["\']\s*,\s*name:\s*["\']([^"\']+)["\']\s*(?:,\s*methods:\s*\[([^\]]+)\])?\s*\)\]\s*(?:public\s+)?function\s+(\w+)'
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content, re.DOTALL)
            for match in matches:
                path = match.group(1)
                name = match.group(2)
                methods = match.group(3) if match.group(3) else "GET"
                function = match.group(4)
                
                # Limpiar los métodos HTTP
                methods = [m.strip(' "\'[]{}') for m in methods.split(',')]
                
                routes.append({
                    'name': name,
                    'path': path,
                    'controller': f"{self.__class__.__name__}::{function}",
                    'methods': methods
                })
                
                self.logger.debug(f"Ruta encontrada: {path} -> {function}")
        
        return routes

    def _extract_services(self) -> List[str]:
        """Extrae servicios del código PHP usando múltiples estrategias de detección"""
        services = set()
        
        # 1. Buscar servicios en declaraciones 'use'
        use_pattern = r'use\s+([\w\\]+)(?:\s+as\s+[\w\\]+)?;'
        for match in re.finditer(use_pattern, self.content):
            lib = match.group(1).strip()
            if lib and (
                'Services' in lib or
                any(service in lib for service in ['Log', 'Conexion', 'ConsultaParametro', 'HttpClient', 'Herramientas'])
            ):
                services.add(lib)
                self.logger.debug(f"Servicio encontrado (use statement): {lib}")
        
        # 2. Buscar servicios inyectados con anotación @inject
        inject_pattern = r'@inject\s*\(["\']([^"\']+)["\']'
        for match in re.finditer(inject_pattern, self.content):
            service = match.group(1).strip()
            if service:
                services.add(service)
                self.logger.debug(f"Servicio encontrado (anotación @inject): {service}")
        
        # 3. Buscar servicios inyectados en constructores (patrón común en frameworks modernos)
        constructor_pattern = r'(?:public|protected)\s+function\s+__construct\s*\((.*?)\)'
        for match in re.finditer(constructor_pattern, self.content, re.DOTALL):
            constructor_params = match.group(1)
            
            # Buscar parámetros con tipado que indiquen servicios
            param_pattern = r'([\w\\]+)(?:Interface)?\s+\$(\w+)'
            for param_match in re.finditer(param_pattern, constructor_params):
                type_hint = param_match.group(1)
                
                # Si el tipo tiene formato de servicio, agregarlo
                if (
                    'Service' in type_hint or 
                    'Repository' in type_hint or
                    'Manager' in type_hint or
                    'Helper' in type_hint
                ):
                    services.add(type_hint)
                    self.logger.debug(f"Servicio encontrado (constructor injection): {type_hint}")
        
        # 4. Buscar servicios de contenedor (patrón común en Symfony/Laravel)
        container_pattern = r'(?:->|::)get\s*\(\s*["\'](\w+(?:\.?\w+)*)["\']'
        for match in re.finditer(container_pattern, self.content):
            service_id = match.group(1).strip()
            if service_id and '.' in service_id:  # service_id con formato dominio.servicio
                services.add(f"container:{service_id}")
                self.logger.debug(f"Servicio encontrado (container): {service_id}")
        
        return sorted(services)

    def _extract_global_variables(self) -> List[str]:
        """Extrae variables globales"""
        globals = []
        pattern = r'global\s+(\$\w+)'
        
        matches = re.finditer(pattern, self.content)
        globals.extend(match.group(1) for match in matches)
        
        return sorted(globals)

    def _extract_local_variables(self) -> List[str]:
        """Extrae variables locales importantes"""
        locals = []
        patterns = [
            r'private\s+\$(\w+)',
            r'protected\s+\$(\w+)',
            r'public\s+\$(\w+)'
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            locals.extend(match.group(1) for match in matches)
        
        return sorted(locals)

    def _detect_language_version(self) -> str:
        """Detecta la versión de PHP utilizada"""
        version = "PHP"
        pattern = r'declare\(strict_types=1\)'
        
        if re.search(pattern, self.content):
            version += " 7+"
        
        return version

    def _extract_db_connections(self) -> Dict[str, Set[str]]:
        """Extrae información de conexiones a bases de datos."""
        connections = {
            'conectores': set(),
            'databases': set()
        }

        # Patrones para detectar conexiones
        patterns = [
            # Conexión mediante cnn->query
            r'\$(?:this->)?cnn->query\s*\(\s*[\'\"](\d+)[\'\"](.*?)\)',
            
            # Conexión directa PDO
            r'new\s+PDO\s*\(\s*[\'"](.*?)[\'"]\s*,',
            
            # Conexiones Doctrine
            r'getManager\(\s*[\'"](.*?)[\'"]\s*\)',
            r'getRepository\(\s*[\'"](.*?)[\'"]\s*\)',
            
            # Conexiones mediante parámetros
            r'->setParameter\(\s*[\'"](.*?)[\'"]\s*,',
            
            # Conexiones en configuración
            r'database_host:\s*[\'"](.*?)[\'"]\s*',
            r'database_name:\s*[\'"](.*?)[\'"]\s*'
        ]

        try:
            # Procesar cada patrón
            for pattern in patterns:
                matches = re.finditer(pattern, self.content, re.IGNORECASE | re.MULTILINE)
                for match in matches:
                    connection_id = match.group(1)
                    
                    # Agregar el conector encontrado
                    if connection_id.isdigit():
                        connections['conectores'].add(f"cnn->query('{connection_id}')")
                        
                        # Obtener nombre de base de datos si existe en configuración
                        if hasattr(self, 'sql_parser'):
                            db_name = self.sql_parser.get_db_name(connection_id)
                            if db_name:
                                connections['databases'].add(db_name)
                    else:
                        # Para otros tipos de conexión
                        connections['conectores'].add(connection_id)

        except Exception as e:
            self.logger.error(f"Error extrayendo conexiones: {str(e)}")

        return connections

    def get_db_name(self, connection_id: str) -> str:
        """Obtiene el nombre de la base de datos para un ID de conexión."""
        try:
            # Buscar en el archivo de configuración
            conexiones_path = Path(__file__).parent.parent / 'plantillas' / 'conexiones_symfony.md'
            if not conexiones_path.exists():
                self.logger.warning(f"Archivo de conexiones no encontrado: {conexiones_path}")
                return ""

            with open(conexiones_path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # Buscar la conexión por ID
            pattern = rf'\*\s+{connection_id}:\s+`([^`]+)`\s*->\s*([^\n]+)'
            match = re.search(pattern, content)
            
            if match:
                return match.group(2).strip()
                
        except Exception as e:
            self.logger.error(f"Error obteniendo nombre de BD: {str(e)}")
            
        return ""

logging.basicConfig(level=logging.DEBUG, format='%(message)s')

def detect_encoding(file_path):
    """Detecta la codificación de un archivo."""
    with open(file_path, 'rb') as f:
        raw_data = f.read(10000)  # Leer los primeros 10,000 bytes
        result = chardet.detect(raw_data)
        encoding = result['encoding']
        confidence = result['confidence']
        logging.debug(f"Detected encoding: {encoding} with confidence {confidence}")
        return encoding

def detect_languages(code):
    """Detecta los lenguajes de programación utilizados en el código."""
    languages = set()
    if re.search(r'<\?php', code):
        languages.add('PHP')
    if re.search(r'<script\b[^>]*>', code):
        languages.add('JavaScript')
    # Si no encuentra etiquetas PHP pero el archivo tiene extensión .php, asumir PHP
    if not languages and '.php' in str(code)[:100]:
        languages.add('PHP')
    return languages

def detect_language_blocks(code):
    """Divide el código en bloques por lenguaje de manera más robusta."""
    blocks = []
    # Si el archivo no tiene tags PHP pero es un archivo PHP, tratar todo como PHP
    if '<?php' not in code and code.strip():
        logging.info("No PHP tags found, treating entire file as PHP")
        blocks.append(('php', code))
        return blocks
    # Encontrar todos los bloques PHP
    php_start_positions = []
    php_end_positions = []
    # Buscar todas las etiquetas de apertura PHP
    for match in re.finditer(r'<\?php', code):
        php_start_positions.append(match.start())
    # Buscar todas las etiquetas de cierre PHP
    for match in re.finditer(r'\?>', code):
        php_end_positions.append(match.end())
    # Si hay más etiquetas de apertura que de cierre, añadir una posición de cierre al final
    if len(php_start_positions) > len(php_end_positions):
        php_end_positions.append(len(code))
    # Procesar cada bloque PHP
    for i in range(len(php_start_positions)):
        start_pos = php_start_positions[i] + 5  # Saltar '<?php'
        # Determinar la posición final
        if i < len(php_end_positions):
            end_pos = php_end_positions[i] - 2  # No incluir '?>'
        else:
            end_pos = len(code)  # Hasta el final del archivo
        if end_pos > start_pos:
            php_code = code[start_pos:end_pos]
            logging.info(f"Found PHP block of {len(php_code)} characters")
            blocks.append(('php', php_code))
    # Buscar bloques JavaScript
    js_pattern = re.compile(r'<script\b[^>]*>(.*?)</script>', re.DOTALL)
    for match in js_pattern.finditer(code):
        js_code = match.group(1).strip()
        logging.info(f"Found JS block of {len(js_code)} characters")
        blocks.append(('js', js_code))
    # Si no se encontró ningún bloque, tratar todo como PHP
    if not blocks and code.strip():
        logging.info("No language blocks found, assuming entire content is PHP")
        blocks.append(('php', code))
    return blocks

def remove_comments(code):
    """ 
    Elimina todos los comentarios del código (/* */, //, #)
    Args:
        code (str): Código fuente
    Returns:
        str: Código sin comentarios
    """ 
    return re.sub(r'/\*[\s\S]*?\*/|//.*?$|#.*?$', '', code, flags=re.MULTILINE | re.DOTALL)

def parse_php_libraries(code):
    """Identifica todas las librerías incluidas en el código PHP."""
    libraries = set()
    # Eliminar comentarios para evitar falsos positivos
    code_sin_comentarios = remove_comments(code)
    # Patrones MEJORADOS para todas las formas de include/require
    include_patterns = [
        # Patrón 1: Forma estándar con paréntesis
        r'(?:include|require|include_once|require_once)\s*\(\s*[\'"](.+?)[\'"]\s*\)\s*;',
        # Patrón 2: Forma sin paréntesis
        r'(?:include|require|include_once|require_once)\s+[\'"](.+?)[\'"]\s*;',
        # Patrón 3: Con variable de concatenación (e.g., require_once $path.'file.php')
        r'(?:include|require|include_once|require_once)\s*\(\s*.+?[\'"](.+?)[\'"]\s*\)\s*;',
        # Patrón 4: Con ruta absoluta o $_SERVER
        r'(?:include|require|include_once|require_once)\s*\(\s*[\'"]/.*?/(.+?)[\'"]\s*\)\s*;'
    ]
    # Buscar librerías cargadas mediante echo
    echo_patterns = [
        r'echo\s*[\'"]<script\s+src=[\'"](.*?)[\'"]',
        r'echo\s*[\'"]<link\s+rel=[\'"]stylesheet[\'"]\s+href=[\'"](.*?)[\'"]'
    ]
    # Buscar en includes/requires con logging extensivo
    for i, pattern in enumerate(include_patterns):
        matches = re.findall(pattern, code_sin_comentarios)
        logging.info(f"Patrón {i+1}: Encontrados {len(matches)} matches")
        for match in matches:
            logging.info(f"Librería encontrada: '{match}'")
            if match and len(match) > 3:  # Ignorar entradas muy cortas
                libraries.add(match)  # Mantener la ruta completa
    # Buscar específicamente nusoap.php (como caso especial)
    nusoap_pattern = r'[\'"](.*?nusoap\.php)[\'"]'
    nusoap_matches = re.findall(nusoap_pattern, code_sin_comentarios)
    for match in nusoap_matches:
        logging.info(f"Librería nusoap encontrada: '{match}'")
        libraries.add(match)
    # Buscar en echos
    for pattern in echo_patterns:
        matches = re.findall(pattern, code_sin_comentarios)
        for match in matches:
            if match:
                libraries.add(match)
    # Asegurar que no haya entradas vacías
    libraries = {lib for lib in libraries if lib and len(lib) > 3}
    # Ordenar y retornar
    return sorted(libraries)

def format_library_name(lib_path):
    """Formatea el nombre de la librería para mostrar información más útil."""
    # Si la ruta contiene 'lib' o similar, mantenerlo como contexto
    parts = lib_path.split('/')
    # Para rutas cortas (1-2 segmentos), mostrar la ruta completa
    if len(parts) <= 2:
        return lib_path
    # Para rutas más largas, mostrar información relevante
    if 'lib' in parts:
        # Obtener el índice del segmento 'lib'
        lib_index = parts.index('lib')
        # Mostrar 'lib' y todos los segmentos posteriores
        return '/'.join(parts[lib_index:])
    # Si no hay 'lib' pero hay una estructura significativa, mostrar los últimos 2-3 segmentos
    if len(parts) >= 3:
        return '/'.join(parts[-3:])
    # En caso contrario, mostrar la ruta completa
    return lib_path

def clean_library_name(lib_name):
    """Limpia el nombre de la librería eliminando rutas y extensiones innecesarias."""
    # Eliminar prefijos de rutas
    lib_name = lib_name.split('/')[-1]  # Solo conservar el último segmento
    lib_name = lib_name.split('.')[0]  # Eliminar la extensión
    return lib_name

def clean_variable_name(var_name):
    """Limpia el nombre de la variable eliminando operadores y caracteres no deseados."""
    # Eliminar caracteres especiales al inicio
    var_name = re.sub(r'^\$?\d+', '', var_name)  # Eliminar números al inicio
    var_name = var_name.strip("'\"")
    var_name = re.sub(r'[=!<>]+.*$', '', var_name)
    var_name = re.sub(r'\[.*?\]', '', var_name)
    var_name = re.sub(r'[\+\-\*\/\%\^\&\|\~].*$', '', var_name)
    return var_name.strip()

def should_exclude_variable(var_name):
    """Determina si una variable debe ser excluida."""
    exclude_patterns = [
        r'^\$\d+',
        r'\$key',
        r'\$value',
        r'\$row',
        r'\$link',
        r'\$item',
        r'\[\w+\]$',  # Array access
        r'^\$_',      # Superglobals except $GLOBALS
    ]
    return any(re.search(pattern, var_name) for pattern in exclude_patterns)

def parse_php_variables(code):
    """Variables PHP separando globales (solo las declaradas con 'global') y locales."""
    variables = {'globales': set(), 'locales': set()}
    # Eliminar comentarios del código para mejor análisis
    code_sin_comentarios = remove_comments(code)
    # 1. DETECCIÓN MEJORADA DE VARIABLES GLOBALES
    # Capturar todas las declaraciones "global" en una primera pasada
    global_declaration_pattern = r'global\s+([^;]+);'
    global_declarations = re.findall(global_declaration_pattern, code_sin_comentarios)
    for declaration in global_declarations:
        # Extraer todas las variables en esta declaración
        var_matches = re.finditer(r'\$([a-zA-Z_][a-zA-Z0-9_]*)', declaration)
        for var_match in var_matches:
            var_name = var_match.group(1)
            if var_name and not var_name.startswith('_'):
                variables['globales'].add(var_name)
                logging.debug(f"Variable global detectada en declaración: {var_name}")
    # 2. Buscar variables en $GLOBALS
    globals_matches = re.finditer(r'\$GLOBALS\s*\[\s*[\'"](\w+)[\'"]', code_sin_comentarios)
    for match in globals_matches:
        var_name = match.group(1)
        if var_name and not var_name.startswith('_'):
            variables['globales'].add(var_name)
            logging.debug(f"Variable en $GLOBALS detectada: {var_name}")
    # 3. Variables locales - buscar todas las variables $ que no sean globales
    var_pattern = re.compile(r'\$([a-zA-Z_][a-zA-Z0-9_]*)')
    # Excluir variables en argumentos de funciones
    function_pattern = re.compile(r'function\s+(?:\w+\s*)?\((.*?)\)', re.DOTALL)
    code_sin_comentarios = remove_comments(code)
    # Procesar argumentos de funciones
    function_matches = function_pattern.finditer(code_sin_comentarios)
    for match in function_matches:
        args = match.group(1)
        arg_vars = var_pattern.finditer(args)
        for var_match in arg_vars:
            var_name = var_match.group(1)
            if var_name and not var_name.startswith('_'):
                variables['locales'].add(var_name)
    # Buscar otras variables (todas las $ que no sean globales ni params)
    for match in var_pattern.finditer(code_sin_comentarios):
        var_name = match.group(1)
        if (var_name and 
            not var_name.startswith('_') and 
            not var_name.isupper() and 
            var_name not in variables['globales'] and
            var_name not in variables['locales']):
            variables['locales'].add(var_name)
    # Logging para depuración
    logging.info(f"Variables globales detectadas: {sorted(list(variables['globales']))}")
    return variables

def parse_js_variables(code):
    """Variables JS."""
    variables = set()
    # Eliminar comentarios en JavaScript
    code_sin_comentarios = remove_comments(code)
    patterns = [
        r'\b(?:var|let|const)\s+(\w+)\b',
        r'(?:window|global)\.(\w+)\s*=',
        r'this\.(\w+)\s*='
    ]
    for pattern in patterns:
        matches = re.finditer(pattern, code_sin_comentarios)
        for match in matches:
            variables.add(match.group(1))
    return variables

def debug_global_variables(file_path, var_names_to_find):
    """Busca las variables específicas en el archivo y muestra su contexto."""
    try:
        # Probar diferentes codificaciones
        encodings_to_try = ['utf-8', 'ISO-8859-1', 'windows-1252', 'latin-1']
        content = None
        for encoding in encodings_to_try:
            try:
                with open(file_path, 'r', encoding=encoding) as f:
                    content = f.read()
                break
            except UnicodeDecodeError:
                continue
        if content is None:
            with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
                content = f.read()
        # Crear archivo de log
        log_file = "global_vars_debug.log"
        with open(log_file, 'w', encoding='utf-8') as log:
            log.write(f"Buscando variables: {', '.join(var_names_to_find)} en {file_path}\n\n")
            # 1. Buscar declaraciones con 'global'
            log.write("=== DECLARACIONES CON 'global' ===\n")
            global_pattern = re.compile(r'global\s+(.+?)(?:;|$)', re.MULTILINE | re.DOTALL)
            global_matches = global_pattern.finditer(content)
            for match in global_matches:
                declaration = match.group(0)
                line_num = content[:match.start()].count('\n') + 1
                log.write(f"Línea {line_num}: {declaration.strip()}\n")
                for var_name in var_names_to_find:
                    if f"${var_name}" in declaration:
                        log.write(f"  --> Encontrada variable ${var_name}\n")
            # 2. Buscar variables específicas en todo el archivo
            log.write("\n=== MENCIONES DE VARIABLES ESPECÍFICAS ===\n")
            for var_name in var_names_to_find:
                log.write(f"\nBuscando ${var_name}:\n")
                # Patrón para buscar la variable en diferentes contextos
                patterns = [
                    (f"global.*?\\${var_name}", "Declaración global"),
                    (f"\\$GLOBALS\\s*\\[\\s*['\"]?{var_name}['\"]?\\s*\\]", "Uso con $GLOBALS"),
                    (f"\\${var_name}\\s*=", "Asignación directa"),
                    (f"function.*?\\${var_name}", "En definición de función"),
                    (f"\\${var_name}", "Cualquier uso")
                ]
                for pattern, context in patterns:
                    var_pattern = re.compile(pattern, re.IGNORECASE)
                    var_matches = var_pattern.finditer(content)
                    for match in var_matches:
                        line_start = content[:match.start()].rfind('\n') + 1
                        line_end = content.find('\n', match.start())
                        if line_end == -1:
                            line_end = len(content)
                        line = content[line_start:line_end].strip()
                        line_num = content[:match.start()].count('\n') + 1
                        log.write(f"  Línea {line_num} ({context}): {line}\n")
            # 3. Analizar el patrón actual para detectar variables globales
            log.write("\n=== ANÁLISIS DE PATRONES ACTUALES ===\n")
            var_pattern = re.compile(r'\$([a-zA-Z_][a-zA-Z0-9_]*)(?![^)]*\))')
            global_line_pattern = r'global\s+(.+?)(?:;|$)'
            global_matches = re.finditer(global_line_pattern, content, re.MULTILINE | re.DOTALL)
            for match in global_matches:
                var_declaration = match.group(1)
                log.write(f"Declaración global: {var_declaration.strip()}\n")
                var_matches = var_pattern.finditer(var_declaration)
                for var_match in var_matches:
                    var_name = var_match.group(1)
                    log.write(f"  Variable detectada: ${var_name}\n")
        print(f"Análisis completo. Revisa el archivo: {log_file}")
        return log_file
    except Exception as e:
        print(f"Error en depuración: {e}")
        return None

def parse_php_functions(code):
    """Detecta todas las funciones y métodos PHP con patrones avanzados."""
    functions = set()
    # Primero eliminamos los comentarios
    code = remove_comments(code)
    # Patrones mejorados para detectar funciones
    patterns = [
        # Funciones normales
        r'function\s+(\w+)\s*\(',
        # Métodos de clase con diferentes combinaciones de modificadores
        r'(?:public|private|protected|static|abstract|final)(?:\s+(?:public|private|protected|static|abstract|final))*\s+function\s+(\w+)\s*\([^)]*\)',
        # Funciones con referencia
        r'function\s+&\s*(\w+)\s*\([^)]*\)',
        # Métodos mágicos (como __construct)
        r'function\s+(__\w+)\s*\([^)]*\)',
        # Funciones anónimas y closures (estas no tienen nombre, pero buscamos contexto)
        r'(\w+)\s*=\s*function\s*\([^)]*\)',
    ]
    # Buscar todas las funciones con los patrones
    for pattern in patterns:
        matches = re.finditer(pattern, code, re.MULTILINE | re.DOTALL)
        for match in matches:
            try:
                function_name = match.group(1)
                if function_name and not function_name.startswith('_'):
                    functions.add(function_name)
                    logging.debug(f"Función encontrada: {function_name}")
            except IndexError:
                continue
    return functions

def parse_js_functions(code):
    """Funciones JS (function y arrow)."""
    functions = set()
    # Eliminar comentarios en JavaScript
    code_sin_comentarios = remove_comments(code)
    patterns = [
        r'function\s+(\w+)\s*\(',  # Funciones normales
        r'const\s+(\w+)\s*=\s*\([^)]*\)\s*=>', # Arrow functions
        r'let\s+(\w+)\s*=\s*\([^)]*\)\s*=>', # Arrow functions con let
        r'var\s+(\w+)\s*=\s*\([^)]*\)\s*=>', # Arrow functions con var
        r'(\w+)\s*:\s*function\s*\(' # Métodos de objeto
    ]
    for pattern in patterns:
        matches = re.finditer(pattern, code_sin_comentarios)
        for match in matches:
            functions.add(match.group(1))
    return functions

def filter_used_functions(code, declared_functions):
    """Filtra las funciones que son realmente utilizadas en el código."""
    used_functions = set()
    for function_name in declared_functions:
        # Patrones para buscar llamadas a funciones
        patterns = [
            rf'\b{re.escape(function_name)}\s*\(',  # Llamada directa
            rf'call_user_func\(\s*[\'"]?{re.escape(function_name)}[\'"]?',  # PHP call_user_func
            rf'[\'"]?{re.escape(function_name)}[\'"]?\s*=>',  # Como callback en array
            rf'\.{re.escape(function_name)}\(',  # JavaScript method call
        ]
        # Si se encuentra algún patrón, considerar la función como utilizada
        for pattern in patterns:
            if re.search(pattern, code, re.IGNORECASE):
                used_functions.add(function_name)
                break
    return used_functions

def leer_conexiones_md(archivo_conexiones):
    """Lee el archivo conexiones.md y devuelve un diccionario con las conexiones y sus descripciones."""
    conexiones_md = {}
    with open(archivo_conexiones, 'r', encoding='utf-8') as f:
        for line in f:
            match = re.match(r'\*\s+\`(\$[a-zA-Z0-9_]+)\`: (.+)', line)
            if match:
                conexiones_md[match.group(1)] = match.group(2)
    return conexiones_md

def parse_conector_db(code):
    """Detecta y lista los tipos de conectores de base de datos utilizados."""
    conectores = set()
    
    try:
        # Construir ruta absoluta al archivo de conexiones
        conexiones_path = Path(__file__).parent.parent / 'plantillas' / 'conexiones_symfony.md'
        logging.debug(f"Buscando archivo de conexiones en: {conexiones_path}")
        
        with open(conexiones_path, 'r', encoding='utf-8') as f:
            conexiones_content = f.read()
            
        # Convertir el contenido en un diccionario de conexiones
        conexiones = {}
        for match in re.finditer(r'\*\s+(\d+):\s+`([^`]+)`\s*->\s*(.+)', conexiones_content):
            id_conexion, nombre_conexion, descripcion = match.groups()
            conexiones[id_conexion] = {
                'nombre': nombre_conexion.strip(),
                'descripcion': descripcion.strip()
            }
            logging.debug(f"Conexión cargada: ID={id_conexion}, Nombre={nombre_conexion}")
        
        # Eliminar comentarios del código antes de procesar
        code_sin_comentarios = remove_comments(code)
        
        # Buscar uso de conexiones por ID
        query_pattern = r'\$(?:this->)?cnn->query\s*\(\s*[\'"](\d+)[\'"]'
        for match in re.finditer(query_pattern, code_sin_comentarios):
            id_conexion = match.group(1)
            if id_conexion in conexiones:
                conectores.add(conexiones[id_conexion]['nombre'])
                logging.debug(f"Conexión detectada: {conexiones[id_conexion]['nombre']}")
    
    except FileNotFoundError:
        logging.error(f"No se encuentra el archivo de conexiones en: {conexiones_path}")
        raise
    except Exception as e:
        logging.error(f"Error procesando conexiones: {e}")
        raise
    
    return sorted(list(conectores))

def parse_base_datos(code, conectores):
    """Base de datos (MySQL, PostgreSQL, SQLite, Oracle)."""
    db_types = {}
    try:
        with open('conexiones.md', 'r', encoding='utf-8') as f:
            conexiones = f.readlines()
        for conector in conectores:
            for linea in conexiones:
                if conector in linea:
                    db_type = linea.split(':')[1].strip()
                    db_types[conector] = db_type
                    break
    except FileNotFoundError:
        logging.error("El archivo conexiones.md no se encuentra.")
    return db_types

def parse_php_tables(code):
    """Consulta las tablas usadas en el código PHP, maneja múltiples tablas en FROM."""
    tables = set()
    # Eliminar comentarios del código
    code_sin_comentarios = remove_comments(code)
    # Buscar cadenas SQL en el código
    sql_patterns = [
        r'=\s*[\'"](?:\s*SELECT|INSERT|UPDATE|DELETE).+?[\'"]',  # Variables SQL
        r'\([\'"](?:\s*SELECT|INSERT|UPDATE|DELETE).+?[\'"]\)'    # Funciones con SQL
    ]
    for pattern in sql_patterns:
        sql_matches = re.finditer(pattern, code_sin_comentarios, re.DOTALL | re.IGNORECASE)
        for sql_match in sql_matches:
            sql = sql_match.group(0)
            # 1. Detectar tablas en cláusulas FROM con múltiples tablas separadas por comas
            from_pattern = r'\bFROM\s+(.+?)(?:\s+WHERE|\s+ORDER|\s+GROUP|\s+HAVING|\s+$)'
            from_matches = re.finditer(from_pattern, sql, re.IGNORECASE | re.DOTALL)
            for from_match in from_matches:
                tables_clause = from_match.group(1).strip()
                # Dividir por coma para capturar múltiples tablas en FROM
                for table_expr in tables_clause.split(','):
                    table_expr = table_expr.strip()
                    if table_expr:
                        # Extraer nombre de tabla de "tabla alias" o "tabla AS alias"
                        parts = re.split(r'\s+(?:as\s+)?', table_expr, 1, re.IGNORECASE)
                        if parts:
                            tables.add(parts[0].strip())
    # 2. Detectar tablas en otras cláusulas comunes
    table_patterns = [
        r'\bJOIN\s+([a-zA-Z_][a-zA-Z0-9_]*)',  # JOIN
        r'\bUPDATE\s+([a-zA-Z_][a-zA-Z0-9_]*)', # UPDATE
        r'\bINSERT\s+INTO\s+([a-zA-Z_][a-zA-Z0-9_]*)', # INSERT
        r'\bCREATE\s+TABLE\s+([a-zA-Z_][a-zA-Z0-9_]*)' # CREATE TABLE
    ]
    for pattern in table_patterns:
        for match in re.finditer(pattern, code_sin_comentarios, re.IGNORECASE):
            table = match.group(1)
            if table:
                tables.add(table)
    logging.debug(f"Detected tables: {tables}")
    return sorted(tables)

def generate_md(file_path: Path, php_data: dict, js_data: dict, languages: set):
    output_dir = Path("docs/")
    output_dir.mkdir(exist_ok=True)  # Ensure output directory exists
    file_md = file_path.with_suffix('.md').name
    output_file = output_dir / file_md
    php_globales = sorted(php_data['variables']['globales'])
    php_locales = sorted(php_data['variables']['locales'])
    php_funcs = sorted(php_data['functions'])
    php_libs = sorted(php_data.get('libraries', []))
    php_tables = sorted(php_data.get('tables', []))
    js_vars = sorted(js_data['variables'])
    js_funcs = sorted(js_data['functions'])
    conectores = sorted(php_data.get('conectores', []))
    db_types = parse_base_datos(file_path.read_text(encoding='ISO-8859-1'), conectores)
    php_globales_str = '\n* '.join(php_globales) if php_globales else '*Sin variables globales*'
    php_locales_str = '\n- '.join(php_locales) if php_locales else '*Sin variables locales*'
    php_funcs_str = '\nfunction '.join(php_funcs) if php_funcs else '*Sin funciones*'
    php_libs_str = '\n* '.join(php_libs) if php_libs else '*Sin librerías*'
    php_tables_str = '\n* '.join(php_tables) if php_tables else '*Sin tablas*'
    js_vars_str = '\n* '.join(js_vars) if js_vars else '*Sin variables*'
    js_funcs_str = '\n* function '.join(js_funcs) if js_funcs else '*Sin funciones*'
    languages_str = '\n* '.join(sorted(languages)) if languages else '*Sin lenguajes detectados*'
    conectores_str = '\n* '.join(conectores) if conectores else '*No se detectó conector de base de datos*'
    base_datos_str = '\n* '.join([f"{conector}: {db_types.get(conector, '*No se encontró tipo de base de datos*')}" for conector in conectores])
    md_content = f"""## **Nombre Aplicativo:** {file_path.name}### **Fecha de creación/modificación: ** {datetime.now().strftime('%Y-%m-%d')}### Lenguaje de programación utilizado:* {languages_str}### Librerías* {php_libs_str}### **Descripción del apliicativo:**``````### **URL*** {file_path.name} -> [Ruta archivo: {file_path.name}](../{file_path.name})### **URL / .md Documentación Archivos Implementados *** {file_md} -> [Ruta archivo: {file_md}](../guia_programador/{file_md})### Funciones:# **PHP: **```function {php_funcs_str}```# ** JavaScript: *** function {js_funcs_str}### **Listado de Variables implicadas **# **PHP:**```- {php_locales_str}```# **JavaScript:*** {js_vars_str}### **Listado de Variables Globales**# **PHP:*** {php_globales_str}### **Conexion a base de datos*** {base_datos_str}### **Tablas Base de datos consultadas / Entidad relacion*** {php_tables_str}### **Tipo de Conector Bases de Datos:*** {conectores_str}### **Elaborado  Por:**José Abel Carvajal### **NOTA:**"""
    output_file.write_text(md_content, encoding='utf-8')

def process_php_file(file_path):
    """Procesa un archivo PHP con mejor detección de componentes."""
    logging.info(f"Processing file: {file_path}")
    # Intentar leer todo el archivo en modo binario primero
    try:
        with open(file_path, 'rb') as f:
            raw_data = f.read()  # Lee el archivo completo
        logging.info(f"Read {len(raw_data)} bytes from file")
    except Exception as e:
        logging.error(f"Error reading file {file_path}: {e}")
        return
    # Probar diferentes codificaciones (poner Latin-1 primero para archivos antiguos)
    encodings_to_try = ['ISO-8859-1', 'windows-1252', 'latin-1', 'utf-8']
    code = None
    for encoding in encodings_to_try:
        try:
            code = raw_data.decode(encoding)
            logging.info(f"Successfully decoded with {encoding}")
            break
        except UnicodeDecodeError:
            continue
    # Si no se pudo decodificar, usar reemplazo
    if code is None:
        code = raw_data.decode('latin-1', errors='replace')
        logging.info("Using 'replace' for decoding with latin-1")
    # Detectar bloques de lenguaje
    blocks = detect_language_blocks(code)
    languages = detect_languages(code)
    # Inicializar contenedores
    php_vars = {'globales': set(), 'locales': set()}
    php_funcs = set()
    js_vars = set()
    js_funcs = set()
    libraries = set()
    tables = set()
    conectores = set()
    all_php_functions = set()
    all_js_functions = set()
    # Procesar cada bloque por lenguaje
    for lang, block in blocks:
        logging.info(f"Processing {lang} block with {len(block)} characters")
        if lang == 'php':
            parsed_vars = parse_php_variables(block)
            php_vars['globales'].update(parsed_vars['globales'])
            php_vars['locales'].update(parsed_vars['locales'])
            php_funcs.update(parse_php_functions(block))
            all_php_functions.update(parse_php_functions(block))
            libraries.update(parse_php_libraries(block))
            tables.update(parse_php_tables(block))
            try:
                conectores.update(parse_conector_db(block))
            except FileNotFoundError as e:
                logging.error(f"Error procesando conectores: {e}")
                sys.exit(1)
        elif lang == 'js':
            js_vars.update(parse_js_variables(block))
            js_funcs.update(parse_js_functions(block))
            all_js_functions.update(parse_js_functions(block))
    used_php_functions = filter_used_functions(code, all_php_functions)
    used_js_functions = filter_used_functions(code, all_js_functions)
    php_funcs = used_php_functions
    js_funcs = used_js_functions
    # Verificación adicional - buscar en todo el código para más robustez
    # Este paso busca todas las declaraciones 'global' en el código completo
    # para asegurar que no se pierda ninguna, incluso si el análisis por bloques falló
    global_declaration_pattern = r'global\s+([^;]+);'
    global_declarations = re.findall(global_declaration_pattern, code)
    # Verificación adicional buscando en todo el código para librerías importantes
    all_libraries = parse_php_libraries(code)
    libraries.update(all_libraries)
    # Añadir logging para verificar librerías encontradas
    logging.info(f"Librerías finales detectadas: {sorted(list(libraries))}")
    for declaration in global_declarations:
        # Extraer todas las variables en esta declaración
        var_matches = re.finditer(r'\$([a-zA-Z_][a-zA-Z0-9_]*)', declaration)
        for var_match in var_matches:
            var_name = var_match.group(1)
            if var_name and not var_name.startswith('_'):
                if var_name not in php_vars['globales']:
                    php_vars['globales'].add(var_name)
                    logging.info(f"Variable global adicional encontrada en verificación secundaria: {var_name}")
    # Depuración final de variables globales detectadas
    logging.info(f"Variables globales finales: {sorted(list(php_vars['globales']))}")
    # Generar documentación
    generate_md(
        Path(file_path),
        {'variables': php_vars, 'functions': php_funcs, 'libraries': libraries, 'tables': tables, 'conectores': conectores},
        {'variables': js_vars, 'functions': js_funcs},
        languages
    )

# Logica de actualización de documentación haciendo uso de git
def get_git_changes(file_path, num_commits=1):
    """Obtiene los cambios recientes de un archivo usando Git."""
    try:
        # Obtener los cambios del último commit que afectó al archivo
        cmd = ["git", "log", "-p", f"-{num_commits}", "--", str(file_path)]
        result = subprocess.run(cmd, capture_output=True, text=True, check=True)
        return result.stdout
    except subprocess.CalledProcessError as e:
        print(f"Error obteniendo cambios de Git: {e}")
        return ""

def parse_git_changes(git_diff):
    """Analiza el diff de Git para extraer líneas agregadas y eliminadas."""
    added_lines = []
    removed_lines = []
    # Patrones para identificar líneas agregadas/eliminadas en el diff
    for line in git_diff.split('\n'):
        if line.startswith('+') and not line.startswith('+++'):
            added_lines.append(line[1:])  # Elimina el '+' inicial
        elif line.startswith('-') and not line.startswith('---'):
            removed_lines.append(line[1:])  # Elimina el '-' inicial
    return added_lines, removed_lines

def analyze_changed_elements(added_lines, removed_lines):
    """Analiza qué elementos (funciones, variables, tablas) han cambiado."""
    changes = {
        'functions': {'added': set(), 'modified': set(), 'removed': set()},
        'variables': {'added': set(), 'modified': set(), 'removed': set()},
        'tables': {'added': set(), 'modified': set(), 'removed': set()},
    }
    # Unir todas las líneas para analizarlas
    added_code = '\n'.join(added_lines)
    removed_code = '\n'.join(removed_lines)
    # Detectar funciones cambiadas
    added_functions = parse_php_functions(added_code)
    removed_functions = parse_php_functions(removed_code)
    changes['functions']['added'] = added_functions - removed_functions
    changes['functions']['removed'] = removed_functions - added_functions
    changes['functions']['modified'] = added_functions.intersection(removed_functions)
    # Detectar variables cambiadas
    added_vars = parse_php_variables(added_code)
    removed_vars = parse_php_variables(removed_code)
    # Para variables globales
    changes['variables']['added'] = added_vars['globales'] - removed_vars['globales']
    changes['variables']['removed'] = removed_vars['globales'] - added_vars['globales']
    changes['variables']['modified'] = added_vars['globales'].intersection(removed_vars['globales'])
    # Detectar tablas cambiadas
    added_tables = set(parse_php_tables(added_code))
    removed_tables = set(parse_php_tables(removed_code))
    changes['tables']['added'] = added_tables - removed_tables
    changes['tables']['removed'] = removed_tables - added_tables
    changes['tables']['modified'] = added_tables.intersection(removed_tables)
    return changes

def extract_commit_info(git_output):
    """Extrae información del commit (autor, fecha, mensaje) del output de git."""
    commit_info = ""
    lines = git_output.split('\n')
    for i, line in enumerate(lines):
        if line.startswith('commit '):
            commit_hash = line.split(' ')[1]
            commit_info += f"Commit: {commit_hash}\n"
        elif line.startswith('Author: '):
            author = line[8:]
            commit_info += f"Autor: {author}\n"
        elif line.startswith('Date: '):
            date = line[6:]
            commit_info += f"Fecha: {date}\n"
        elif i > 3 and line and not line.startswith('+') and not line.startswith('-') and not line.startswith(' '):
            # Probablemente sea el mensaje del commit
            if not line.strip().startswith('diff --git'):
                commit_info += f"Mensaje: {line.strip()}\n"
                break
    return commit_info

def update_md_with_changes(md_file_path, changes, commit_info=""):
    """Actualiza el archivo MD existente con los cambios recientes."""
    try:
        with open(md_file_path, 'r', encoding='utf-8') as f:
            md_content = f.read()
        # Obtener la fecha actual
        current_date = datetime.now().strftime('%Y-%m-%d')
        # Crear sección de cambios recientes
        changes_section = f"\n\n## Cambios Recientes ({current_date})\n\n"
        if commit_info:
            changes_section += f"### Commit Info\n{commit_info}\n\n"
        # Agregar funciones cambiadas
        if any(changes['functions'].values()):
            changes_section += "### Funciones\n"
            if changes['functions']['added']:
                changes_section += "#### Agregadas\n"
                for func in changes['functions']['added']:
                    changes_section += f"* `{func}()`\n"
            if changes['functions']['modified']:
                changes_section += "#### Modificadas\n"
                for func in changes['functions']['modified']:
                    changes_section += f"* `{func}()`\n"
            if changes['functions']['removed']:
                changes_section += "#### Eliminadas\n"
                for func in changes['functions']['removed']:
                    changes_section += f"* `{func}()`\n"
        # Agregar variables cambiadas
        if any(changes['variables'].values()):
            changes_section += "\n### Variables Globales\n"
            if changes['variables']['added']:
                changes_section += "#### Agregadas\n"
                for var in changes['variables']['added']:
                    changes_section += f"* `${var}`\n"
            if changes['variables']['modified']:
                changes_section += "#### Modificadas\n"
                for var in changes['variables']['modified']:
                    changes_section += f"* `${var}`\n"
            if changes['variables']['removed']:
                changes_section += "#### Eliminadas\n"
                for var in changes['variables']['removed']:
                    changes_section += f"* `${var}`\n"
        # Agregar tablas cambiadas
        if any(changes['tables'].values()):
            changes_section += "\n### Tablas\n"
            if changes['tables']['added']:
                changes_section += "#### Agregadas\n"
                for table in changes['tables']['added']:
                    changes_section += f"* `{table}`\n"
            if changes['tables']['modified']:
                changes_section += "#### Modificadas\n"
                for table in changes['tables']['modified']:
                    changes_section += f"* `{table}`\n"
            if changes['tables']['removed']:
                changes_section += "#### Eliminadas\n"
                for table in changes['tables']['removed']:
                    changes_section += f"* `{table}`\n"
        # Verificar si ya existe una sección de cambios recientes
        if "## Cambios Recientes" in md_content:
            # Reemplazar la sección existente
            md_content = re.sub(r"## Cambios Recientes.*?(?=##|\Z)", changes_section, md_content, flags=re.DOTALL)
        else:
            # Agregar la nueva sección antes de la última sección (normalmente "NOTA:")
            last_section_match = re.search(r"### \*\*NOTA:\*\*", md_content)
            if last_section_match:
                insert_position = last_section_match.start()
                md_content = md_content[:insert_position] + changes_section + md_content[insert_position:]
            else:
                # Si no hay sección "NOTA:", añadir al final
                md_content += changes_section
        # Guardar el archivo actualizado
        with open(md_file_path, 'w', encoding='utf-8') as f:
            f.write(md_content)
        print(f"Archivo MD actualizado con los cambios recientes: {md_file_path}")
    except Exception as e:
        print(f"Error actualizando el archivo MD: {e}")

def document_recent_changes(file_path, num_commits=1):
    """Documenta los cambios recientes en un archivo PHP."""
    file_path = Path(file_path)
    # Verificar que el archivo exista
    if not file_path.exists():
        print(f"Error: El archivo {file_path} no existe.")
        return
    # Obtener cambios de Git
    git_output = get_git_changes(file_path, num_commits)
    if not git_output:
        print(f"No se encontraron cambios recientes para {file_path}")
        return
    # Extraer información del commit
    commit_info = extract_commit_info(git_output)
    # Extraer y analizar cambios
    added_lines, removed_lines = parse_git_changes(git_output)
    changes = analyze_changed_elements(added_lines, removed_lines)
    # Ruta al archivo MD
    md_file_path = Path("docs") / file_path.with_suffix('.md').name
    # Verificar si el archivo MD existe, si no, crearlo primero
    if not md_file_path.exists():
        print(f"El archivo MD no existe. Generando documentación completa primero...")
        process_php_file(file_path)
    # Actualizar el archivo MD con los cambios
    update_md_with_changes(md_file_path, changes, commit_info)

# Ejecutar el script
# process_php_file(Path("files/reporte_app_consuertepay.php"))

# Para poder ejecutar como script independiente
if __name__ == "__main__":
    import sys
    if len(sys.argv) < 2:
        print("Uso: python docu_php.py <archivo_php> [cambios] [num_commits]")
        sys.exit(1)
        
    file_path = sys.argv[1]
    
    # Si se especifica "cambios", documentar solo los cambios recientes
    if len(sys.argv) > 2 and (sys.argv[2] == "cambios"):
        num_commits = 1
        if len(sys.argv) > 3 and sys.argv[3].isdigit():
            num_commits = int(sys.argv[3])
        document_recent_changes(file_path, num_commits)
    else:
        # Documentar todo el archivo
        process_php_file(Path(file_path))
