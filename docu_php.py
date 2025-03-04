from datetime import datetime
import re
from pathlib import Path
import logging
logging.basicConfig(level=logging.DEBUG, format='%(message)s')

def detect_languages(code):
    """Detecta los lenguajes de programación utilizados en el código."""
    languages = set()
    if re.search(r'<\?php', code):
        languages.add('PHP')
    if re.search(r'<script\b[^>]*>', code):
        languages.add('JavaScript')
    # Puedes agregar más lenguajes aquí si es necesario
    return languages

def detect_language_blocks(code):
    """Divide el código en bloques por lenguaje."""
    blocks = []
    php_pattern = re.compile(r'<\?php(.*?)\?>', re.DOTALL)
    js_pattern = re.compile(r'<script\b[^>]*>(.*?)</script>', re.DOTALL)
    
    # Extraer bloques PHP
    for match in php_pattern.finditer(code):
        blocks.append(('php', match.group(1)))
    
    # Extraer bloques JavaScript
    for match in js_pattern.finditer(code):
        blocks.append(('js', match.group(1)))
    
    return blocks

def parse_php_libraries(code):
    """Identifica las librerías incluidas en el código PHP y las limpia."""
    libraries = set()
    
    # Buscar includes y requires
    include_patterns = [
        r'include\s*\(?\s*[\'"](.*?)[\'"]\s*\)?\s*;',
        r'require\s*\(?\s*[\'"](.*?)[\'"]\s*\)?\s*;',
        r'include_once\s*\(?\s*[\'"](.*?)[\'"]\s*\)?\s*;',
        r'require_once\s*\(?\s*[\'"](.*?)[\'"]\s*\)?\s*;'
    ]
    
    # Buscar librerías cargadas mediante echo
    echo_patterns = [
        r'echo\s*[\'"]<script\s+src=[\'"](.*?)[\'"]',
        r'echo\s*[\'"]<link\s+rel=[\'"]stylesheet[\'"]\s+href=[\'"](.*?)[\'"]'
    ]
    
    # Buscar en includes/requires
    for pattern in include_patterns:
        matches = re.findall(pattern, code)
        for match in matches:
            if match.endswith('.php') or match.endswith('.js') or match.endswith('.css'):
                libraries.add(clean_library_name(match))  # Limpiar el nombre
    
    # Buscar en echos
    for pattern in echo_patterns:
        matches = re.findall(pattern, code)
        for match in matches:
            libraries.add(clean_library_name(match))  # Limpiar el nombre
    
    return sorted(libraries)

def clean_library_name(lib_name):
    """Limpia el nombre de la librería eliminando rutas y extensiones innecesarias."""
    # Eliminar prefijos de rutas
    lib_name = lib_name.split('/')[-1]  # Solo conservar el último segmento
    lib_name = lib_name.split('.')[0]  # Eliminar la extensión
    return lib_name

def clean_variable_name(var_name):
    """Limpia el nombre de la variable eliminando operadores y caracteres no deseados."""
    var_name = var_name.strip("'\"")
    var_name = re.sub(r'[=!<>]+.*$', '', var_name)
    var_name = re.sub(r'\[.*?\]', '', var_name)
    var_name = re.sub(r'[\+\-\*\/\%\^\&\|\~].*$', '', var_name)
    return var_name.strip()

def should_exclude_variable(var_name):
    """Determina si una variable debe ser excluida."""
    exclude_patterns = [
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
    """Variables PHP separando globales y locales."""
    code_sin_comments = re.sub(r'//.*?$|/\*.*?\*/|\#.*?$', '', code, flags=re.MULTILINE | re.DOTALL)
    variables = set()
    globales = set()

    # Detect class properties
    class_props = re.findall(r'\$this->(\w+)', code_sin_comments)
    for prop in class_props:
        variables.add(f'$this->{prop}')

    # Detect global variables
    global_matches = re.findall(r'global\s+([\$a-zA-Z_]\w*(?:\s*,\s*[\$a-zA-Z_]\w*)*)', code_sin_comments)
    for match in global_matches:
        vars_in_declaration = re.findall(r'[\$a-zA-Z_]\w*', match)
        globales.update(vars_in_declaration)

    if '$GLOBALS' in code_sin_comments:
        globales.add('$GLOBALS')

    # Detect function parameters
    func_params = re.findall(r'function\s*\((.*?)\)', code_sin_comments)
    for params in func_params:
        for param in params.split(','):
            param = param.strip()
            if param.startswith('$'):
                clean_var = clean_variable_name(param)
                if clean_var and not should_exclude_variable(clean_var):
                    variables.add(clean_var)

    # Detect regular variables
    var_patterns = [
        r'\$[a-zA-Z_]\w*',  # Basic variables
        r'\$[a-zA-Z_]\w*\s*=',  # Assignments
        r'\$[a-zA-Z_]\w*\s*[=!<>]+',  # Comparisons
        r'"\$[a-zA-Z_]\w*"',  # String interpolation double quotes
        r'\'\$[a-zA-Z_]\w*\'',  # String interpolation single quotes
    ]

    for pattern in var_patterns:
        matches = re.findall(pattern, code_sin_comments)
        for match in matches:
            clean_var = clean_variable_name(match)
            if clean_var and not should_exclude_variable(clean_var):
                variables.add(clean_var)

    locales = variables - globales
    return {'globales': globales, 'locales': locales}

def parse_js_variables(code):
    """Variables JS (var, let, const)."""
    return {match[1] for match in re.findall(r'\b(var|let|const)\s+(\w+)', code)}

def parse_php_functions(code):
    """Funciones PHP (function y métodos de clase)."""
    functions = set()
    
    # Regular functions
    func_matches = re.findall(r'function\s+(\w+)\s*\(', code)
    functions.update(func_matches)
    
    # Class methods
    class_matches = re.findall(r'class\s+\w+\s*{([^}]*)}', code)
    for class_body in class_matches:
        method_matches = re.findall(r'function\s+(\w+)\s*\(', class_body)
        functions.update(method_matches)
    
    return functions

def parse_js_functions(code):
    """Funciones JS (function y arrow)."""
    functions = set()
    func_matches = re.findall(r'function\s+(\w+)|const\s+(\w+)\s*=\s*\(.*?\)\s*=>', code)
    for match in func_matches:
        if match[0]:  # Named function
            functions.add(match[0])
        elif match[1]:  # Arrow function
            functions.add(match[1])
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
    try:
        code = Path(file_path).read_text(encoding='utf-8')
    except UnicodeDecodeError:
        code = Path(file_path).read_text(encoding='ISO-8859-1')
    blocks = detect_language_blocks(code)

    php_vars = {'globales': set(), 'locales': set()}
    php_funcs = set()
    js_vars = set()
    js_funcs = set()
    libraries = set()
    tables = set()
    conectores = set()

    # Detectar lenguajes utilizados
    languages = detect_languages(code)

    for lang, block in blocks:
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

    generate_md(
        file_path,
        {'variables': php_vars, 'functions': php_funcs, 'libraries': libraries, 'tables': tables, 'conectores': conectores},
        {'variables': js_vars, 'functions': js_funcs},
        languages
    )

# Ejecutar el script
process_php_file(Path("files/generar_reporte_app_recaudos_consuertepay.php"))