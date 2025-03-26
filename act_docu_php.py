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
    # (Implementación de la detección de funciones aquí)

    return changes

def extract_commit_info(git_output):
    """Extrae información del commit (autor, fecha, mensaje) del output de git."""
    commit_info = ""
    lines = git_output.split('\n')
    for i, line in enumerate(lines):
        if line.startswith('commit '):
            commit_info = line
            break
    return commit_info

def update_md_with_changes(md_file_path, changes, commit_info=""):
    """Actualiza el archivo MD existente con los cambios recientes."""
    try:
        with open(md_file_path, 'r', encoding='windows-1252') as f:
            md_content = f.read()
        
        # Obtener la fecha actual
        current_date = datetime.now().strftime('%Y-%m-%d')
        
        # Crear sección de cambios recientes
        changes_section = f"\n\n## Cambios Recientes ({current_date})\n\n"
        
        if commit_info:
            changes_section += f"### Commit Info\n{commit_info}\n\n"
        
        # (Implementación de la actualización de cambios aquí)

        # Guardar el archivo actualizado
        with open(md_file_path, 'w', encoding='windows-1252') as f:
            f.write(md_content)
        
        print(f"Archivo MD actualizado con los cambios recientes: {md_file_path}")
        
    except Exception as e:
        print(f"Error actualizando el archivo MD: {e}")

def main():
    """Función principal del documentador"""
    parser = argparse.ArgumentParser(description='Documentador de archivos PHP')
    parser.add_argument('-i', '--input', required=True, help='Archivo PHP a documentar')
    parser.add_argument('-c', '--changes-only', action='store_true', 
                        help='Documentar solo los cambios Git (sin documentación completa)')
    parser.add_argument('-g', '--with-git', action='store_true', 
                        help='Incluir información Git en la documentación completa')
    parser.add_argument('-n', '--num-commits', type=int, default=1, 
                        help='Número de commits a documentar')
    parser.add_argument('-d', '--since-date', 
                        help='Documentar cambios desde una fecha (formato YYYY-MM-DD)')
    parser.add_argument('-v', '--verbose', action='store_true', help='Mostrar información detallada')
    
    args = parser.parse_args()
    
    try:
        if args.changes_only:
            # Modo de solo cambios - generar un resumen de cambios
            print("Generando resumen de cambios Git...")
            
            # Determinar parámetros de consulta Git
            git_params = {'num_commits': args.num_commits}
            if args.since_date:
                git_params['since_date'] = args.since_date
            
            # Generar un archivo Markdown con solo los cambios
            generator.generate_changes_report(changes)
            
        else:
            # Modo documentación completa
            result = parser.parse()
            
            # Opcionalmente añadir información Git
            if args.with_git:
                print("Incluyendo historial Git en la documentación...")
                result['changes'] = parser.get_git_changes(args.num_commits)
            
            # Generar documentación
            generator.generate(result)
        
        print(f"Documentación generada exitosamente para {file_path}")
        
    except Exception as e:
        print(f"Error: {str(e)}")
