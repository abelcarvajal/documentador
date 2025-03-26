<?php
$output = "";
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $fileInput = escapeshellarg($_POST['fileInput']);
    $outputDir = escapeshellarg($_POST['outputDir']);
    
    $ejecutar = "python -m documentador.docu_php -i $fileInput -o $outputPath";
    $output = shell_exec($ejecutar);
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="windows-1252">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/styles.css">
    <title>Documentador</title>
</head>
<body>
    <div class="container justify-content-center">
        <h2>Generador de Documentacion</h2>
        <div class="row mb-3 h-50 w-50 position-absolute top-50 start-50 translate-middle">
            <form id="docForm" action="index.php" method="post">
                <div class="mb-3">
                    <label for="fileInput" class="form-label">Selecciona el archivo a documentar:</label>
                    <input type="file" id="fileInput" class="form-control" name="fileInput" required>
                </div>
                
                <div class="mb-3">
                    <label for="outputPath" class="form-label">Ingrese la ruta de salida:</label>
                    <input type="text" class="form-control mb-2" id="outputPath" name="outputPath" required>
                    <input type="button" class="btn btn-secondary" onclick="selectDirectory()" id="selectPath" value="Seleccionar carpeta">
                </div>
                
                <div class="mb-3">
                    <label for="outputDir" class="form-label">Selecciona el directorio de salida:</label>
                    <select id="outputDir" name="outputDir" class="form-control" required>
                        <option value="documentador/docs/generated/">Directorio de Pruebas</option>
                        <option value="C:\git_consuerte\serversoap_recaudos\public\guia_programador/">Directorio de Produccin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="docType">Tipo de documentacion:</label>
                    <select id="docType" name="docType">
                        <option value="complete">Documentacion Completa</option>
                        <option value="latest">Ultimos Cambios</option>
                    </select>
                </div>
                
                <div class="mb-3 d-flex justify-content-center">
                    <input type="submit" class="btn btn-secondary" value="Generar Documentacion">
                </div>
            </form>
        </div>
        <script src="js/scripts.js"></script>
    </div>
</body>
</html>
