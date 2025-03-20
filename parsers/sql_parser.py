"""
Módulo para análisis de código SQL incrustado en código fuente

Este módulo contiene funciones específicas para analizar y extraer información
de consultas SQL, como tablas, columnas y operaciones realizadas.
"""
import re
import logging
from pathlib import Path
from typing import Set, Dict, List, Any
from documentador.utils.db_config import db_config

class SQLParser:
    def __init__(self):
        self.logger = logging.getLogger(__name__)
        self.sql_keywords = {
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'FROM', 'WHERE', 'JOIN',
            'GROUP', 'ORDER', 'BY', 'HAVING', 'UNION', 'ALL', 'AS', 'ON',
            'AND', 'OR', 'IN', 'EXISTS', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END',
            'SUM', 'COUNT', 'AVG', 'MIN', 'MAX', 'CAST', 'COALESCE', 'NULL',
            'TOKEN', 'TABLE', 'VIEW', 'PROCEDURE', 'FUNCTION'
        }

    def _clean_query(self, query: str) -> str:
        """Limpia y normaliza una consulta SQL"""
        if not query:
            return ""
        
        # Eliminar comentarios
        query = re.sub(r'--.*$', '', query, flags=re.MULTILINE)
        query = re.sub(r'/\*.*?\*/', '', query, flags=re.DOTALL)
        
        # Eliminar contenido entre paréntesis
        query = re.sub(r'\([^)]*\)', '', query)
        
        # Eliminar cadenas literales
        query = re.sub(r"'[^']*'", '', query)
        query = re.sub(r'"[^"]*"', '', query)
        
        return query.strip()

    def extract_tables(self, query: str) -> Set[str]:
        """Extrae nombres de tablas de una consulta SQL."""
        tables = set()
        if not query or not isinstance(query, str):
            return tables
        
        # Patrones para tablas
        table_patterns = [
            r'(?:FROM|JOIN)\s+(?:(?:[A-Za-z0-9_]+\.)?([A-Za-z][A-Za-z0-9_]*(?:_[A-Za-z0-9_]+)*))',
            r'(?:UPDATE|INSERT\s+INTO|DELETE\s+FROM)\s+(?:(?:[A-Za-z0-9_]+\.)?([A-Za-z][A-Za-z0-9_]*(?:_[A-Za-z0-9_]+)*))',
            r'\(\s*SELECT\s+.*?FROM\s+(?:(?:[A-Za-z0-9_]+\.)?([A-Za-z][A-Za-z0-9_]*(?:_[A-Za-z0-9_]+)*))',
            r'USING\s+(?:(?:[A-Za-z0-9_]+\.)?([A-Za-z][A-Za-z0-9_]*(?:_[A-Za-z0-9_]+)*))'
        ]
        
        try:
            # Procesar cada patrón
            for pattern in table_patterns:
                matches = re.finditer(pattern, query, re.IGNORECASE | re.MULTILINE | re.DOTALL)
                for match in matches:
                    table_name = match.group(1).strip('`"\' ')
                    
                    # Validación básica
                    if self._is_valid_table_name(table_name):
                        tables.add(table_name.lower())
                        
        except Exception as e:
            self.logger.error(f"Error extrayendo tablas: {str(e)}")
        
        return tables

    def extract_db_connections(self, code: str) -> Dict[str, Set[str]]:
        """Extrae información de conexiones a bases de datos."""
        connections = {
            'conectores': set(),
            'databases': set()  # Cambiado de 'bases_datos' a 'databases' para consistencia
        }
        
        # Buscar patrones de conexión a BD directamente sin usar parse_sql_queries
        connection_patterns = [
            r'cnn->query\s*\(\s*[\'\"](\d+)[\'\"](.*?)\)',
        ]
        
        for pattern in connection_patterns:
            matches = re.finditer(pattern, code, re.DOTALL)
            for match in matches:
                id_conexion = match.group(1)
                connections['conectores'].add(f"cnn->query('{id_conexion}')")
                
                # Usar DBConfig para obtener el nombre de la base de datos
                if db_name := db_config.get_db_name(id_conexion):
                    connections['databases'].add(db_name)
        
        return connections

    def get_db_aliases(self) -> Dict[str, str]:
        """Lee las conexiones definidas en el archivo de configuración."""
        try:
            conexiones_path = Path(__file__).parent.parent / 'plantillas' / 'conexiones_symfony.md'
            if not conexiones_path.exists():
                self.logger.warning(f"Archivo de conexiones no encontrado: {conexiones_path}")
                return {}
                
            content = conexiones_path.read_text(encoding='utf-8')
            
            # Patrón para extraer alias y nombres
            pattern = r'`([^`]+)`\s*->\s*([^\n]+)'
            
            return {
                match.group(1): match.group(2).strip()
                for match in re.finditer(pattern, content)
            }
            
        except Exception as e:
            self.logger.error(f"Error leyendo archivo de conexiones: {e}")
            return {}
    
    def parse_sql_queries(self, code: str) -> Dict[str, Any]:
        """Detecta y analiza todas las consultas SQL en el código."""
        queries = []
        
        # Asegurarnos que code sea string
        if not isinstance(code, str):
            self.logger.warning("Code no es string, convirtiendo...")
            code = str(code)
        
        # Patrones para detectar SQL
        sql_patterns = [
            # Variables SQL
            r'\$(?:sql|sq|sqmant|sq_control|sq_hist)[0-9_]*\s*=\s*[\'"](.*?)[\'"]',
            r'\$sql[a-z0-9_]*\s*\.=\s*[\'"](.*?)[\'"]',
            
            # Métodos de base de datos
            r'->(?:query|execute|Execute|prepare)\s*\(\s*(?:[\'"]?\d+[\'"]?\s*,\s*)?[\'"](.*?)[\'"]',
            
            # Consultas multilínea
            r'/\*.*?SELECT\s+.*?FROM.*?\*/',
            r'//.*?SELECT\s+.*?FROM.*?$'
        ]

        try:
            # Pre-procesar código
            from documentador.utils.text import clean_whitespace, remove_comments
            code = clean_whitespace(code)
            
            # Procesar cada patrón
            for pattern in sql_patterns:
                try:
                    matches = re.finditer(pattern, code, re.IGNORECASE | re.MULTILINE | re.DOTALL)
                    for match in matches:
                        # Obtener la consulta
                        query_text = match.group(1) if match.groups() else match.group(0)
                        query_text = query_text.strip()
                        
                        # Verificar si es una consulta SQL válida
                        if re.search(r'\b(SELECT|INSERT|UPDATE|DELETE|FROM|JOIN)\b', query_text, re.IGNORECASE):
                            # Extraer tablas directamente
                            tables = self.extract_tables(query_text)
                            
                            queries.append({
                                'query': query_text,
                                'tables': list(tables)
                            })
                except Exception as e:
                    self.logger.error(f"Error procesando patrón {pattern}: {str(e)}")
                    continue
                    
        except Exception as e:
            self.logger.error(f"Error en parse_sql_queries: {str(e)}")
            return {'queries': []}

        return {'queries': queries}

    def _is_valid_table_name(self, name: str) -> bool:
        """Valida si un nombre es una tabla válida."""
        non_tables = {
            'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'NULL', 'AS', 'IN', 
            'EXISTS', 'VALUES', 'TABLE', 'TEMP', 'TMP'
        }
        return (
            name.upper() not in non_tables and
            len(name) > 2 and
            not name.isdigit() and
            not name.startswith('(')
        )

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

    def get_db_name(self, connection_id: str) -> str:
        """
        Obtiene el nombre de la base de datos para un ID de conexión.
        
        Args:
            connection_id (str): ID de la conexión
            
        Returns:
            str: Nombre de la base de datos o cadena vacía si no se encuentra
        """
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
