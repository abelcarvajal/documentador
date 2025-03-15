import re
import logging
from typing import Dict, Set, List

logger = logging.getLogger(__name__)

class VariableParser:
    def __init__(self, content: str):
        self.content = content
        self.php_variables = {'globales': set(), 'locales': set()}
        self.js_variables = {'globales': set(), 'locales': set()}

    def _should_exclude_variable(self, var_name: str) -> bool:
        """Determina si una variable debe ser excluida"""
        exclude_patterns = [
            r'^\d+$',  # Variables numéricas
            r'^(key|value|row|link|item)$',  # Variables comunes de iteración
            r'^(GET|POST|REQUEST|SESSION|COOKIE|SERVER|ENV|FILES|GLOBALS)$',  # Superglobales
            r'^(this|self)$',  # Referencias a la clase
            r'^(_|__).*'  # Variables que comienzan con guión bajo
        ]
        return any(re.match(pattern, var_name) for pattern in exclude_patterns)

    def parse_php_variables(self) -> Dict[str, Set[str]]:
        """Extrae variables PHP"""
        # Variables globales (propiedades de clase)
        class_patterns = [
            r'private\s+\$(\w+)\s*(?:=\s*[^;]+)?;',
            r'protected\s+\$(\w+)\s*(?:=\s*[^;]+)?;',
            r'public\s+\$(\w+)\s*(?:=\s*[^;]+)?;',
            r'global\s+\$(\w+)',
            r'\$this->(\w+)'
        ]

        for pattern in class_patterns:
            matches = re.finditer(pattern, self.content)
            for match in matches:
                var_name = match.group(1)
                if not self._should_exclude_variable(var_name):
                    self.php_variables['globales'].add(var_name)
                    logger.debug(f"Variable global PHP encontrada: {var_name}")

        # Variables locales
        local_patterns = [
            r'(?<!->)\$(\w+)\s*=',  # Asignaciones
            r'foreach\s*\(\s*\$(\w+)\s+as',  # Variables de foreach
            r'function\s+\w+\s*\([^)]*\$(\w+)[^)]*\)'  # Parámetros de función
        ]

        for pattern in local_patterns:
            matches = re.finditer(pattern, self.content)
            for match in matches:
                var_name = match.group(1)
                if (not self._should_exclude_variable(var_name) and 
                    var_name not in self.php_variables['globales']):
                    self.php_variables['locales'].add(var_name)
                    logger.debug(f"Variable local PHP encontrada: {var_name}")

        return self.php_variables

    def parse_js_variables(self) -> Dict[str, Set[str]]:
        """Extrae variables JavaScript"""
        # Variables globales JS
        global_patterns = [
            r'(?:window|global)\.(\w+)\s*=',
            r'var\s+(\w+)\s*=',
            r'this\.(\w+)\s*='
        ]

        for pattern in global_patterns:
            matches = re.finditer(pattern, self.content)
            for match in matches:
                var_name = match.group(1)
                if not self._should_exclude_variable(var_name):
                    self.js_variables['globales'].add(var_name)
                    logger.debug(f"Variable global JS encontrada: {var_name}")

        # Variables locales JS
        local_patterns = [
            r'(?:let|const)\s+(\w+)\s*=',
            r'function\s*\((\w+)\)',
            r'\.forEach\(\s*(\w+)\s*=>'
        ]

        for pattern in local_patterns:
            matches = re.finditer(pattern, self.content)
            for match in matches:
                var_name = match.group(1)
                if (not self._should_exclude_variable(var_name) and 
                    var_name not in self.js_variables['globales']):
                    self.js_variables['locales'].add(var_name)
                    logger.debug(f"Variable local JS encontrada: {var_name}")

        return self.js_variables