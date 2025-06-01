<?php
session_start();
require_once 'logger.php';

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    echo "Acceso denegado.";
    exit;
}

$logFile = __DIR__ . '/activity.log';
$logDir = __DIR__ . '/logs';

if (file_exists($logFile)) {
    $allLogs = file($logFile);
    $logs = [];
    $currentDate = date('Y-m-d');
    foreach ($allLogs as $line) {
        if (preg_match('/^\[' . preg_quote($currentDate, '/') . '/', $line)) {
            $logs[] = $line;
        }
    }
} else {
    $logs = [];
}


?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Logs de Actividad</title>
    <link rel="stylesheet" href="styles.css?v=2">
    <link rel="stylesheet" href="styles.css?v=2">
</head>
<body>
    <h1>Logs de Actividad de Usuarios</h1>
    <a href="index.php">← Volver</a>
    <pre><?php echo htmlspecialchars(implode("", $logs)); ?></pre>
</body>
</html>
