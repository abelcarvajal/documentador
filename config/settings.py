from pathlib import Path

# Directorios base
BASE_DIR = Path(__file__).parent.parent
OUTPUT_DIR = BASE_DIR / "docs" / "generated"
TEMPLATES_DIR = BASE_DIR / "templates"

# Rutas relativas para documentación
SRC_CONTROLLER_PATH = "../../src/Controller"
DOC_PATH = "../../public/guia_programador"

LOG_DIR = BASE_DIR / "logs"

# Asegurar que los directorios existan
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
LOG_DIR.mkdir(parents=True, exist_ok=True)

# Configuración de logging
LOG_LEVEL = "INFO"
LOG_FORMAT = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
LOG_FILE = BASE_DIR / "logs/documentador.log"

# Configuración de parseo
MAX_FILE_SIZE_MB = 10
SUPPORTED_ENCODINGS = ['utf-8', 'latin-1', 'iso-8859-1', 'cp1252']

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