from datetime import datetime
import re
import chardet
from pathlib import Path
import logging
import subprocess
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('docu_php.log'),
        logging.StreamHandler()
    ]
)

OUTPUT_DIR = Path("documentador/docs")
ENCODINGS_TO_TRY = ['ISO-8859-1', 'windows-1252', 'latin-1', 'utf-8']

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
        js_code = match.group(1)
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
    """Detecta las librerías y servicios importados en el código PHP."""
    libraries = set()
    
    # Buscar declaraciones use
    use_pattern = r'use\s+([^;]+);'
    matches = re.finditer(use_pattern, code)
    
    for match in matches:
        lib = match.group(1).strip()
        # Eliminar alias si existen
        if ' as ' in lib:
            lib = lib.split(' as ')[0].strip()
        # Formatear nombre de librería
        if lib.startswith('Symfony\\') or lib.startswith('PhpOffice\\') or lib.startswith('App\\'):
            libraries.add(lib)
            logging.debug(f"Library found: {lib}")

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

def parse_php_services(code):
    """
    Detecta los servicios inyectados en el controlador Symfony.
    Analiza el constructor y las propiedades de la clase.
    """
    services = set()
    
    # Eliminar comentarios para mejor análisis
    code_sin_comentarios = remove_comments(code)
    
    # Patrones para detectar servicios
    patterns = [
        # Constructor injection
        r'public\s+function\s+__construct\s*\((.*?)\)',
        
        # Property injection
        r'#\[Autowire\]\s*(?:private|protected|public)\s+(\w+)\s+\$\w+',
        
        # Service property declarations
        r'private\s+(\w+)\s+\$\w+;'
    ]
    
    # Buscar servicios en el constructor
    constructor_match = re.search(patterns[0], code_sin_comentarios, re.DOTALL)
    if constructor_match:
        params = constructor_match.group(1)
        # Buscar tipos de servicios en los parámetros
        service_matches = re.finditer(r'(\w+)\s+\$\w+', params)
        for match in service_matches:
            service_type = match.group(1)
            if not service_type.startswith(('int', 'string', 'bool', 'array', 'float')):
                services.add(service_type)
                logging.debug(f"Service found in constructor: {service_type}")
    
    # Buscar servicios autowired y declaraciones de propiedades
    for pattern in patterns[1:]:
        matches = re.finditer(pattern, code_sin_comentarios)
        for match in matches:
            service_type = match.group(1)
            if not service_type.startswith(('int', 'string', 'bool', 'array', 'float')):
                services.add(service_type)
                logging.debug(f"Service found in properties: {service_type}")
    
    return sorted(services)

def parse_php_routes(code):
    """
    Detecta las rutas definidas en el controlador Symfony.
    Busca tanto anotaciones como atributos de ruta.
    """
    routes = []
    
    # Patrones para detectar rutas
    patterns = [
        # Patrón para atributos (#[Route])
        r'#\[Route\([\'"]([^\'"]+)[\'"],\s*name:\s*[\'"]([^\'"]+)[\'"](?:,\s*methods:\s*\[([^\]]+)\])?\)',
        
        # Patrón para anotaciones (@Route)
        r'@Route\([\'"]([^\'"]+)[\'"],\s*name=[\'"]([^\'"]+)[\'"](?:,\s*methods=\{([^\}]+)\})?\)'
    ]
    
    for pattern in patterns:
        matches = re.finditer(pattern, code)
        for match in matches:
            route_data = {
                'path': match.group(1),
                'name': match.group(2),
                'methods': []
            }
            
            # Procesar métodos si existen
            if match.group(3):
                methods = match.group(3).replace('[', '').replace(']', '')
                methods = [m.strip().strip('"\'') for m in methods.split(',')]
                route_data['methods'] = methods
            
            routes.append(route_data)
            
    # También buscar rutas en la configuración YAML convertida a array
    yaml_pattern = r'path:\s*([^\n]+)\s*controller:\s*([^\n]+)\s*methods:\s*\[([^\]]+)\]'
    yaml_matches = re.finditer(yaml_pattern, code)
    
    for match in yaml_matches:
        route_data = {
            'path': match.group(1).strip(),
            'controller': match.group(2).strip(),
            'methods': [m.strip().strip('"\'') for m in match.group(3).split(',')]
        }
        routes.append(route_data)
    
    return routes

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
    """Determina si una variable debe ser excluida del análisis."""
    exclude_patterns = [
        r'^\d+$',  # Variables numéricas
        r'^(key|value|row|link|item)$',  # Variables comunes de iteración
        r'^(GET|POST|REQUEST|SESSION|COOKIE|SERVER|ENV|FILES|GLOBALS)$',  # Superglobales
        r'^(this|self)$',  # Referencias a la clase
        r'^(_|__).*'  # Variables que comienzan con guión bajo
    ]
    
    return any(re.match(pattern, var_name) for pattern in exclude_patterns)

def parse_php_variables(code):
    """
    Detecta variables globales (propiedades de clase) y locales en código PHP.
    Excluye código en comentarios.
    """
    # Primero eliminar todos los comentarios
    code_sin_comentarios = remove_comments(code)
    
    variables = {
        'globales': set(),
        'locales': set()
    }
    
    # Patrones para detectar variables de clase (globales)
    class_var_patterns = [
        r'private\s+\$(\w+)\s*(?:=\s*[^;]+)?;',
        r'protected\s+\$(\w+)\s*(?:=\s*[^;]+)?;',
        r'public\s+\$(\w+)\s*(?:=\s*[^;]+)?;'
    ]
    
    # Detectar variables de clase
    for pattern in class_var_patterns:
        matches = re.finditer(pattern, code_sin_comentarios)
        for match in matches:
            var_name = match.group(1)
            if var_name and not var_name.startswith('_'):
                variables['globales'].add(var_name)
                logging.debug(f"Variable de clase encontrada: {var_name}")
    
    # Detectar variables locales dentro de funciones
    # Excluir variables que ya son globales o propiedades de clase
    local_patterns = [
        r'\$(\w+)\s*=(?!=)',  # Asignaciones
        r'foreach\s*\(\s*\$(\w+)\s+as',  # Variables de foreach
        r'function\s+\w+\s*\([^)]*\$(\w+)[^)]*\)',  # Parámetros de función
    ]
    
    for pattern in local_patterns:
        matches = re.finditer(pattern, code_sin_comentarios)
        for match in matches:
            var_name = match.group(1)
            if (var_name and 
                not var_name.startswith('_') and 
                not var_name.startswith('this->') and
                var_name not in variables['globales'] and
                not should_exclude_variable(var_name)):
                variables['locales'].add(var_name)
                logging.debug(f"Variable local encontrada: {var_name}")
    
    # Logging para depuración
    logging.debug(f"Variables globales encontradas: {sorted(list(variables['globales']))}")
    logging.debug(f"Variables locales encontradas: {sorted(list(variables['locales']))}")
    
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

# ------------------------------FUNCIÓN DE DEPURACIÓN------------------------------
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

#***********************************************************************************

def parse_php_functions(code):
    """Detecta todas las funciones y métodos PHP con patrones avanzados."""
    functions = set()
    
    # Primero eliminamos los comentarios
    code = remove_comments(code)
    
    # Patrones mejorados para detectar funciones
    patterns = [
        # Funciones normales
        r'function\s+(\w+)\s*\([^)]*\)',
        
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

def leer_conexiones_md(archivo_conexiones="documentador/conexiones_sgd.md"):
    """Lee el archivo conexiones_sgd.md y devuelve un diccionario con las conexiones y sus descripciones."""
    conexiones_md = {}
    with open(archivo_conexiones, 'r', encoding='utf-8') as f:
        for line in f:
            match = re.match(r'\*\s+\`(\$[a-zA-Z0-9_]+)\`: (.+)', line)
            if match:
                conexiones_md[match.group(1)] = match.group(2)
    return conexiones_md

def parse_conector_db(code):
    """Detecta los conectores y sus conexiones a bases de datos."""
    conectores = set()
    connections = set()
    
    # Eliminar comentarios
    code_sin_comentarios = remove_comments(code)
    
    try:
        # Leer archivo de conexiones
        with open('documentador/conexiones_sgd.md', 'r', encoding='utf-8') as f:
            conexiones_content = f.read()
            conexiones_mapping = {}
            
            # Extraer mapeo ID -> nombre_conexion
            for match in re.finditer(r'\*\s+(\d+):\s+`([^`]+)`', conexiones_content):
                id_conexion = match.group(1)
                nombre_conexion = match.group(2).split(' ')[0]  # Tomar primer término
                conexiones_mapping[id_conexion] = nombre_conexion
            
            # Buscar uso de query con ID de conexión
            query_pattern = r'\$(?:this->)?cnn->query\s*\(\s*[\'"](\d+)[\'"](?:\s*,\s*\$[^\)]+)?\)'
            for match in re.finditer(query_pattern, code_sin_comentarios):
                ejemplo_uso = match.group(0)
                id_conexion = match.group(1)
                
                # Agregar ejemplo de uso del conector
                ejemplo_limpio = re.sub(r'\$(?:this->)?', '', ejemplo_uso)
                conectores.add(ejemplo_limpio)
                
                # Agregar nombre de conexión
                if id_conexion in conexiones_mapping:
                    connections.add(conexiones_mapping[id_conexion])
                    logging.debug(f"Conexión detectada: {conexiones_mapping[id_conexion]} (ID: {id_conexion})")
    
    except FileNotFoundError:
        logging.error("No se encontró el archivo conexiones_sgd.md")
    
    return sorted(conectores), sorted(connections)

def parse_php_tables(code):
    """
    Detecta las tablas consultadas en consultas SQL dentro del código PHP.
    """
    tables = set()
    
    # Primero eliminar comentarios
    code_sin_comentarios = remove_comments(code)
    
    # Palabras reservadas SQL que no son nombres de tabla
    sql_keywords = {
        'ON', 'WHERE', 'AND', 'OR', 'AS', 'IN', 'SELECT', 'INSERT', 'UPDATE', 
        'DELETE', 'FROM', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'OUTER', 'CROSS',
        'GROUP', 'ORDER', 'BY', 'HAVING', 'LIMIT', 'OFFSET', 'UNION', 'ALL'
    }
    
    # Encontrar todas las asignaciones SQL
    sql_queries = re.finditer(r'\$sql(?:\d*)\s*=\s*["\']([^"\']+)["\']', code_sin_comentarios)
    
    for sql_match in sql_queries:
        query = sql_match.group(1).upper()  # Convertir a mayúsculas para comparación
        
        # Patrones para detectar tablas
        patterns = [
            (r'FROM\s+([A-Z_][A-Z0-9_$]*)', 'FROM'),
            (r'JOIN\s+([A-Z_][A-Z0-9_$]*)', 'JOIN'),
            (r'INTO\s+([A-Z_][A-Z0-9_$]*)', 'INTO'),
            (r'UPDATE\s+([A-Z_][A-Z0-9_$]*)', 'UPDATE')
        ]
        
        for pattern, context in patterns:
            matches = re.finditer(pattern, query)
            for match in matches:
                table_name = match.group(1).strip()
                
                # Verificar que no sea una palabra reservada
                if (table_name not in sql_keywords and 
                    not table_name.startswith(('SELECT', 'INSERT', 'UPDATE', 'DELETE'))):
                    tables.add(table_name)
                    logging.debug(f"Tabla SQL encontrada: {table_name} (contexto: {context})")
    
    return sorted(tables)

def generate_md(file_path: Path, php_data: dict, js_data: dict, languages: set):
    """Genera el archivo MD con la documentación según la plantilla establecida."""
    OUTPUT_DIR.mkdir(exist_ok=True)
    file_md = file_path.with_suffix('.md').name
    output_file = OUTPUT_DIR / file_md

    # Formatear datos
    php_globales = sorted(php_data['variables']['globales'])
    php_locales = sorted(php_data['variables']['locales'])
    php_funcs = sorted(php_data['functions'])
    php_libs = sorted(php_data.get('libraries', []))
    php_tables = sorted(php_data.get('tables', []))
    conectores, connections = php_data.get('conectores', ([], []))
    services = sorted(php_data.get('services', []))
    routes = php_data.get('routes', [])
    
    # Formatear strings
    php_globales_str = '\n'.join([f'* ${var}' for var in sorted(php_data['variables']['globales'])]) if php_data['variables']['globales'] else '*Sin variables globales*'
    php_locales_str = '\n'.join([f'* ${var}' for var in sorted(php_data['variables']['locales'])]) if php_data['variables']['locales'] else '*Sin variables locales*'
    php_funcs_str = '\n* '.join(php_funcs) if php_funcs else '*Sin funciones*'
    php_libs_str = '\n* '.join(php_libs) if php_libs else '*Sin librerías*'
    php_tables_str = '\n* '.join(php_tables) if php_tables else '*Sin tablas*'
    conectores_str = '\n* '.join(conectores) if conectores else '*No se detectó uso del conector*'
    connections_str = '\n* '.join(connections) if connections else 'No se detectó conexión base de datos'
    languages_str = '\n* '.join(sorted(languages)) if languages else '*Sin lenguajes detectados*'
    services_str = '\n* '.join(services) if services else '*Sin servicios detectados*'
    
    # Formatear rutas
    routes_str = ''
    if routes:
        for route in routes:
            path = route.get('path', '')
            name = route.get('name', '')
            methods = ', '.join(route.get('methods', ['GET']))
            routes_str += f"* {path}:\n  path: {path}\n  controller: {name}\n  methods: [{methods}]\n\n"
    else:
        routes_str = '*No se detectaron rutas*'

    md_content = f"""<!-- filepath: {file_path} -->
# **TICKET:** #
### **Fecha de creación/modificación: ** {datetime.now().strftime('%Y-%m-%d')}
## **Nombre Aplicativo:** {file_path.stem}

### **Descripcion Aplicativo:**
```

```

### **Librerias**
* {php_libs_str}

### **Servicios**
* {services_str}

### **Lenguaje de programación utilizado:**
* {languages_str}

### **URL**
* {file_path.name} -> [Ruta archivo: {file_path.name}](../../src/Controller/{file_path.name})

### **URL / .md Documentación Archivos Implementados**
* {file_md} -> [Ruta archivo: {file_md}](../../public/guia_programador/{file_md})

# **PHP:**

### **Listado de Variables Globales**
{php_globales_str}

### **Listado de Variables**
```
{php_locales_str}
```
### **Tablas Base de datos consultadas / Entidad relacion**
* {php_tables_str}

### **Tipo de Conector Bases de Datos**
* {conectores_str}

## **Conexión a base de datos**
* {connections_str}

### **Funciones**
* {php_funcs_str}

## Rutas
{routes_str}

### **Realizado por:**
José Abel Carvajal
"""

    output_file.write_text(md_content, encoding='utf-8')
    logging.info(f"Generated documentation: {output_file}")

def process_php_file(file_path):
    """Procesa un archivo PHP con mejor detección de componentes."""
    file_path = Path(file_path)
    logging.info(f"Processing file: {file_path}")
    
    # Comprobar si ya exxiste un archivo MD de documentación
    md_file_path =OUTPUT_DIR / file_path.with_suffix('.md').name
    
    if md_file_path.exists():
        logging.info(f"Documentacion existente en: {md_file_path}. Actualizando con camvios recientes...")
        
        #   Si el aarchivo MD ya existe, documentar solo los cambios recientes en el archivo PHP
        
        document_recent_changes(file_path)
        return
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
    # Obtener rutas
    routes = parse_php_routes(code)
    conectores, connections = parse_conector_db(code)
    # Inicializar contenedores
    php_vars = {'globales': set(), 'locales': set()}
    php_funcs = set()
    js_vars = set()
    js_funcs = set()
    libraries = set()
    tables = set()
    conectores = set()
    connections = set()
    all_php_functions = set()
    all_js_functions = set()
    services = set()
    
    # Detectar rutas y conexiones al inicio
    routes = parse_php_routes(code)
    conector_data = parse_conector_db(code)
    if conector_data[0]:  # Si hay conectores
        conectores.update(conector_data[0])
    if conector_data[1]:  # Si hay conexiones
        connections.update(conector_data[1])
    
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
            services.update(parse_php_services(block))
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
        {
            'variables': php_vars, 
            'functions': php_funcs, 
            'libraries': libraries, 
            'tables': tables, 
            'conectores': (list(conectores), list(connections)),
            'routes': routes,
            'services': services
        },
        {'variables': js_vars, 'functions': js_funcs},
        languages
    )

# Logica de actualización de documentación haciendo uso de git

def get_git_changes(file_path, num_commits=1, author=None, since=None):
    """Obtiene los cambios del archivo usando git diff."""
    try:
        # Primero verificar si hay cambios del archivo
        check_cmd = ["git", "log", "-1", "--", str(file_path)]
        check_result = subprocess.run(check_cmd, capture_output=True, text=True)
        
        if not check_result.stdout:
            logging.warning(f"No se encontraron commits para {file_path}")
            return "", "", ""
        
        # Si hay commits, obtener el diff
        # Permitir especificar rango de commits explícitamente 
        if num_commits > 1:
            cmd = ["git", "log", "-p", f"-{num_commits}"]
        else:
            # Para comparar últimos dos commits del archivo
            cmd = ["git", "log", "-p", "-1"]
            
        if author:
            cmd.extend(["--author", author])
        if since:
            cmd.extend(["--since", since])
            
        cmd.extend(["--", str(file_path)])
        
        logging.debug(f"Ejecutando comando: {' '.join(cmd)}")
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        if result.returncode != 0:
            logging.error(f"Error ejecutando git: {result.stderr}")
            return "", "", ""
            
        git_output = result.stdout
        
        if not git_output:
            logging.info("No se encontraron cambios")
            return "", "", ""
        
        # Extraer información del commit y contenido
        sections = git_output.split("diff --git")
        commit_info = sections[0]  # La primera sección tiene la info del commit
        
        # Procesar el diff para obtener contenido viejo y nuevo
        old_content = []
        new_content = []
        
        for section in sections[1:]:  # El resto son los diffs
            for line in section.split('\n'):
                if line.startswith('-') and not line.startswith('---'):
                    old_content.append(line[1:])
                elif line.startswith('+') and not line.startswith('+++'):
                    new_content.append(line[1:])
        
        logging.debug(f"Contenido obtenido - Anterior: {len(old_content)} líneas, Nuevo: {len(new_content)} líneas")
        
        return '\n'.join(old_content), '\n'.join(new_content), extract_commit_info(commit_info)
            
    except subprocess.CalledProcessError as e:
        logging.error(f"Error git: {e}")
        logging.error(f"Comando que falló: {e.cmd}")
        logging.error(f"Salida de error: {e.stderr}")
        return "", "", ""
    
def parse_git_changes(git_diff):
    """Analiza el diff de Git para extraer líneas agregadas y eliminadas."""
    added_lines = []
    removed_lines = []
    current_function = None
    in_diff_section = False
    in_hunk = False
    
    for line in git_diff.split('\n'):
        # Detectar inicio de sección diff
        if line.startswith('diff --git'):
            in_diff_section = True
            current_function = None
            continue
            
        # Detectar inicio de hunk (fragmento modificado)
        if line.startswith('@@'):
            in_hunk = True
            # Intentar detectar contexto de función del hunk
            func_context = re.search(r'@@ .+ @@ (?:.*?function\s+(\w+)|.*?(\w+)\s*\()', line)
            if func_context:
                current_function = func_context.group(1) or func_context.group(2)
                logging.debug(f"Contexto de hunk: función {current_function}")
            continue
        
        if in_hunk and in_diff_section:
            # Detectar contexto de función dentro del diff
            if 'function' in line:
                func_match = re.search(r'function\s+(\w+)', line)
                if func_match:
                    current_function = func_match.group(1)
                    logging.debug(f"Detectada función: {current_function}")
            
            # Procesar líneas agregadas o eliminadas
            if line.startswith('+') and not line.startswith('+++'):
                line_content = line[1:].strip()
                if line_content and not line_content.startswith('//'):
                    context = f"{current_function}:" if current_function else ""
                    added_lines.append(f"{context}{line_content}")
                    logging.debug(f"Línea agregada {context}: {line_content}")
                    
            elif line.startswith('-') and not line.startswith('---'):
                line_content = line[1:].strip()
                if line_content and not line_content.startswith('//'):
                    context = f"{current_function}:" if current_function else ""
                    removed_lines.append(f"{context}{line_content}")
                    logging.debug(f"Línea eliminada {context}: {line_content}")
    
    return '\n'.join(added_lines), '\n'.join(removed_lines)

def analyze_changed_elements(old_content, new_content):
    """Analiza los elementos modificados en el código."""
    changes = {
        'functions': {'added': set(), 'modified': set(), 'removed': set()},
        'variables': {'added': set(), 'modified': set(), 'removed': set()},
        'tables': {'added': set(), 'modified': set(), 'removed': set()},
        'libraries': {'added': set(), 'modified': set(), 'removed': set()},
        'services': {'added': set(), 'modified': set(), 'removed': set()},
        'routes': {'added': set(), 'modified': set(), 'removed': set()}
    }

    # Procesar cada tipo de elemento
    processors = {
        'functions': lambda x: set(parse_php_functions(x)),
        'variables': lambda x: set(parse_php_variables(x)['locales']),
        'tables': lambda x: set(parse_php_tables(x)),
        'libraries': lambda x: set(parse_php_libraries(x)),
        'services': lambda x: set(parse_php_services(x)),
        'routes': lambda x: {r['path'] for r in parse_php_routes(x)}
    }

    # Analizar cambios
    for element_type, processor in processors.items():
        old_elements = processor(old_content)
        new_elements = processor(new_content)
        
        # Detectar elementos agregados/eliminados/modificados
        changes[element_type]['added'] = new_elements - old_elements
        changes[element_type]['removed'] = old_elements - new_elements
        changes[element_type]['modified'] = old_elements & new_elements
        
        # Logging para depuración
        logging.debug(f"{element_type}:")
        logging.debug(f"  Agregados: {changes[element_type]['added']}")
        logging.debug(f"  Eliminados: {changes[element_type]['removed']}")
        logging.debug(f"  Modificados: {changes[element_type]['modified']}")

    return changes

def extract_commit_info(git_output):
    """Extrae información del commit de forma más limpia."""
    commit_info = ""
    message = []
    in_message = False
    
    for line in git_output.split('\n'):
        if line.startswith('commit '):
            commit_info += f"Commit: {line.split(' ')[1]}\n"
        elif line.startswith('Author: '):
            commit_info += f"Autor: {line[8:]}\n"
        elif line.startswith('Date: '):
            commit_info += f"Fecha: {line[6:]}\n"
        elif line.startswith('    ') and not line.strip().startswith(('index ', 'diff --git')):
            # Capturar mensaje del commit
            message.append(line.strip())
        elif line.startswith('diff --git'):
            break
    
    if message:
        commit_info += f"Mensaje: {' '.join(message)}\n"
    
    return commit_info

def update_md_with_changes(md_file_path, changes, commit_info=""):
    """Actualiza el archivo MD con los cambios detectados."""
    try:
        with open(md_file_path, 'r', encoding='utf-8') as f:
            md_content = f.read()
        
        # Obtener fecha del commit
        commit_date = None
        for line in commit_info.split('\n'):
            if line.startswith('Fecha:'):
                commit_date = line.replace('Fecha:', '').strip()
                break
        
        if not commit_date:
            commit_date = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        # Crear nueva sección de cambios
        changes_section = f"\n\n## Cambios {commit_date}\n\n"
        
        if commit_info:
            changes_section += f"### Commit Info\n```\n{commit_info}\n```\n"
        
        # Secciones a mostrar en orden
        sections = [
            ('Librerías', 'libraries'),
            ('Servicios', 'services'),
            ('Rutas', 'routes'),
            ('Funciones', 'functions'),
            ('Variables', 'variables'),
            ('Tablas', 'tables')
        ]

        # Procesar cada sección
        for section_name, section_key in sections:
            changes_section += f"\n### {section_name}\n"
            if any(changes[section_key].values()):
                for change_type, items in [
                    ('Agregadas', 'added'),
                    ('Modificadas', 'modified'),
                    ('Eliminadas', 'removed')
                ]:
                    if changes[section_key][items]:
                        changes_section += f"#### {change_type}\n"
                        for item in sorted(changes[section_key][items]):
                            changes_section += f"* `{item}`\n"
            else:
                changes_section += "* Sin cambios detectados\n"
        
        # Agregar sección antes de NOTA, manteniendo el historial
        last_section_match = re.search(r"### \*\*NOTA:\*\*", md_content)
        if last_section_match:
            insert_position = last_section_match.start()
            md_content = md_content[:insert_position] + changes_section + "\n" + md_content[insert_position:]
        else:
            md_content += changes_section + "\n### **NOTA:**\n"
        
        with open(md_file_path, 'w', encoding='utf-8') as f:
            f.write(md_content)
            
        logging.info(f"Archivo MD actualizado: {md_file_path}")
        
        # Logging detallado de los cambios
        logging.debug("Contenido de changes:")
        for section, data in changes.items():
            logging.debug(f"{section}:")
            for change_type, items in data.items():
                logging.debug(f"  {change_type}: {items}")
        
    except Exception as e:
        logging.error(f"Error actualizando archivo MD: {e}")
        raise

def document_recent_changes(file_path, num_commits=1, author=None, since=None, commit_hash=None):
    """Documenta los cambios recientes en un archivo PHP."""
    file_path = Path(file_path)
    
    if not file_path.exists():
        print(f"Error: El archivo {file_path} no existe.")
        return
    
    # Opción para comparar con un commit específico
    if commit_hash:
        try:
            # Obtener contenido del commit específico
            cmd = ["git", "show", f"{commit_hash}:{file_path}"]
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            old_content = result.stdout
            
            # Leer contenido actual
            with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
                new_content = f.read()
                
            # Información del commit
            cmd = ["git", "show", "--pretty=format:'Commit: %H%nAutor: %an <%ae>%nFecha: %ad%nMensaje: %s'", "-s", commit_hash]
            result = subprocess.run(cmd, capture_output=True, text=True, check=True)
            commit_info = result.stdout
            
            logging.info(f"Comparando con commit: {commit_hash}")
        except subprocess.CalledProcessError as e:
            logging.error(f"Error obteniendo commit {commit_hash}: {e}")
            return
    else:
        # Método original
        old_content, new_content, commit_info = get_git_changes(file_path, num_commits, author, since)
        if not old_content and not new_content:
            print(f"No se encontraron cambios para los criterios especificados")
            return
        
    # Analizar cada versión del archivo
    old_elements = {
        'functions': set(parse_php_functions(old_content)),
        'variables': parse_php_variables(old_content)['globales'],
        'tables': set(parse_php_tables(old_content)),
        'libraries': set(parse_php_libraries(old_content)),
        'services': set(parse_php_services(old_content)),
        'routes': {r['path'] for r in parse_php_routes(old_content)}
    }
    
    new_elements = {
        'functions': set(parse_php_functions(new_content)),
        'variables': parse_php_variables(new_content)['globales'],
        'tables': set(parse_php_tables(new_content)),
        'libraries': set(parse_php_libraries(new_content)),
        'services': set(parse_php_services(new_content)),
        'routes': {r['path'] for r in parse_php_routes(new_content)}
    }
    
    # Detectar cambios
    changes = {
        element_type: {
            'added': new_elements[element_type] - old_elements[element_type],
            'removed': old_elements[element_type] - new_elements[element_type],
            'modified': new_elements[element_type] & old_elements[element_type]
        }
        for element_type in old_elements.keys()
    }
    
    # Actualizar archivo MD
    md_file_path = OUTPUT_DIR / file_path.with_suffix('.md').name
    if not md_file_path.exists():
        print(f"El archivo MD no existe. Generando documentación completa primero...")
        process_php_file(file_path)
    
    update_md_with_changes(md_file_path, changes, commit_info)
    
def debug_file_changes(file_path, num_commits=1):
    """Función de depuración para mostrar detalles de los cambios en el archivo."""
    try:
        # Obtener historial de cambios
        cmd = ["git", "log", "-5", "--pretty=oneline", "--", str(file_path)]
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        print("\n=== Últimos 5 commits que afectan al archivo ===")
        print(result.stdout)
        
        # Obtener último diff
        cmd = ["git", "log", "-p", "-1", "--", str(file_path)]
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        print("\n=== Último diff del archivo ===")
        print(result.stdout[:500] + "..." if len(result.stdout) > 500 else result.stdout)
        
        # Analizar el archivo actual
        with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
            content = f.read()
        
        print("\n=== Análisis del archivo actual ===")
        print(f"- Funciones detectadas: {parse_php_functions(content)}")
        print(f"- Variables globales: {parse_php_variables(content)['globales']}")
        print(f"- Rutas: {parse_php_routes(content)}")
        
        return True
    except Exception as e:
        print(f"Error en modo debug: {e}")
        return False

# Ejecutar el script
# process_php_file(Path("files/reporte_app_consuertepay.php"))
# Para poder ejecutar como script independiente
if __name__ == "__main__":
    import sys
    
    if len(sys.argv) < 2:
        print("""Uso: python docu_php.py <archivo_php> [opciones]
Opciones:
  cambios                - Documentar cambios recientes
  debug                  - Modo de depuración
  commits=N              - Número de commits a analizar (default: 1)
  autor=NOMBRE           - Filtrar por autor
  desde=FECHA            - Desde qué fecha (ej: "2 weeks ago")
  commit=HASH            - Comparar con commit específico
  
Ejemplos: 
- python docu_php.py DatosController.php
- python docu_php.py DatosController.php cambios
- python docu_php.py DatosController.php cambios autor=Desarrollo2
- python docu_php.py DatosController.php debug
- python docu_php.py DatosController.php cambios commit=a1b2c3d
""")
        sys.exit(1)
    
    file_path = sys.argv[1]
    
    # Procesar argumentos de manera más flexible
    args = {}
    for arg in sys.argv[2:]:
        if '=' in arg:
            key, value = arg.split('=', 1)
            args[key.lower()] = value
        else:
            args[arg.lower()] = True
    
    if "debug" in args:
        debug_file_changes(file_path)
    elif "cambios" in args:
        num_commits = int(args.get("commits", 1))
        author = args.get("autor")
        since = args.get("desde")
        commit_hash = args.get("commit")
        document_recent_changes(file_path, num_commits, author, since, commit_hash)
    else:
        process_php_file(Path(file_path))
