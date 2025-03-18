from pathlib import Path

# Directorio base del proyecto
BASE_DIR = Path(__file__).parent.parent

# Configuración global
CONFIG = {
    'paths': {
        'output': BASE_DIR / "docs" / "generated",
        'templates': BASE_DIR / "templates",
        'logs': BASE_DIR / "logs",
        'connectors': BASE_DIR / "plantillas" / "conexiones_symfony.md"
    },
    'parsers': {
        'supported_languages': ['php', 'javascript', 'sql'],
        'file_patterns': {
            'sql': r'(?:SELECT|INSERT|UPDATE|DELETE)\s+.+?(?:FROM|INTO|TABLE)\s+(\w+)',
            'js': r'<script\b[^>]*>(.*?)</script>'
        },
        'encoding': ['utf-8', 'latin-1', 'iso-8859-1', 'cp1252']
    }
}

class Config:
    def __init__(self):
        self.BASE_DIR = BASE_DIR
        self.CONFIG = CONFIG

    def get(self, key: str, default=None):
        """Obtiene un valor de configuración"""
        try:
            current = self.CONFIG
            for part in key.split('.'):
                current = current[part]
            return current
        except (KeyError, TypeError):
            return default

# Instancia única de configuración
config = Config()

# Exportar variables para compatibilidad
OUTPUT_DIR = config.CONFIG['paths']['output']
TEMPLATES_DIR = config.CONFIG['paths']['templates']
LOG_DIR = config.CONFIG['paths']['logs']

# Rutas relativas para documentación
SRC_CONTROLLER_PATH = "../../src/Controller"
DOC_PATH = "../../public/guia_programador"

# Asegurar que los directorios existan
config.get('paths.output').mkdir(parents=True, exist_ok=True)
config.get('paths.logs').mkdir(parents=True, exist_ok=True)

# Configuración de logging
LOG_LEVEL = "INFO"
LOG_FORMAT = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
LOG_FILE = config.BASE_DIR / "logs/documentador.log"

# Configuración de parseo
MAX_FILE_SIZE_MB = 10
SUPPORTED_ENCODINGS = config.get('parsers.encoding')

# Patrones regex para PHP
PHP_PATTERNS = {
    'libraries': [
        r'use\s+([^;]+);',
        r'require(?:_once)?\s*\(?\s*[\'"]([^\'"]+)[\'"]',
        r'include(?:_once)?\s*\(?\s*[\'"]([^\'"]+)[\'"]'
    ],
    'functions': r'function\s+(\w+)\s*\([^)]*\)',
    'classes': r'class\s+(\w+)',
    'variables': r'\$(\w+)\s*=',
}

# Configuración de plantillas
TEMPLATE_VARS = {
    'default_author': 'No especificado',
    'default_ticket': 'No especificado',
    'date_format': '%d/%m/%Y %H:%M:%S'
}

# Exclusiones
EXCLUDED_DIRS = [
    'vendor',
    'node_modules',
    'tests',
    'cache'
]

EXCLUDED_FILES = [
    '.git',
    '.env',
    '.gitignore'
]