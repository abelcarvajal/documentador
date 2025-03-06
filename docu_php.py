from datetime import datetime
import re
import chardet
from pathlib import Path
import logging
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
        js_code = match.group(1)
        logging.info(f"Found JS block of {len(js_code)} characters")
        blocks.append(('js', js_code))
    
    # Si no se encontró ningún bloque, tratar todo como PHP
    if not blocks and code.strip():
        logging.info("No language blocks found, assuming entire content is PHP")
        blocks.append(('php', code))
    
    return blocks

def parse_php_libraries(code):
    """Identifica todas las librerías incluidas en el código PHP."""
    libraries = set()
    
    # Eliminar comentarios para evitar falsos positivos
    code_sin_comentarios = re.sub(r'/\*[\s\S]*?\*/|//.*?$', '', code, flags=re.MULTILINE)
    
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
        r'^\$\d+'
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
    code_sin_comentarios = re.sub(r'/\*[\s\S]*?\*/|//.*?$', '', code, flags=re.MULTILINE)
    
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
    
    patterns = [
        r'\b(?:var|let|const)\s+(\w+)\b',
        r'(?:window|global)\.(\w+)\s*=',
        r'this\.(\w+)\s*='
    ]
    
    for pattern in patterns:
        matches = re.finditer(pattern, code)
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
    code = re.sub(r'/\*[\s\S]*?\*/|//.*?$', '', code, flags=re.MULTILINE)
    
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
    
    patterns = [
        r'function\s+(\w+)\s*\(',  # Funciones normales
        r'const\s+(\w+)\s*=\s*\([^)]*\)\s*=>', # Arrow functions
        r'let\s+(\w+)\s*=\s*\([^)]*\)\s*=>', # Arrow functions con let
        r'var\s+(\w+)\s*=\s*\([^)]*\)\s*=>', # Arrow functions con var
        r'(\w+)\s*:\s*function\s*\(' # Métodos de objeto
    ]
    
    for pattern in patterns:
        matches = re.finditer(pattern, code)
        for match in matches:
            functions.add(match.group(1))
            
    return functions

def leer_conexiones_md(archivo_conexiones):
    """Lee el archivo conexiones.md y devuelve un diccionario con las conexiones y sus descripciones."""
    conexiones_md = {}
    with open(archivo_conexiones, 'r', encoding='utf-8') as f:
        for line in f:
            match = re.match(r'\*\s+\`(\$[a-zA-Z0-9_]+)\`: (.+)', line)
            if match:
                conexiones_md[match.group(1)] = match.group(2)
    return conexiones_md

def parse_conector_db(code, archivo_conexiones="conexiones.md"):
    """Detecta y lista los tipos de conectores de base de datos utilizados, con descripciones."""
    conectores = set()
    conexiones_md = leer_conexiones_md(archivo_conexiones)

    # Buscar todas las variables que se utilizan con el operador `->`
    variable_pattern = re.compile(r'(\$[a-zA-Z_]\w*)\s*->')
    variables = set(variable_pattern.findall(code))

    logging.debug(f"Variables encontradas con `->`: {variables}")

    # Verificar si alguna de las variables coincide con las conexiones conocidas
    for var in variables:
        if var in conexiones_md:
            logging.debug(f"Conexión potencial encontrada: {var}")
            # Verificar si la conexión se utiliza en una operación de base de datos
            if re.search(rf'{re.escape(var)}\s*->\s*(conectar|_Execute|Execute|query|prepare)\s*\(', code, re.IGNORECASE):
                logging.debug(f"Conexión utilizada: {var}")
                descripcion = conexiones_md.get(var, "Descripción no encontrada")
                logging.debug(f"Descripción de la conexión: {descripcion}")
                conectores.add(var)
            else:
                logging.debug(f"Conexión no utilizada: {var}")

    return sorted(conectores)

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
    """Consulta las tablas usadas en el código PHP, ignorando comentarios."""
    tables = set()

    # Eliminar comentarios del código
    code_sin_comentarios = re.sub(r'//.*?$|/\*.*?\*/|\#.*?$', '', code, flags=re.MULTILINE | re.DOTALL)

    # Detectar patrones SQL en la cláusula FROM, JOIN y UPDATE
    sql_pattern = re.compile(
        r'\b(SELECT|INSERT|UPDATE|DELETE)\s+.*?\bFROM\s+([`\'"]?)(\w+)(?:\.\w+)?\2(?:\s+AS\s+\w+)?'  # FROM clause
        r'|'  # OR
        r'\b(SELECT|INSERT|UPDATE|DELETE)\s+.*?\bJOIN\s+([`\'"]?)(\w+)(?:\.\w+)?\6(?:\s+AS\s+\w+)?'  # JOIN clause
        r'|'  # OR
        r'\bUPDATE\s+([`\'"]?)(\w+)(?:\.\w+)?\7\b'  # UPDATE clause
        r'|'  # OR
        r'\bINSERT\s+INTO\s+([`\'"]?)(\w+)(?:\.\w+)?\8\b'  # INSERT INTO clause
        r'|'  # OR
        r'\bDELETE\s+FROM\s+([`\'"]?)(\w+)(?:\.\w+)?\9\b'  # DELETE FROM clause
        r'(?:,\s*([`\'"]?)(\w+)(?:\.\w+)?\4(?:\s+AS\s+\w+)?)*',  # Multiple tables
        re.IGNORECASE | re.DOTALL
    )

    # Buscar tablas en las consultas
    matches = sql_pattern.findall(code_sin_comentarios)

    # Loguear las coincidencias encontradas
    logging.debug(f"SQL Matches found: {matches}")

    for match in matches:
        # Extraer las tablas de los diferentes grupos de captura
        for i in range(2, len(match), 3):  # Saltar el primer grupo (comando SQL) y avanzar de 3 en 3
            table = match[i]
            if table and re.match(r'^[a-zA-Z_][a-zA-Z0-9_]*$', table):  # Validar nombre de tabla
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
    
    md_content = f"""## **Nombre Aplicativo:** {file_path.name}
### **Fecha de creación/modificación: ** {datetime.now().strftime('%Y-%m-%d')}

### Lenguaje de programación utilizado:
* {languages_str}

### Librerías
* {php_libs_str}

### **Descripción del apliicativo:**

```

```

### **URL**

* {file_path.name} -> [Ruta archivo: {file_path.name}](../{file_path.name})

### **URL / .md Documentación Archivos Implementados **

* {file_md} -> [Ruta archivo: {file_md}](../guia_programador/{file_md})

### Funciones:
# **PHP: **

```
function {php_funcs_str}
```

# ** JavaScript: **

* function {js_funcs_str}

### **Listado de Variables implicadas **

# **PHP:**
```
- {php_locales_str}
```

# **JavaScript:**
* {js_vars_str}

### **Listado de Variables Globales**
# **PHP:**

* {php_globales_str}

### **Conexion a base de datos**

* {base_datos_str}

### **Tablas Base de datos consultadas / Entidad relacion**
* {php_tables_str}

### **Tipo de Conector Bases de Datos:**

* {conectores_str}

### **Elaborado  Por:**

José Abel Carvajal

### **NOTA:**
"""
    
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
    
    # Procesar cada bloque por lenguaje
    for lang, block in blocks:
        logging.info(f"Processing {lang} block with {len(block)} characters")
        if lang == 'php':
            parsed_vars = parse_php_variables(block)
            php_vars['globales'].update(parsed_vars['globales'])
            php_vars['locales'].update(parsed_vars['locales'])
            php_funcs.update(parse_php_functions(block))
            libraries.update(parse_php_libraries(block))
            tables.update(parse_php_tables(block))
            conectores.update(parse_conector_db(block))
        elif lang == 'js':
            js_vars.update(parse_js_variables(block))
            js_funcs.update(parse_js_functions(block))
    
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

# Ejecutar el script
process_php_file(Path("files/datos.php"))