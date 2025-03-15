import re
import logging
from typing import Set

logger = logging.getLogger(__name__)

class LanguageDetector:
    """Detector de lenguajes de programación en el código fuente"""
    
    def __init__(self, content: str):
        self.content = content
        self.languages = set()

    def detect_languages(self) -> Set[str]:
        """
        Detecta los lenguajes de programación utilizados
        """
        # Detectar PHP
        if re.search(r'<\?php', self.content) or re.search(r'namespace\s+[\w\\]+;', self.content):
            self.languages.add('PHP')
            logger.debug("PHP detectado")

        # Detectar JavaScript
        if any(pattern in self.content for pattern in [
            '<script',
            '$(document)',
            'document.addEventListener',
            'import ',
            'require('
        ]):
            self.languages.add('JavaScript')
            logger.debug("JavaScript detectado")

        return self.languages