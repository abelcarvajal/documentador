import subprocess
import re
from pathlib import Path
from datetime import datetime

def get_git_changes(file_path, num_commits=1):
    """Obtiene los cambios recientes de un archivo usando Git."""
    try:
        # Obtener los cambios del último commit que afectó al archivo
        cmd = ["git", "log", "-p", f"-{num_commits}", "--", str(file_path)]
        result = subprocess.run(cmd, capture_output=True, text=True, check=True)
        return result.stdout
    except subprocess.CalledProcessError as e:
        print(f"Error obteniendo cambios de Git: {e}")
        return ""

def parse_git_changes(git_diff):
    """Analiza el diff de Git para extraer líneas agregadas y eliminadas."""
    added_lines = []
    removed_lines = []
    
    # Patrones para identificar líneas agregadas/eliminadas en el diff
    for line in git_diff.split('\n'):
        if line.startswith('+') and not line.startswith('+++'):
            added_lines.append(line[1:])  # Elimina el '+' inicial
        elif line.startswith('-') and not line.startswith('---'):
            removed_lines.append(line[1:])  # Elimina el '-' inicial
    
    return added_lines, removed_lines

def analyze_changed_elements(added_lines, removed_lines):
    """Analiza qué elementos (funciones, variables, tablas) han cambiado."""
    changes = {
        'functions': {'added': set(), 'modified': set(), 'removed': set()},
        'variables': {'added': set(), 'modified': set(), 'removed': set()},
        'tables': {'added': set(), 'modified': set(), 'removed': set()},
    }
    
    # Unir todas las líneas para analizarlas
    added_code = '\n'.join(added_lines)
    removed_code = '\n'.join(removed_lines)
    
    # Detectar funciones cambiadas
    added_functions = parse_php_functions(added_code)
    removed_functions = parse_php_functions(removed_code)
    
    changes['functions']['added'] = added_functions - removed_functions
    changes['functions']['removed'] = removed_functions - added_functions
    changes['functions']['modified'] = added_functions.intersection(removed_functions)
    
    # Detectar variables cambiadas
    added_vars = parse_php_variables(added_code)
    removed_vars = parse_php_variables(removed_code)
    
    # Para variables globales
    changes['variables']['added'] = added_vars['globales'] - removed_vars['globales']
    changes['variables']['removed'] = removed_vars['globales'] - added_vars['globales']
    changes['variables']['modified'] = added_vars['globales'].intersection(removed_vars['globales'])
    
    # Detectar tablas cambiadas
    added_tables = set(parse_php_tables(added_code))
    removed_tables = set(parse_php_tables(removed_code))
    
    changes['tables']['added'] = added_tables - removed_tables
    changes['tables']['removed'] = removed_tables - added_tables
    changes['tables']['modified'] = added_tables.intersection(removed_tables)
    
    return changes

def extract_commit_info(git_output):
    """Extrae información del commit (autor, fecha, mensaje) del output de git."""
    commit_info = ""
    lines = git_output.split('\n')
    for i, line in enumerate(lines):
        if line.startswith('commit '):
            commit_hash = line.split(' ')[1]
            commit_info += f"Commit: {commit_hash}\n"
        elif line.startswith('Author: '):
            author = line[8:]
            commit_info += f"Autor: {author}\n"
        elif line.startswith('Date: '):
            date = line[6:]
            commit_info += f"Fecha: {date}\n"
        elif i > 3 and line and not line.startswith('+') and not line.startswith('-') and not line.startswith(' '):
            # Probablemente sea el mensaje del commit
            if not line.strip().startswith('diff --git'):
                commit_info += f"Mensaje: {line.strip()}\n"
                break
    
    return commit_info

def update_md_with_changes(md_file_path, changes, commit_info=""):
    """Actualiza el archivo MD existente con los cambios recientes."""
    try:
        with open(md_file_path, 'r', encoding='utf-8') as f:
            md_content = f.read()
        
        # Obtener la fecha actual
        current_date = datetime.now().strftime('%Y-%m-%d')
        
        # Crear sección de cambios recientes
        changes_section = f"\n\n## Cambios Recientes ({current_date})\n\n"
        
        if commit_info:
            changes_section += f"### Commit Info\n{commit_info}\n\n"
        
        # Agregar funciones cambiadas
        if any(changes['functions'].values()):
            changes_section += "### Funciones\n"
            if changes['functions']['added']:
                changes_section += "#### Agregadas\n"
                for func in changes['functions']['added']:
                    changes_section += f"* `{func}()`\n"
            if changes['functions']['modified']:
                changes_section += "#### Modificadas\n"
                for func in changes['functions']['modified']:
                    changes_section += f"* `{func}()`\n"
            if changes['functions']['removed']:
                changes_section += "#### Eliminadas\n"
                for func in changes['functions']['removed']:
                    changes_section += f"* `{func}()`\n"
        
        # Agregar variables cambiadas
        if any(changes['variables'].values()):
            changes_section += "\n### Variables Globales\n"
            if changes['variables']['added']:
                changes_section += "#### Agregadas\n"
                for var in changes['variables']['added']:
                    changes_section += f"* `${var}`\n"
            if changes['variables']['modified']:
                changes_section += "#### Modificadas\n"
                for var in changes['variables']['modified']:
                    changes_section += f"* `${var}`\n"
            if changes['variables']['removed']:
                changes_section += "#### Eliminadas\n"
                for var in changes['variables']['removed']:
                    changes_section += f"* `${var}`\n"
        
        # Agregar tablas cambiadas
        if any(changes['tables'].values()):
            changes_section += "\n### Tablas\n"
            if changes['tables']['added']:
                changes_section += "#### Agregadas\n"
                for table in changes['tables']['added']:
                    changes_section += f"* `{table}`\n"
            if changes['tables']['modified']:
                changes_section += "#### Modificadas\n"
                for table in changes['tables']['modified']:
                    changes_section += f"* `{table}`\n"
            if changes['tables']['removed']:
                changes_section += "#### Eliminadas\n"
                for table in changes['tables']['removed']:
                    changes_section += f"* `{table}`\n"
        
        # Verificar si ya existe una sección de cambios recientes
        if "## Cambios Recientes" in md_content:
            # Reemplazar la sección existente
            md_content = re.sub(r"## Cambios Recientes.*?(?=##|\Z)", changes_section, md_content, flags=re.DOTALL)
        else:
            # Agregar la nueva sección antes de la última sección (normalmente "NOTA:")
            last_section_match = re.search(r"### \*\*NOTA:\*\*", md_content)
            if last_section_match:
                insert_position = last_section_match.start()
                md_content = md_content[:insert_position] + changes_section + md_content[insert_position:]
            else:
                # Si no hay sección "NOTA:", añadir al final
                md_content += changes_section
        
        # Guardar el archivo actualizado
        with open(md_file_path, 'w', encoding='utf-8') as f:
            f.write(md_content)
        
        print(f"Archivo MD actualizado con los cambios recientes: {md_file_path}")
        
    except Exception as e:
        print(f"Error actualizando el archivo MD: {e}")

def document_recent_changes(file_path, num_commits=1):
    """Documenta los cambios recientes en un archivo PHP."""
    file_path = Path(file_path)
    
    # Verificar que el archivo exista
    if not file_path.exists():
        print(f"Error: El archivo {file_path} no existe.")
        return
    
    # Obtener cambios de Git
    git_output = get_git_changes(file_path, num_commits)
    if not git_output:
        print(f"No se encontraron cambios recientes para {file_path}")
        return
    
    # Extraer información del commit
    commit_info = extract_commit_info(git_output)
    
    # Extraer y analizar cambios
    added_lines, removed_lines = parse_git_changes(git_output)
    changes = analyze_changed_elements(added_lines, removed_lines)
    
    # Ruta al archivo MD
    md_file_path = Path("docs") / file_path.with_suffix('.md').name
    
    # Verificar si el archivo MD existe, si no, crearlo primero
    if not md_file_path.exists():
        print(f"El archivo MD no existe. Generando documentación completa primero...")
        process_php_file(file_path)
    
    # Actualizar el archivo MD con los cambios
    update_md_with_changes(md_file_path, changes, commit_info)
