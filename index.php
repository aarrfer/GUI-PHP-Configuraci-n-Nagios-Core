<?php
session_start();
require_once 'logger.php';
echo "Usuario actual: " . $_SESSION['user'];
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

//control acceso
logActivity($_SESSION['user'], 'Accedió a index.php');

$basePath = "/etc/nagios4/";

//Escanear directorios y listar archivos
function getConfigFiles($dir, $extFilter = '') {
    $files = [];
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = $dir . '/' . $file;
        if (is_file($fullPath)) {
            if ($extFilter) {
                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === strtolower($extFilter)) {
                    $files[] = str_replace($GLOBALS['basePath'] . '/', '', $fullPath);
                }
            } else {
                $files[] = str_replace($GLOBALS['basePath'] . '/', '', $fullPath);
            }
        }
    }
    return $files;
}

//Escanea la carpeta base y lista directorios con sus archivos
$directories = [];
foreach (scandir($basePath) as $folder) {
    if ($folder === '.' || $folder === '..') continue;
    $fullPath = $basePath . '/' . $folder;
    if (is_dir($fullPath)) {
        $directories[$folder] = getConfigFiles($fullPath);
    }
}

//Mostrar el contenido del archivo seleccionado
$fileContent = "";
$selectedFile = isset($_GET['configFile']) ? $_GET['configFile'] : null;
if ($selectedFile && file_exists($basePath . '/' . $selectedFile)) {
    $filePath = $basePath . '/' . $selectedFile;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['deleteFile'])) {
            // Botón eliminar archivo
            unlink($filePath);
            exec("sudo systemctl restart nagios4 2>&1", $output, $status);
            $message = $status === 0 ? "Archivo eliminado y Nagios reiniciado correctamente." 
                                     : "Error al reiniciar Nagios: " . implode("\n", $output);
            // Vaciar contenido y selectedFile porque archivo borrado
            $fileContent = "";
            $selectedFile = null;
        } elseif (isset($_POST['fileContent'])) {
            // Guardar cambios
            file_put_contents($filePath, $_POST['fileContent']);
            exec("sudo systemctl restart nagios4 2>&1", $output, $status);
            $message = $status === 0 ? "Archivo guardado y Nagios reiniciado correctamente." 
                                     : "Error al reiniciar Nagios: " . implode("\n", $output);
            $fileContent = file_get_contents($filePath);
        }
    } else {
        $fileContent = file_get_contents($filePath);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Interfaz de Configuración de Nagios</title>
    <link rel="stylesheet" href="styles.css?v=2" />
    <script>
        function updateFileSelection() {
            const selectedFile = document.getElementById('configFile').value;
            if (selectedFile) {
                window.location.href = "?configFile=" + selectedFile;
            }
        }
    </script>
</head>
<body>
    <h1>Configuración de Nagios</h1>

    <!-- Botones horizontales -->
    <div class="button-bar">
        <a href="logout.php" style="color: red;">Cerrar sesión</a>
        <a href="dashboard.php" style="background-color: green;">Ver Dashboard</a>
        <a href="add_host.php" style="background-color: green;">Nuevo host</a>
        <a href="alerts.php">Alertas recientes</a>
        <a href="view_logs.php" style="background-color: green;">Ver logs</a>
        <?php if ($_SESSION['user'] === 'admin'): ?>
            <a href="manage_users.php" style="background-color: green;">Administrar Usuarios</a>
        <?php endif; ?>
        <a href="create_file.php" style="color: red;">Crear archivo de objeto</a>
    </div>

    <?php if (isset($message)) echo "<p><strong>$message</strong></p>"; ?>

    <form>
        <label for="configFile">Archivos de configuración de Nagios.</label>
        <select name="configFile" id="configFile" onchange="updateFileSelection()">
            <option value="">Seleccione archivo</option>

            <!-- Configuración General -->
            <optgroup label="Configuración General">
                <option value="nagios.cfg" <?php echo ($selectedFile == 'nagios.cfg') ? 'selected' : ''; ?>>nagios.cfg</option>
                <option value="resource.cfg" <?php echo ($selectedFile == 'resource.cfg') ? 'selected' : ''; ?>>resource.cfg</option>
                <option value="cgi.cfg" <?php echo ($selectedFile == 'cgi.cfg') ? 'selected' : ''; ?>>cgi.cfg</option>
                <option value="htdigest.users" <?php echo ($selectedFile == 'htdigest.users') ? 'selected' : ''; ?>>htdigest.users</option>
            </optgroup>

            <!-- Configuración de Objetos -->
            <optgroup label="Configuración de Objetos">
                <?php
                //Listar dinámicamente solo archivos .cfg dentro de /etc/nagios4/objects
                $objectFiles = getConfigFiles($basePath . '/objects', 'cfg');
                foreach ($objectFiles as $objFile) {
                    $selected = ($selectedFile === $objFile) ? 'selected' : '';
                    //Mostrar solo el nombre del archivo sin el prefijo 'objects/'
                    $displayName = basename($objFile);
                    echo "<option value=\"$objFile\" $selected>$displayName</option>";
                }
                ?>
            </optgroup>

        </select>
    </form>

    <?php if ($selectedFile): ?>
    <h2>Editando: <?php echo htmlspecialchars($selectedFile); ?></h2>
    <form method="post" onsubmit="return confirm('¿Seguro que quieres eliminar este archivo? Esta acción no se puede deshacer.');">
        <textarea name="fileContent" rows="20" cols="80"><?php echo htmlspecialchars($fileContent); ?></textarea><br>
        <button type="submit" class="btn btn-save" name="saveFile" value="1">Guardar Cambios</button>
        <button type="submit" name="deleteFile" value="1" class="btn btn-delete">
            Eliminar Archivo
        </button>
    </form>
    <p><a href="download.php?file=<?php echo urlencode($selectedFile); ?>" class="btn-download">Descargar archivo</a></p>
    <br>
    <a href="index.php">Volver a la página principal</a>
<?php endif; ?>

<footer class="footer">
    <p>Desarrollado por Álvaro Arroyo Fernández</p>
</footer>
</body>
</html>
