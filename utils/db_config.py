import re
import logging
from pathlib import Path
from typing import Dict

logger = logging.getLogger(__name__)

class DBConfig:
    """Clase para manejar la configuración de bases de datos"""
    
    def __init__(self):
        self.conexiones_path = Path(__file__).parent.parent / 'plantillas' / 'conexiones_symfony.md'
        self._aliases = None

    def get_db_aliases(self) -> Dict[str, str]:
        """Lee las conexiones definidas en el archivo."""
        if self._aliases is not None:
            return self._aliases
            
        try:
            if not self.conexiones_path.exists():
                logger.warning(f"Archivo de conexiones no encontrado: {self.conexiones_path}")
                return {}
                
            content = self.conexiones_path.read_text(encoding='utf-8')
            
            # Patrón para extraer ID y nombre
            pattern = r'\*\s+(\d+):\s+`([^`]+)`'
            
            self._aliases = {
                match.group(2): match.group(1)
                for match in re.finditer(pattern, content)
            }
            
            return self._aliases
            
        except Exception as e:
            logger.error(f"Error leyendo archivo de conexiones: {e}")
            return {}

    def get_db_name(self, id_conexion: str) -> str:
        """Obtiene el nombre de la base de datos por su ID."""
        aliases = self.get_db_aliases()
        for name, db_id in aliases.items():
            if db_id == id_conexion:
                return name
        return None

# Instancia global para uso en toda la aplicación
db_config = DBConfig()