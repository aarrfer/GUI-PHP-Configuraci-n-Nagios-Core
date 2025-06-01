<?php
session_start();
require_once 'logger.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

//registro de acceso
logActivity($_SESSION['user'], 'Accedió a alerts.php');

$statusFile = '/var/lib/nagios4/status.dat';
$alertas = [];

if (file_exists($statusFile)) {
    $lines = file($statusFile);
    $in_host = false;
    $in_service = false;
    $block = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === "hoststatus {") {
            $in_host = true;
            $block = [];
        } elseif ($line === "}" && $in_host) {
            if (isset($block['host_name'], $block['current_state']) && $block['current_state'] !== '0') {
                $alertas[] = [
                    'tipo' => 'Host',
                    'host' => $block['host_name'],
                    'descripcion' => 'Estado del host',
                    'estado' => $block['current_state'],
                    'hora' => date('Y-m-d H:i:s', $block['last_check'] ?? time())
                ];
            }
            $in_host = false;
        } elseif ($in_host && strpos($line, "=") !== false) {
            list($key, $value) = explode("=", $line, 2);
            $block[$key] = $value;
        }

        if ($line === "servicestatus {") {
            $in_service = true;
            $block = [];
        } elseif ($line === "}" && $in_service) {
            if (isset($block['host_name'], $block['service_description'], $block['current_state']) && $block['current_state'] !== '0') {
                $alertas[] = [
                    'tipo' => 'Servicio',
                    'host' => $block['host_name'],
                    'descripcion' => $block['service_description'],
                    'estado' => $block['current_state'],
                    'hora' => date('Y-m-d H:i:s', $block['last_check'] ?? time())
                ];
            }
            $in_service = false;
        } elseif ($in_service && strpos($line, "=") !== false) {
            list($key, $value) = explode("=", $line, 2);
            $block[$key] = $value;
        }
    }
}

function estadoTexto($estado) {
    switch ($estado) {
        case '1': return '<span style="color:orange;">WARNING</span>';
        case '2': return '<span style="color:red;">CRITICAL</span>';
        case '3': return '<span style="color:gray;">UNKNOWN</span>';
        default: return 'OK';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alertas recientes - Nagios</title>
 <link rel="stylesheet" href="styles.css?v=2">

</head>
<body>
    <h1>Alertas recientes</h1>
    <p><a href="index.php">← Volver al Panel</a></p>

    <?php if (empty($alertas)): ?>
        <p>No hay alertas activas. Todo está OK ✔</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Fecha/Hora</th>
                <th>Host</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Estado</th>
            </tr>
            <?php foreach ($alertas as $alerta): ?>
                <tr>
                    <td><?= htmlspecialchars($alerta['hora']) ?></td>
                    <td><?= htmlspecialchars($alerta['host']) ?></td>
                    <td><?= htmlspecialchars($alerta['tipo']) ?></td>
                    <td><?= htmlspecialchars($alerta['descripcion']) ?></td>
                    <td><?= estadoTexto($alerta['estado']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
