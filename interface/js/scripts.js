function selectDirectory() {
    const input = document.createElement('input');
    input.type = 'file';
    input.webkitdirectory = true; // Permitir selección de directorios
    input.addEventListener('change', function(event) {
        // Obtener la ruta relativa del primer archivo en el directorio
        const relativePath = event.target.files[0].webkitRelativePath;
        console.log('Ruta relativa completa: ' + relativePath);

        const directoryPath = relativePath.substring(0, relativePath.lastIndexOf('/') + 1) // Obtener solo el nombre del directorio
        document.getElementById('outputPath').value = directoryPath; // Establecer el valor en el campo de texto
        console.log('Ruta relativa capturada: ' + directoryPath);
    });
    input.click();
}


