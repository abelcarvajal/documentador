"""
Módulo para análisis de código SQL incrustado en código fuente

Este módulo contiene funciones específicas para analizar y extraer información
de consultas SQL, como tablas, columnas y operaciones realizadas.
"""
import re
import logging

def extract_tables_from_query(query):
    """
    Extrae nombres de tablas de una consulta SQL
    
    Analiza consultas SQL complejas y extrae los nombres de todas las tablas 
    referenciadas usando patrones de expresiones regulares.
    
    Args:
        query (str): Consulta SQL a analizar
        
    Returns:
        set: Conjunto de nombres de tablas encontradas
    """
    if not query or not isinstance(query, str):
        return set()
    
    tables = set()
    
    # Normalizar espacios en blanco y convertir a mayúsculas para análisis consistente
    query = re.sub(r'\s+', ' ', query.upper())
    
    # Eliminar subconsultas temporalmente para evitar confusiones
    # Guardamos las subconsultas para procesarlas después
    subconsultas = []
    def reemplazar_subconsulta(match):
        subconsultas.append(match.group(1))
        return f" __SUBCONSULTA_{len(subconsultas)-1}__ "
    
    # Reemplazar subconsultas
    query_sin_subconsultas = re.sub(r'\(\s*SELECT\s+.*?\)', lambda m: reemplazar_subconsulta(m), query, flags=re.DOTALL|re.IGNORECASE)
    
    # Palabras clave SQL que preceden a nombres de tablas
    table_keywords = [
        r'FROM\s+([A-Z0-9_$]+)',
        r'JOIN\s+([A-Z0-9_$]+)',
        r'INTO\s+([A-Z0-9_$]+)',
        r'UPDATE\s+([A-Z0-9_$]+)',
        r'TABLE\s+([A-Z0-9_$]+)',
        r'TRUNCATE\s+TABLE\s+([A-Z0-9_$]+)'
    ]
    
    # Palabras reservadas SQL que no deben ser consideradas nombres de tabla
    sql_keywords = {
        'ON', 'WHERE', 'AND', 'OR', 'AS', 'IN', 'SELECT', 'INSERT', 'UPDATE', 
        'DELETE', 'FROM', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'OUTER', 'CROSS',
        'GROUP', 'ORDER', 'BY', 'HAVING', 'LIMIT', 'OFFSET', 'UNION', 'ALL',
        'NULL', 'IS', 'NOT', 'DISTINCT', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END',
        'ASC', 'DESC'
    }
    
    # Buscar tablas usando los patrones definidos
    for pattern in table_keywords:
        matches = re.finditer(pattern, query_sin_subconsultas)
        for match in matches:
            table_name = match.group(1).strip()
            # Eliminar alias si existe
            if ' ' in table_name:
                table_name = table_name.split(' ')[0]
            
            # Eliminar comillas si existen
            table_name = table_name.strip('"\'')
            
            # Eliminar prefijo de esquema si existe
            if '.' in table_name:
                table_name = table_name.split('.')[-1]
                
            # Verificar que no sea una palabra reservada ni un alias
            if (table_name not in sql_keywords and 
                not table_name.startswith('__SUBCONSULTA_') and
                len(table_name) > 2):  # Prevenir aliases muy cortos
                
                tables.add(table_name)
                logging.debug(f"Tabla SQL encontrada: {table_name}")
    
    # Procesar subconsultas recursivamente
    for i, subconsulta in enumerate(subconsultas):
        subtables = extract_tables_from_query(subconsulta)
        tables.update(subtables)
        
    return tables

def parse_sql_queries(code):
    """
    Detecta y analiza todas las consultas SQL en el código.
    
    Busca asignaciones a variables que parezcan consultas SQL,
    así como llamadas a funciones de base de datos.
    
    Args:
        code (str): Código fuente a analizar
        
    Returns:
        list: Lista de diccionarios con información de consultas SQL
    """
    queries = []
    
    # Patrones para encontrar consultas SQL
    sql_patterns = [
        # Asignaciones a variables $sql
        r'\$sql(?:\w*)\s*=\s*[\'"]([^\'"]+)[\'"]',
        
        # Asignaciones a variables $query
        r'\$query(?:\w*)\s*=\s*[\'"]([^\'"]+)[\'"]',
        
        # Llamadas a query() con consulta SQL directa
        r'->query\s*\(\s*[^,]*,\s*[\'"]([^\'"]+)[\'"]',
        
        # Llamadas a execute() con consulta SQL
        r'->execute\s*\(\s*[\'"]([^\'"]+)[\'"]',
        
        # Llamadas a prepare() con consulta SQL
        r'->prepare\s*\(\s*[\'"]([^\'"]+)[\'"]',
    ]
    
    for pattern in sql_patterns:
        matches = re.finditer(pattern, code, re.DOTALL)
        for match in matches:
            query_text = match.group(1).strip()
            
            # Solo procesar si parece una consulta SQL válida
            if any(keyword in query_text.upper() for keyword in 
                  ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER']):
                
                # Intentar determinar el tipo de operación
                operation_type = 'unknown'
                for op in ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER']:
                    if query_text.upper().startswith(op):
                        operation_type = op.lower()
                        break
                
                # Extraer tablas
                tables = extract_tables_from_query(query_text)
                
                query_info = {
                    'text': query_text[:100] + ('...' if len(query_text) > 100 else ''),
                    'operation': operation_type,
                    'tables': list(tables),
                    'context': get_query_context(code, match.start())
                }
                
                queries.append(query_info)
                logging.debug(f"SQL query found ({operation_type}): {query_info['text']}")
    
    return queries

def get_query_context(code, position, context_lines=1):
    """
    Obtiene el contexto de una consulta SQL (función o método donde se encuentra)
    
    Args:
        code (str): Código fuente completo
        position (int): Posición de la consulta en el código
        context_lines (int): Número de líneas de contexto a obtener
        
    Returns:
        str: Nombre de la función o contexto donde se encuentra la consulta
    """
    # Buscar hacia atrás para encontrar la función actual
    code_before = code[:position]
    
    # Buscar la última definición de función antes de la posición
    func_matches = list(re.finditer(r'function\s+(\w+)', code_before))
    if func_matches:
        last_func = func_matches[-1]
        return last_func.group(1)
    
    # Si no se encuentra función, devolver contexto cercano
    lines = code_before.split('\n')
    if lines:
        start = max(0, len(lines) - context_lines)
        context = '\n'.join(lines[start:]).strip()
        return context[:50] + '...' if len(context) > 50 else context
    
    return "unknown context"

def analyze_sql_changes(old_content, new_content):
    """
    Analiza cambios en las consultas SQL entre dos versiones de código
    
    Args:
        old_content (str): Contenido antiguo del archivo
        new_content (str): Contenido nuevo del archivo
        
    Returns:
        dict: Diccionario con cambios detectados en las consultas SQL
    """
    # Obtener consultas en ambas versiones
    old_queries = parse_sql_queries(old_content)
    new_queries = parse_sql_queries(new_content)
    
    # Obtener tablas en ambas versiones
    old_tables = set()
    for query in old_queries:
        old_tables.update(query['tables'])
    
    new_tables = set()
    for query in new_queries:
        new_tables.update(query['tables'])
    
    # Detectar cambios en tablas
    added_tables = new_tables - old_tables
    removed_tables = old_tables - new_tables
    common_tables = old_tables.intersection(new_tables)
    
    # Detectar cambios en consultas por tipo de operación
    old_ops = {query['operation']: query['text'] for query in old_queries}
    new_ops = {query['operation']: query['text'] for query in new_queries}
    
    added_ops = set(new_ops.keys()) - set(old_ops.keys())
    removed_ops = set(old_ops.keys()) - set(new_ops.keys())
    
    # Contar cambios por tipo
    changes = {
        'tables': {
            'added': list(added_tables),
            'removed': list(removed_tables),
            'common': list(common_tables)
        },
        'operations': {
            'added': list(added_ops),
            'removed': list(removed_ops)
        },
        'total_queries': {
            'old': len(old_queries),
            'new': len(new_queries),
            'diff': len(new_queries) - len(old_queries)
        }
    }
    
    return changes
