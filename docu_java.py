from datetime import datetime
import re
from pathlib import Path
import logging
import traceback

logging.basicConfig(level=logging.DEBUG, format='%(message)s')

def detect_package(code):
    """Detecta el paquete en el código Java."""
    match = re.search(r'package\s+([\w.]+);', code)
    return match.group(1) if match else None

def detect_language_blocks(code):
    """Divide el código en bloques de lenguaje."""
    blocks = []
    java_pattern = re.compile(r'(public\s+class\s+\w+.*?\{.*?\})', re.DOTALL)
    for match in java_pattern.finditer(code):
        blocks.append(('java', match.group(1)))
    return blocks

def parse_java_libraries(code):
    """Identifica las librerías (imports) en Java."""
    return sorted(set(re.findall(r'import\s+([\w.]+);', code)))

def parse_java_variables(code):
    # Expresión regular mejorada para variables de clase
    pattern = re.compile(r'\b(private|public|protected|static|final)\s+(?:static\s+)?(?:final\s+)?([A-Za-z\[\]<>]+)\s+(\w+)\s*;', re.DOTALL)
    return [
        {
            'access': ' '.join(modifier for modifier in match.group(1).split() if modifier),
            'type': match.group(2),
            'name': match.group(3),
            'static': 'static' in match.group(1), # Agregar la clave 'static'
            'final': 'final' in match.group(1)  # Agregar la clave 'final'
        }
        for match in pattern.finditer(code)
    ]

def parse_local_variables(code):
    # Expresión regular mejorada para variables locales, más robusta
    pattern = re.compile(r'\b(?:final\s+)?([A-Za-z\[\]<>]+)\s+(\w+)\b(?=[;\s,=])', re.DOTALL)
    return [
        {
            'access': 'local',
            'type': match.group(1),
            'name': match.group(2)
        }
        for match in pattern.finditer(code)
    ]

def parse_java_functions(code):
    """Extrae las funciones de una cadena de código Java."""
    if not isinstance(code, str):
        logging.error("Error: parse_java_functions recibió un objeto que no es una cadena")
        return []
        
    # Primero, extraer el nombre de la clase
    class_name_match = re.search(r'public\s+class\s+(\w+)', code)
    if not class_name_match:
        logging.warning("No se encontró ninguna clase pública en el código")
        return []
        
    class_name = class_name_match.group(1)
    logging.info(f"Clase encontrada: {class_name}")
    
    # Extraer todo el contenido de la clase (desde la declaración hasta el último corchete)
    class_content_pattern = re.compile(fr'public\s+class\s+{class_name}.*?\{{(.*)\}}$', re.DOTALL)
    class_content_match = class_content_pattern.search(code)
    
    if not class_content_match:
        logging.warning(f"No se pudo extraer el contenido de la clase {class_name}")
        return []
    
    class_body = class_content_match.group(1)
    functions = []
    
    # Patrón mejorado para detectar métodos, incluyendo constructores y métodos con parámetros especiales
    method_pattern = re.compile(
        r'(public|private|protected)(?:\s+static)?(?:\s+final)?'  # Modificadores de acceso
        r'\s+(?:(?:[\w<>\[\]]+(?:\s*<.*?>)?(?:\s*\[\s*\])*)|(?:void)|(?:' + re.escape(class_name) + r'))' # Tipo de retorno o constructor
        r'\s+(\w+)\s*\((.*?)\)'  # Nombre del método y parámetros
        r'\s*(?:\{|throws)', # Seguido por { o throws
        re.DOTALL
    )
    
    # Buscar constructores específicamente
    constructor_pattern = re.compile(
        fr'(public|private|protected)\s+{class_name}\s*\((.*?)\)\s*\{{', 
        re.DOTALL
    )
    
    # Buscar métodos de AsyncTask
    async_method_pattern = re.compile(
        r'(protected|public|private)\s+([\w<>\[\]]+(?:\s*<.*?>)?(?:\s*\[\s*\])*)\s+(\w+)\s*\((.*?)\.\.\.\s*(\w+)\)',
        re.DOTALL
    )
    
    # Buscar constructores
    for match in constructor_pattern.finditer(class_body):
        access_modifier = match.group(1)
        parameters = match.group(2).strip()
        
        functions.append({
            'access': access_modifier,
            'return_type': class_name,  # Constructor returns the class type
            'name': class_name,
            'parameters': parameters
        })
    
    # Buscar métodos normales
    for match in method_pattern.finditer(class_body):
        full_match = match.group(0)
        if "..." not in full_match:  # Skip varargs methods as they'll be handled by async_method_pattern
            access_modifier = match.group(1)
            # El tipo de retorno está entre el modificador de acceso y el nombre del método
            return_type_and_name = re.search(r'(?:' + re.escape(access_modifier) + r')\s+([\w<>\[\]]+(?:\s*<.*?>)?(?:\s*\[\s*\])*)\s+(\w+)', full_match)
            
            if return_type_and_name:
                return_type = return_type_and_name.group(1)
                function_name = return_type_and_name.group(2)
                parameters_match = re.search(r'\((.*?)\)', full_match)
                parameters = parameters_match.group(1).strip() if parameters_match else ""
                
                functions.append({
                    'access': access_modifier,
                    'return_type': return_type,
                    'name': function_name,
                    'parameters': parameters
                })
    
    # Buscar métodos con varargs (como doInBackground)
    for match in async_method_pattern.finditer(class_body):
        access_modifier = match.group(1)
        return_type = match.group(2)
        function_name = match.group(3)
        param_type = match.group(4)
        param_name = match.group(5)
        
        functions.append({
            'access': access_modifier,
            'return_type': return_type,
            'name': function_name,
            'parameters': f"{param_type}... {param_name}"
        })
    
    logging.info(f"Funciones encontradas: {len(functions)}")
    return functions

def generate_md(file_path: Path, java_data: dict, package: str):
    """Genera un archivo Markdown con la información extraída en el formato requerido."""
    try:
        output_dir = Path("docs/")
        output_dir.mkdir(exist_ok=True)
        output_file = output_dir / file_path.with_suffix('.md').name

        # Formatear librerías en una lista simple
        libraries = java_data.get('libraries', [])
        grouped_libraries = '\n'.join([f"* {lib}" for lib in libraries]) if libraries else "*Sin librerías*"

        # Variables
        java_vars_str = '\n'.join([
            f'* {var["access"]} {("static " if var.get("static", False) else "")}{("final " if var.get("final", False) else "")}{var["type"]} {var["name"]}'
            for var in java_data.get('variables', [])
        ]) or '*Sin variables*'

        # Funciones
        java_funcs_str = '\n'.join([
            f'* {func["access"]} {func["return_type"]} {func["name"]}({func["parameters"]})'
            for func in java_data.get('functions', [])
        ]) or '*Sin funciones*'

        # Obtener el nombre de la clase del archivo
        class_name = file_path.stem

        md_content = (
            f"# Documentación de {class_name}\n\n"
            f"### Descripción\n*  \n"
            f"### Paquete\n* package {package if package else 'No especificado'};\n\n"
            f"### Lenguaje de programación utilizado:\n* Java\n\n"
            f"### Librerías\n\n{grouped_libraries}\n\n"
            f"### **URL**\n\n"
            f"* {file_path.name} --> [Ruta: {file_path.name} ](../{file_path.name})\n\n"
            f"### **URL / .md Documentación Archivos Implementados**\n"
            f"* {file_path.name} --> [Ruta: {file_path.name} ](../administrativos.md/{file_path.with_suffix('.md').name})\n\n"
            f"### **Variables**\n\n{java_vars_str}\n\n"
            f"### **Conexión a base de datos** \n\n* \n\n"
            f"### **Tipo de Conector Bases de Datos:**\n* \n\n"
            f"### **Funciones:**\n\n{java_funcs_str}\n\n"
            f"### Elaborado Por:\n\nJosé Abel Carvajal \n\n"
            f"### **NOTA:**\n"
            f"Documentación generada el {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n"
        )

        output_file.write_text(md_content, encoding='utf-8')
        logging.info(f"Archivo generado: {output_file}")

    except Exception as e:
        logging.error(f"Error al generar MD: {str(e)}")
        traceback.print_exc()

def process_java_file(file_path: Path):
    """Procesa un archivo Java y genera documentación."""
    if not file_path.exists():
        logging.error(f"Error: Archivo no encontrado: {file_path}")
        return

    code = None
    try:
        with file_path.open(encoding='utf-8') as f:
            code = f.read()
    except (UnicodeDecodeError, FileNotFoundError) as e:
        try:
            with file_path.open(encoding='latin-1') as f:
                code = f.read()
        except (UnicodeDecodeError, FileNotFoundError) as e:
            logging.error(f"Error al leer el archivo {file_path}: {e}. Check file encoding or permissions.")
            return  # Exit early if file reading fails
    except Exception as e:
        logging.error(f"Error inesperado al leer el archivo {file_path}: {e}")
        return

    # Crucial Check: Ensure 'code' is a non-empty string BEFORE processing
    if isinstance(code, str) and code.strip():
        package = detect_package(code)
        libraries = parse_java_libraries(code)
        java_vars, java_funcs = [], []
        seen_vars = set()

        for var in parse_java_variables(code):
            if var['name'] not in seen_vars:
                java_vars.append(var)
                seen_vars.add(var['name'])
        for var in parse_local_variables(code):
            if var['name'] not in seen_vars:
                java_vars.append(var)
                seen_vars.add(var['name'])

        try:
            java_funcs.extend(parse_java_functions(code))  # Call parse_java_functions only if code is valid
        except Exception as e:
            logging.error(f"Error en parse_java_functions: {e}")
            traceback.print_exc()  # Añadido para mostrar el stack trace completo
            return #Return if parse_java_functions fails

        generate_md(file_path, {'variables': java_vars, 'functions': java_funcs, 'libraries': libraries}, package)
    else:
        logging.error("Error: Could not read file contents into a string or file is empty.")

# Procesar el archivo Java
test_file = Path("files/ObtenerValoresZona.java")
process_java_file(test_file)