<?php
$output = "";
$baseDir = 'C:\\git_consuerte\\serversoap_recaudos';
$outputDirs = [
    '../docs/generated/' => 'Directorio de Pruebas',
    '\\public\\guia_programador\\' => 'Directorio de Produccion'
];
$docuPhpPath = '../docu_php.py';

//echo "Current working directory: " . getcwd() . "<br>"; // Debug line to check current working directory
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $fileInput = $_POST['fileInput'];
    $outputDir = $_POST['outputDir'];

    if (!file_exists($fileInput)) {
        echo "El archivo no existe.";
        exit;
    }
    
    $ejecutar = "python \"$docuPhpPath\" -i \"$fileInput\" -o \"$outputDir\"";
    //print_r($ejecutar);
    $output = shell_exec($ejecutar . ' 2>&1'); // Capture both output and errors
    //echo $output;
    
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
            <form id="docForm" action="index.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="fileInput" class="form-label">Ruta del archivo a documentar:</label>
                <input type="text" id="fileInput" class="form-control" name="fileInput" required>
            </div>

                <div class="mb-3">
                    <label for="outputDir" class="form-label">Selecciona el directorio de salida:</label>
                    <select id="outputDir" name="outputDir" class="form-control" required>
                        <?php
                            foreach ($outputDirs as $path => $label): ?>
                            <option value = <?php echo $baseDir . $path; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
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
