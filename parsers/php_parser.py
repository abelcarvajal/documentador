import re
import logging
from pathlib import Path
from typing import Dict, List, Any, Set, Tuple
from .language_detector import LanguageDetector
from .js_parser import JSParser
from .variable_parser import VariableParser

logger = logging.getLogger(__name__)

class PHPParser:
    def __init__(self, file_path: Path):
        self.file_path = file_path
        self.content = None
        self.raw_content = None  # Contenido original sin modificar
        self.services = set()
        self.js_libraries = set()

    def _remove_comments(self, content: str) -> str:
        """
        Elimina comentarios PHP y JavaScript manteniendo el contenido original 
        para referencia
        """
        # Guardar contenido original
        self.raw_content = content

        # Patrón para comentarios de una línea
        content = re.sub(r'(?<!:)//.*$', '', content, flags=re.MULTILINE)
        
        # Patrón para comentarios de una línea con #
        content = re.sub(r'#.*$', '', content, flags=re.MULTILINE)
        
        # Patrón para comentarios multilinea PHP y JavaScript
        content = re.sub(r'/\*[\s\S]*?\*/', '', content)
        
        # Patrón para comentarios de documentación PHP
        content = re.sub(r'\/\*\*[\s\S]*?\*\/', '', content)
        
        logger.debug("Comentarios eliminados del código")
        return content

    def _load_file(self) -> bool:
        """Carga y preprocesa el contenido del archivo"""
        try:
            raw_content = self.file_path.read_text(encoding='utf-8')
            self.content = self._remove_comments(raw_content)
            return True
        except UnicodeDecodeError:
            # Intentar con otras codificaciones
            encodings = ['latin-1', 'iso-8859-1', 'cp1252']
            for encoding in encodings:
                try:
                    raw_content = self.file_path.read_text(encoding=encoding)
                    self.content = self._remove_comments(raw_content)
                    return True
                except UnicodeDecodeError:
                    continue
            logger.error(f"No se pudo leer el archivo {self.file_path}")
            return False

    def _extract_commented_code(self) -> List[Dict[str, str]]:
        """
        Extrae código comentado para documentación (opcional)
        """
        commented_code = []
        patterns = [
            (r'\/\*\*(.*?)\*\/', 'PHPDoc'),
            (r'(?<!:)//(.+)$', 'Single line'),
            (r'/\*(.*?)\*/', 'Multi line')
        ]
        
        for pattern, comment_type in patterns:
            matches = re.finditer(pattern, self.raw_content, re.MULTILINE | re.DOTALL)
            for match in matches:
                comment = match.group(1).strip()
                if comment:
                    commented_code.append({
                        'type': comment_type,
                        'content': comment
                    })
                    
        return commented_code

    def _extract_libraries(self) -> List[str]:
        """Extrae las librerías del código PHP"""
        libraries = set()
        
        # Patrones para detectar librerías
        patterns = [
            r'use\s+([^;]+);',  # use statements
            r'require(?:_once)?\s*\(?\s*[\'"]([^\'"]+)[\'"]',  # require/require_once
            r'include(?:_once)?\s*\(?\s*[\'"]([^\'"]+)[\'"]'   # include/include_once
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            for match in matches:
                lib = match.group(1).strip()
                # Excluir los servicios de las librerías
                if lib and not any(service in lib for service in self.services):
                    libraries.add(lib)
                    logger.debug(f"Librería encontrada: {lib}")
        
        return sorted(list(libraries))

    def _extract_services(self) -> List[str]:
        """Extrae los servicios inyectados en el controlador"""
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
        constructor_match = re.search(patterns[0], self.content, re.DOTALL)
        if constructor_match:
            params = constructor_match.group(1)
            # Buscar tipos de servicios en los parámetros
            service_matches = re.finditer(r'(\w+)\s+\$\w+', params)
            for match in service_matches:
                service_type = match.group(1)
                if not service_type.startswith(('int', 'string', 'bool', 'array', 'float')):
                    self.services.add(service_type)
                    logger.debug(f"Servicio encontrado en constructor: {service_type}")
        
        return sorted(list(self.services))

    def _extract_js_blocks(self) -> List[str]:
        """Extrae bloques de código JavaScript"""
        js_code = []
        
        # Patrón para tags script
        script_pattern = r'<script[^>]*>(.*?)</script>'
        script_blocks = re.finditer(script_pattern, self.content, re.DOTALL)
        
        for block in script_blocks:
            code = block.group(1).strip()
            if code:
                js_code.append(code)
                logger.debug(f"Bloque JS encontrado en script tag: {code[:50]}...")

        # Patrón para jQuery o JavaScript inline
        jquery_patterns = [
            r'\$\(document\)\.ready\(\s*function\s*\(\)\s*\{(.*?)\}\s*\)',
            r'\$\(function\s*\(\)\s*\{(.*?)\}\s*\)',
            r'document\.addEventListener\([\'"]DOMContentLoaded[\'"],\s*function\s*\(\)\s*\{(.*?)\}\s*\)'
        ]
        
        for pattern in jquery_patterns:
            matches = re.finditer(pattern, self.content, re.DOTALL)
            for match in matches:
                code = match.group(1).strip()
                if code:
                    js_code.append(code)
                    logger.debug(f"Bloque JS encontrado en código inline: {code[:50]}...")
        
        self.js_blocks = js_code
        return js_code

    def _extract_js_libraries(self) -> List[str]:
        """Extrae librerías JavaScript del código"""
        # Patrones para librerías JS
        patterns = [
            # Script tags con src - Simplificado y más preciso
            r'<script\s+[^>]*?src=[\'"](.*?)[\'"].*?>',
            # ES6 imports
            r'import\s*{\s*([^}]+)\s*}\s*from\s*[\'"]([^\'"]+)[\'"]',
            # require
            r'require\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)',
            # import dinámico
            r'import\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)'
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            for match in matches:
                # Para script tags, el grupo 1 contiene el src
                lib = match.group(1).strip()
                if lib:
                    self.js_libraries.add(lib)
                    logger.debug(f"Librería JS encontrada: {lib}")
    
        return sorted(list(self.js_libraries))

    def parse(self) -> Dict[str, Any]:
        """Realiza el parsing del archivo PHP"""
        if not self._load_file():
            return {}
            
        # Detectar lenguajes
        detector = LanguageDetector(self.content)
        languages = detector.detect_languages()
        
        # Parsear variables
        variable_parser = VariableParser(self.content)
        php_variables = variable_parser.parse_php_variables()
        js_variables = variable_parser.parse_js_variables() if 'JavaScript' in languages else {}
        
        # Extraer información
        services = self._extract_services()
        libraries = self._extract_libraries()
        
        # Analizar JavaScript si está presente
        js_data = {}
        if 'JavaScript' in languages:
            js_parser = JSParser(self.content)
            js_data = js_parser.analyze()
        
        return {
            'file_name': self.file_path.name,
            'file_path': str(self.file_path),  # Ruta completa del archivo
            'languages': sorted(list(languages)),
            'libraries': libraries,
            'services': services,
            'php_variables': php_variables,
            'js_variables': js_variables,
            **js_data
        }