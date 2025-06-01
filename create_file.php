<?php
session_start();
require_once 'logger.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$basePath = "/etc/nagios4/objects/";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['newFileName'])) {
    $newFileName = basename($_POST['newFileName']);
    $newFileContent = $_POST['newFileContent'] ?? '';

    //Validar extensión .cfg
    if (pathinfo($newFileName, PATHINFO_EXTENSION) !== 'cfg') {
        $message = "Error: Solo se permiten archivos con extensión .cfg";
    }
    //Validar caracteres del nombre de archivo
    elseif (preg_match('/^[a-zA-Z0-9_\-]+\.cfg$/', $newFileName) === 0) {
        $message = "Nombre de archivo no válido. Solo letras, números, guiones y guion bajo antes de .cfg";
    } else {
        $fullNewFilePath = $basePath . $newFileName;

        if (file_exists($fullNewFilePath)) {
            $message = "El archivo ya existe.";
        } else {
            if (file_put_contents($fullNewFilePath, $newFileContent) !== false) {
                $message = "Archivo creado correctamente: $newFileName";
                logActivity($_SESSION['user'], "Creó archivo objects/$newFileName");
                exec("sudo systemctl restart nagios4 2>&1", $output, $status);
                if ($status !== 0) {
                    $message .= ". Pero hubo error al reiniciar Nagios.";
                }
            } else {
                $message = "Error al crear el archivo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>crear archivo de objeto</title>
    <link rel="stylesheet" href="styles.css?v=3" />
</head>
<body>
    <h1>Crear nuevo archivo de objetos</h1>
    <a href="index.php">← Volver</a>

    <?php if (isset($message)) echo "<p><strong>$message</strong></p>"; ?>

    <form method="post" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px;">
        <label for="newFileName">Nombre archivo (solo extensión <code>.cfg</code>):</label><br>
        <input type="text" name="newFileName" id="newFileName" required pattern="[a-zA-Z0-9_\-]+\.cfg" title="Solo letras, números, guiones, guion bajo y debe terminar en .cfg"><br><br>

        <label for="newFileContent">Contenido:</label><br>
        <textarea name="newFileContent" id="newFileContent" rows="10" cols="60"></textarea><br><br>
        <input type="submit" value="Crear archivo" class="btn-small">
    </form>
</body>
</html>
