"""
Módulo para análisis de código JavaScript

Este módulo contiene funciones específicas para analizar y extraer información
de bloques de JavaScript, como variables, funciones, eventos DOM, y llamadas a APIs.
"""
import re
import logging
from typing import List, Set, Dict, Any
from documentador.utils.text import remove_comments

logger = logging.getLogger(__name__)

class JSParser:
    def __init__(self, content: str):
        self.content = content
        self.js_blocks = []
        self.libraries = set()

    def extract_blocks(self) -> List[str]:
        """Extrae bloques de código JavaScript"""
        js_code = []
        
        # Patrón para tags script
        script_pattern = r'<script[^>]*>(.*?)</script>'
        script_blocks = re.finditer(script_pattern, self.content, re.DOTALL)
        
        for block in script_blocks:
            code = block.group(1).strip()
            if code:
                js_code.append(code)
                logger.debug(f"Bloque JS encontrado: {code[:50]}...")
                
        self.js_blocks = js_code
        return js_code

    def extract_libraries(self) -> List[str]:
        """Extrae referencias a librerías JavaScript"""
        patterns = [
            r'<script\s+[^>]*?src=[\'"](.*?)[\'"].*?>',
            r'import\s*{\s*([^}]+)\s*}\s*from\s*[\'"]([^\'"]+)[\'"]',
            r'require\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)'
        ]
        
        for pattern in patterns:
            matches = re.finditer(pattern, self.content)
            for match in matches:
                lib = match.group(1).strip()
                if lib:
                    self.libraries.add(lib)
                    logger.debug(f"Librería JS encontrada: {lib}")
        
        return sorted(list(self.libraries))

    def analyze(self) -> Dict[str, Any]:
        """Analiza el contenido JavaScript completo"""
        blocks = self.extract_blocks()
        libraries = self.extract_libraries()
        
        return {
            'has_js': bool(blocks or libraries),
            'js_libraries': libraries,
            'js_blocks': blocks
        }
