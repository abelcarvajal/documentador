from abc import ABC, abstractmethod
from typing import Dict, Set, Any

class BaseParser(ABC):
    """Clase base para todos los parsers"""
    
    @abstractmethod
    def parse(self, content: str) -> Dict[str, Any]:
        """Parsea el contenido y retorna los resultados"""
        pass
    
    @abstractmethod
    def clean(self, content: str) -> str:
        """Limpia el contenido antes de parsearlo"""
        pass