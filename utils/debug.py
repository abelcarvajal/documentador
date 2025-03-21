# utils/debug.py
"""
Utilidades de depuración y logging.

Este módulo proporciona funciones para configurar el sistema de logging,
medir tiempos de ejecución y depurar código.
"""

import logging
import time
import json
import pprint
import inspect
import functools
import traceback
from pathlib import Path
from typing import Dict, Any, Optional, Callable, Union, TypeVar, cast

# Variable global para controlar el modo debug
_DEBUG_MODE = False

T = TypeVar('T')

def setup_logging(verbose: bool = False) -> None:
    """
    Configura el sistema de logging.
    
    Args:
        verbose: Si es True, establece el nivel de logging a DEBUG
    """
    # Configuración básica
    root_logger = logging.getLogger()
    level = logging.DEBUG if verbose else logging.INFO
    root_logger.setLevel(level)
    
    # Limpiar handlers previos
    for handler in root_logger.handlers[:]:
        root_logger.removeHandler(handler)
    
    # Formato de los mensajes
    formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    
    # Handler para consola
    console = logging.StreamHandler()
    console.setLevel(level)
    console.setFormatter(formatter)
    root_logger.addHandler(console)
    
    # Handler para archivo
    log_dir = Path(__file__).parent.parent / 'logs'
    log_dir.mkdir(parents=True, exist_ok=True)
    log_file = log_dir / 'documentador.log'
    
    file_handler = logging.FileHandler(log_file, encoding='utf-8')
    file_handler.setLevel(logging.DEBUG)  # El archivo siempre guarda todo
    file_handler.setFormatter(formatter)
    root_logger.addHandler(file_handler)
    
    logging.info(f"Logging configurado (nivel: {logging.getLevelName(level)})")

def log_execution_time(func: Callable[..., T]) -> Callable[..., T]:
    """
    Decorador para medir y registrar el tiempo de ejecución de una función.
    
    Args:
        func: Función a decorar
        
    Returns:
        Callable: Función decorada
    """
    @functools.wraps(func)
    def wrapper(*args: Any, **kwargs: Any) -> T:
        start_time = time.time()
        result = func(*args, **kwargs)
        execution_time = time.time() - start_time
        
        # Registrar tiempo de ejecución
        logging.debug(f"{func.__name__} ejecutada en {execution_time:.4f} segundos")
        
        return result
    
    return cast(Callable[..., T], wrapper)

def dump_object(obj: Any, max_depth: int = 2) -> str:
    """
    Convierte un objeto a una representación de texto para depuración.
    
    Args:
        obj: Objeto a convertir
        max_depth: Profundidad máxima de recursión
        
    Returns:
        str: Representación textual del objeto
    """
    try:
        if hasattr(obj, '__dict__'):
            # Para objetos personalizados
            return pprint.pformat(obj.__dict__, depth=max_depth)
        else:
            # Intentar serializar a JSON
            return json.dumps(obj, indent=2, default=lambda o: str(o))
    except Exception:
        # Si todo falla, usar la representación por defecto
        return pprint.pformat(obj, depth=max_depth)

def debug_mode(enabled: bool = True) -> None:
    """
    Activa o desactiva el modo de depuración global.
    
    Args:
        enabled: Si se debe habilitar el modo depuración
    """
    global _DEBUG_MODE
    _DEBUG_MODE = enabled
    
    # Ajustar nivel de logging según el modo
    if enabled:
        logging.getLogger().setLevel(logging.DEBUG)
        logging.info("Modo DEBUG activado")
    else:
        logging.getLogger().setLevel(logging.INFO)
        logging.info("Modo DEBUG desactivado")

def is_debug_enabled() -> bool:
    """
    Verifica si el modo de depuración está activado.
    
    Returns:
        bool: True si el modo depuración está activado
    """
    return _DEBUG_MODE

def print_stack_trace(skip_frames: int = 1) -> None:
    """
    Imprime la pila de llamadas actual para depuración.
    
    Args:
        skip_frames: Número de frames a omitir al inicio
    """
    if not _DEBUG_MODE:
        return
    
    stack = traceback.extract_stack()[:-skip_frames]
    logging.debug("Stack trace actual:")
    for frame in stack:
        logging.debug(f"  {frame.filename}:{frame.lineno} en {frame.name}")