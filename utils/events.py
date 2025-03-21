from typing import Dict, Callable, List
import logging
from dataclasses import dataclass
from datetime import datetime

@dataclass
class Event:
    name: str
    data: Dict
    timestamp: datetime = datetime.now()

class EventSystem:
    """Sistema de eventos para comunicación entre parsers"""
    
    def __init__(self):
        self._handlers: Dict[str, List[Callable]] = {}
        self._logger = logging.getLogger(__name__)
        self.history: List[Event] = []

    def subscribe(self, event: str, handler: Callable):
        """Suscribe un manejador a un evento"""
        if event not in self._handlers:
            self._handlers[event] = []
        self._handlers[event].append(handler)
        self._logger.debug(f"Handler {handler.__name__} suscrito a evento {event}")
        
    def emit(self, event_name: str, data: Dict):
        """Emite un evento con datos"""
        event = Event(event_name, data)
        self.history.append(event)
        
        if event_name in self._handlers:
            for handler in self._handlers[event_name]:
                try:
                    handler(data)
                except Exception as e:
                    self._logger.error(f"Error en handler {handler.__name__}: {e}")

    def get_history(self, event_type: str = None) -> List[Event]:
        """Obtiene el historial de eventos"""
        if event_type:
            return [e for e in self.history if e.name == event_type]
        return self.history

event_system = EventSystem()