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
        # Remover comentarios
        query = re.sub(r'--.*$', '', query, flags=re.MULTILINE)
        query = re.sub(r'/\*.*?\*/', '', query, flags=re.DOTALL)
        
        # Remover funciones de agregación
        query = re.sub(r'(SUM|COUNT|AVG|MIN|MAX)\s*\([^)]+\)', '', query)
        
        # Remover CASE statements
        query = re.sub(r'CASE\s+WHEN.*?END', '', query, flags=re.DOTALL)
        
        return query.upper()

    def extract_tables(self, query: str) -> Set[str]:
        """Extrae nombres de tablas de una consulta SQL"""
        tables = set()
        if not query:
            return tables
            
        # Lista de alias comunes o nombres cortos que deben ser ignorados
        alias_blacklist = {
            'c3', 't1', 't2', 't3', 'a', 'b', 'c', 'd', 'tmp', 'temp',
            'aux', 'det', 'cab', 'src', 'dst', 'old', 'new'
        }
        
        # Limpiar la consulta primero
        query = self._clean_query(query)
        
        # Patrones mejorados para tablas
        table_patterns = [
            # Patrón básico FROM/JOIN/INTO/UPDATE
            r'(?:FROM|JOIN|UPDATE|INTO)\s+([A-Za-z0-9_\.]+)',
            
            # Patrón con alias opcional
            r'(?:FROM|JOIN|UPDATE|INTO)\s+([A-Za-z0-9_\.]+)(?:\s+(?:AS\s+)?\w+)?',
            
            # Patrón para subconsultas
            r'FROM\s+\((SELECT.*?FROM\s+([A-Za-z0-9_\.]+))\s*\)',
            
            # Patrón para INNER/LEFT/RIGHT JOIN
            r'(?:INNER|LEFT|RIGHT|OUTER)?\s*JOIN\s+([A-Za-z0-9_\.]+)',
            
            # Patrón para INSERT INTO
            r'INSERT\s+INTO\s+([A-Za-z0-9_\.]+)',
            
            # Patrón para DELETE FROM
            r'DELETE\s+FROM\s+([A-Za-z0-9_\.]+)',
            
            # Patrón para capturas de tablas en WHERE
            r'WHERE\s+([A-Za-z0-9_\.]+)\.', 
            
            # Patrón para tablas en subconsultas
            r'\(\s*SELECT\s+.*?\s+FROM\s+([A-Za-z0-9_\.]+)'
        ]

        for pattern in table_patterns:
            matches = re.finditer(pattern, query, re.IGNORECASE | re.DOTALL)
            for match in matches:
                table_name = match.group(1).strip('`"\' ')
                
                # Manejar nombres con esquema
                if '.' in table_name:
                    schema, table = table_name.split('.')
                    table_name = table
                    
                if (
                    table_name.upper() not in self.sql_keywords and
                    table_name.lower() not in alias_blacklist and  # Nueva validación
                    len(table_name) > 2 and  # Aumentado el mínimo de caracteres
                    not table_name.isdigit() and
                    not table_name.startswith('(') and
                    not re.match(r'^[a-z][0-9]$', table_name.lower())  # Evitar patrones como c3
                ):
                    tables.add(table_name.lower())
                    
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
        all_tables = set()
        
        # Patrones mejorados para encontrar asignaciones SQL
        sql_patterns = [
            # Patrones para variables SQL en PHP
            r'\$sql[0-9]*\s*=\s*[\'"]([^\'"]+)[\'"]',
            r'\$sq[0-9]*\s*=\s*[\'"]([^\'"]+)[\'"]',
            r'\$sqmant\s*=\s*[\'"]([^\'"]+)[\'"]',
            r'\$sq_control[_]*\s*=\s*[\'"]([^\'"]+)[\'"]',
            r'\$sq_hist\s*=\s*[\'"]([^\'"]+)[\'"]',
            r'\$sql_[a-z0-9_]*\s*=\s*[\'"]([^\'"]+)[\'"]',
            
            # Patrones para métodos de base de datos mejorados
            r'->query\s*\(\s*[\'"]?[^,]*[\'"]?\s*,\s*[\'"]([^\'"]+)[\'"]',
            r'->execute\s*\(\s*[\'"]([^\'"]+)[\'"]',
            r'->Execute\s*\(\s*[\'"]([^\'"]+)[\'"]',
            r'->prepare\s*\(\s*[\'"]([^\'"]+)[\'"]',
            
            # Patrones mejorados para consultas directas
            r'SELECT\s+.*?\s+FROM\s+([^\s;]+)',
            r'INSERT\s+INTO\s+([^\s;]+)',
            r'UPDATE\s+([^\s;]+)',
            r'DELETE\s+FROM\s+([^\s;]+)',
            r'(?:INNER|LEFT|RIGHT|OUTER)?\s*JOIN\s+([^\s;]+)',
            
            # Nuevos patrones para capturar más variantes
            r'\$sq_[a-z0-9_]*\s*=\s*[\'"]([^\'"]+)[\'"]',
            r'\$sql[a-z0-9_]*\s*\.=\s*[\'"]([^\'"]+)[\'"]',
            r'->query\s*\(\s*[\'"]([^\'"]+)[\'"]',
            r'->execute\s*\(\s*[\'"]([^\'"]+)[\'"]',
            
            # Patrones para subconsultas
            r'\(\s*SELECT\s+.*?\s+FROM\s+([^\s;]+)\s*\)',
            r'JOIN\s+\(\s*SELECT\s+.*?\s+FROM\s+([^\s;]+)\s*\)',
            
            # Patrones para UNION
            r'UNION\s+(?:ALL\s+)?SELECT\s+.*?\s+FROM\s+([^\s;]+)',
            
            # Patrones para WITH
            r'WITH\s+\w+\s+AS\s*\(\s*SELECT\s+.*?\s+FROM\s+([^\s;]+)\s*\)',
        ]
        
        sql_patterns.extend([
            # Patrones para consultas con alias y comentarios
            r'FROM\s+([A-Za-z0-9_\.]+)\s*(?:AS\s+[A-Za-z0-9_]+)?(?:\s*--.*)?$',
            
            # Patrones para consultas con schema
            r'FROM\s+[A-Za-z0-9_]+\.([A-Za-z0-9_]+)',
            
            # Patrones para variables PHP concatenadas
            r'\$sql\s*\.=\s*[\'"]FROM\s+([A-Za-z0-9_\.]+)[\'"]',
            
            # Patrones para consultas dentro de funciones
            r'function\s+\w+\s*\([^)]*\)\s*{[^}]*FROM\s+([A-Za-z0-9_\.]+)',
            
            # Patrones para consultas en comentarios
            r'/\*.*?FROM\s+([A-Za-z0-9_\.]+).*?\*/',
            
            # Patrones para consultas con USING
            r'USING\s+([A-Za-z0-9_\.]+)',
            
            # Patrones para consultas con INTO
            r'INTO\s+([A-Za-z0-9_\.]+)\s+',
        ])
        
        # Pre-procesar el código para manejar concatenaciones
        code = re.sub(r'\'\s*\.\s*\$[^\']*\s*\.\s*\'', ' ', code)
        code = re.sub(r'\s+', ' ', code)
        
        for pattern in sql_patterns:
            matches = re.finditer(pattern, code, re.IGNORECASE | re.MULTILINE | re.DOTALL)
            for match in matches:
                query_text = match.group(1).strip()
                
                # Buscar palabras clave SQL con validación mejorada
                if re.search(r'\b(SELECT|INSERT|UPDATE|DELETE|FROM|JOIN|UNION|WITH)\b', query_text, re.IGNORECASE):
                    tables = self.extract_tables(query_text)
                    if tables:
                        all_tables.update(tables)
                        queries.append({
                            'query': query_text,
                            'tables': list(tables)
                        })

        return {
            'queries': queries, 
            'tables': sorted(list(all_tables))
        }

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
