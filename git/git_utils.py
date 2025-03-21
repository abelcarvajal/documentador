# documentador/git/git_utils.py
"""
Utilidades para trabajar con repositorios Git.

Este módulo proporciona funciones para interactuar con repositorios Git,
obtener información del historial de cambios, autores, y realizar
análisis basados en las diferencias entre versiones.
"""
import os
import logging
import subprocess
from pathlib import Path
from typing import List, Dict, Tuple, Optional, Union, Set


def is_git_repo(path: Union[str, Path]) -> bool:
    """
    Verifica si el directorio especificado es parte de un repositorio Git.

    Args:
        path: Ruta al directorio a verificar

    Returns:
        bool: True si es parte de un repositorio Git, False en caso contrario
    """
    try:
        result = subprocess.run(
            ["git", "rev-parse", "--is-inside-work-tree"],
            cwd=path,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=False
        )
        return result.returncode == 0 and "true" in result.stdout
    except Exception as e:
        logging.debug(f"Error verificando repositorio Git: {e}")
        return False


def get_git_root(path: Union[str, Path]) -> Optional[Path]:
    """
    Obtiene la ruta raíz del repositorio Git.

    Args:
        path: Ruta desde donde buscar la raíz del repositorio

    Returns:
        Path: Ruta raíz del repositorio Git o None si no se encuentra
    """
    if not is_git_repo(path):
        return None
    
    try:
        result = subprocess.run(
            ["git", "rev-parse", "--show-toplevel"],
            cwd=path,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=True
        )
        return Path(result.stdout.strip())
    except subprocess.CalledProcessError:
        return None


def get_file_history(file_path: Union[str, Path], max_entries: int = 10) -> List[Dict[str, str]]:
    """
    Obtiene el historial de cambios de un archivo en el repositorio Git.

    Args:
        file_path: Ruta al archivo
        max_entries: Número máximo de entradas del historial a retornar

    Returns:
        List[Dict]: Lista de diccionarios con la información de cada commit
    """
    if not is_git_repo(Path(file_path).parent):
        return []
    
    try:
        # Formato personalizado: hash, autor, fecha, mensaje
        format_str = "--pretty=format:%H|%an|%ad|%s"
        
        result = subprocess.run(
            ["git", "log", format_str, "--date=short", "-n", str(max_entries), "--", str(file_path)],
            cwd=Path(file_path).parent,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=True
        )
        
        commits = []
        for line in result.stdout.strip().split('\n'):
            if not line:
                continue
            
            parts = line.split('|', 3)
            if len(parts) >= 4:
                commits.append({
                    'hash': parts[0],
                    'author': parts[1],
                    'date': parts[2],
                    'message': parts[3]
                })
        
        return commits
    except subprocess.CalledProcessError as e:
        logging.error(f"Error obteniendo historial Git: {e}")
        return []


def get_file_contributors(file_path: Union[str, Path]) -> List[Dict[str, Union[str, int]]]:
    """
    Obtiene la lista de contribuyentes de un archivo con su número de commits.

    Args:
        file_path: Ruta al archivo

    Returns:
        List[Dict]: Lista de diccionarios con nombre de autor y número de commits
    """
    if not is_git_repo(Path(file_path).parent):
        return []
    
    try:
        result = subprocess.run(
            ["git", "shortlog", "-sne", "--", str(file_path)],
            cwd=Path(file_path).parent,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=True
        )
        
        contributors = []
        for line in result.stdout.strip().split('\n'):
            if not line:
                continue
            
            # El formato es: número de commits, nombre <email>
            parts = line.strip().split('\t')
            if len(parts) == 2:
                count = int(parts[0].strip())
                author_info = parts[1].strip()
                
                # Extraer email si está disponible
                email = ""
                if '<' in author_info and '>' in author_info:
                    email_start = author_info.find('<')
                    email_end = author_info.find('>')
                    email = author_info[email_start + 1:email_end]
                    name = author_info[:email_start].strip()
                else:
                    name = author_info
                
                contributors.append({
                    'name': name,
                    'email': email,
                    'commits': count
                })
        
        return sorted(contributors, key=lambda x: x['commits'], reverse=True)
    except subprocess.CalledProcessError as e:
        logging.error(f"Error obteniendo contribuyentes: {e}")
        return []


def get_file_diff(file_path: Union[str, Path], commit_hash: str) -> str:
    """
    Obtiene las diferencias introducidas por un commit específico en un archivo.

    Args:
        file_path: Ruta al archivo
        commit_hash: Hash del commit

    Returns:
        str: Diferencias en formato unificado
    """
    if not is_git_repo(Path(file_path).parent):
        return ""
    
    try:
        result = subprocess.run(
            ["git", "show", f"{commit_hash}:{file_path}"],
            cwd=Path(file_path).parent,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=True
        )
        
        return result.stdout
    except subprocess.CalledProcessError as e:
        logging.error(f"Error obteniendo diferencias: {e}")
        return ""


def get_file_blame(file_path: Union[str, Path]) -> List[Dict[str, str]]:
    """
    Obtiene información de "blame" para un archivo, mostrando quien modificó cada línea.

    Args:
        file_path: Ruta al archivo

    Returns:
        List[Dict]: Lista de diccionarios con información de cada línea
    """
    if not is_git_repo(Path(file_path).parent):
        return []
    
    try:
        result = subprocess.run(
            ["git", "blame", "--line-porcelain", str(file_path)],
            cwd=Path(file_path).parent,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=True
        )
        
        lines_info = []
        current_line = {}
        content = ""
        
        for line in result.stdout.split('\n'):
            if line.startswith('\t'):
                # Contenido de la línea
                content = line[1:]
                if current_line:
                    current_line['content'] = content
                    lines_info.append(current_line)
                    current_line = {}
            elif " " in line:
                key, value = line.split(" ", 1)
                if key == "author":
                    current_line['author'] = value
                elif key == "committer-time":
                    current_line['date'] = value
                elif key == "summary":
                    current_line['message'] = value
        
        return lines_info
    except subprocess.CalledProcessError as e:
        logging.error(f"Error obteniendo blame: {e}")
        return []


def get_modified_files(days: int = 7) -> List[Path]:
    """
    Obtiene la lista de archivos modificados en los últimos días.

    Args:
        days: Número de días hacia atrás para buscar

    Returns:
        List[Path]: Lista de rutas de archivos modificados
    """
    try:
        result = subprocess.run(
            ["git", "log", f"--since={days}.days.ago", "--name-only", "--pretty=format:"],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=True
        )
        
        files = set()
        for line in result.stdout.strip().split('\n'):
            if line and not line.isspace():
                file_path = Path(line.strip())
                if file_path.exists():
                    files.add(file_path)
        
        return sorted(list(files))
    except subprocess.CalledProcessError as e:
        logging.error(f"Error obteniendo archivos modificados: {e}")
        return []


def get_file_creation_date(file_path: Union[str, Path]) -> Optional[str]:
    """
    Obtiene la fecha de creación de un archivo según Git.

    Args:
        file_path: Ruta al archivo

    Returns:
        Optional[str]: Fecha de creación o None si no se puede determinar
    """
    if not is_git_repo(Path(file_path).parent):
        return None
    
    try:
        # Busca el primer commit que introdujo este archivo
        result = subprocess.run(
            ["git", "log", "--follow", "--format=%ad", "--date=short", "--reverse", "--", str(file_path)],
            cwd=Path(file_path).parent,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=True
        )
        
        lines = result.stdout.strip().split('\n')
        if lines and lines[0]:
            return lines[0]
        return None
    except subprocess.CalledProcessError:
        return None


def get_file_content_at_commit(file_path: Union[str, Path], commit_hash: str) -> Optional[str]:
    """
    Obtiene el contenido de un archivo en un commit específico.

    Esto es útil para analizar cómo ha cambiado un archivo a lo largo del tiempo.

    Args:
        file_path: Ruta al archivo
        commit_hash: Hash del commit

    Returns:
        Optional[str]: Contenido del archivo en ese commit o None si hubo error
    """
    if not is_git_repo(Path(file_path).parent):
        return None
    
    try:
        file_path_str = str(file_path)
        if isinstance(file_path, Path):
            # Convertir path relativo al repositorio
            repo_root = get_git_root(file_path.parent)
            if repo_root:
                file_path_str = str(file_path.relative_to(repo_root))
        
        result = subprocess.run(
            ["git", "show", f"{commit_hash}:{file_path_str}"],
            cwd=Path(file_path).parent,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=False  # No lanzar excepción si falla
        )
        
        if result.returncode == 0:
            return result.stdout
        return None
    except Exception as e:
        logging.error(f"Error obteniendo contenido en commit {commit_hash}: {e}")
        return None