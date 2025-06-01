<?php
session_start();
require_once 'logger.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

//registrar acceso a este formulario
logActivity($_SESSION['user'], 'Accedió a dashboard.php');

$hostsFile = "/etc/nagios4/objects/hosts.cfg";
$hosts = [];

if (file_exists($hostsFile)) {
    $lines = file($hostsFile);
    $currentHost = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'define host') !== false) {
            $currentHost = [];
        } elseif (strpos($line, '}') !== false && !empty($currentHost)) {
            $hosts[] = $currentHost;
        } elseif (strpos($line, 'host_name') === 0) {
            $currentHost['host_name'] = preg_split('/\s+/', $line)[1];
        } elseif (strpos($line, 'address') === 0) {
            $currentHost['address'] = preg_split('/\s+/', $line)[1];
        }
    }
}

$statusFile = '/var/lib/nagios4/status.dat';
$hostStates = [];
$serviceStates = [];

if (file_exists($statusFile)) {
    $lines = file($statusFile);
    $in_host = false;
    $in_service = false;
    $host = [];
    $service = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "hoststatus {") {
            $in_host = true;
            $host = [];
        } elseif ($line === "}" && $in_host) {
            if (isset($host['host_name'], $host['current_state'])) {
                $hostStates[$host['host_name']] = $host['current_state'];
            }
            $in_host = false;
        } elseif ($in_host) {
            if (strpos($line, "=") !== false) {
                list($key, $value) = explode("=", $line, 2);
                $host[$key] = $value;
            }
        }

        if ($line === "servicestatus {") {
            $in_service = true;
            $service = [];
        } elseif ($line === "}" && $in_service) {
            if (isset($service['host_name'], $service['service_description'], $service['current_state'])) {
                $serviceStates[$service['host_name']][] = [
                    'description' => $service['service_description'],
                    'state' => $service['current_state']
                ];
            }
            $in_service = false;
        } elseif ($in_service) {
            if (strpos($line, "=") !== false) {
                list($key, $value) = explode("=", $line, 2);
                $service[$key] = $value;
            }
        }
    }
}

function estadoTexto($estado) {
    switch ($estado) {
        case '0': return '<span style="color:green;">OK</span>';
        case '1': return '<span style="color:orange;">WARNING</span>';
        case '2': return '<span style="color:red;">CRITICAL</span>';
        default: return 'UNKNOWN';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Hosts - Nagios</title>
    <link rel="stylesheet" href="styles.css?v=2">
    <!--refresco automatico de la pagina-->
    <meta http-equiv="refresh" content="10">

</head>
<body>
    <h1>Dashboard de Hosts y Servicios</h1>
    <p><a href="index.php">← Volver al Panel</a></p>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>Host</th>
                <th>IP</th>
                <th>Estado Host</th>
                <th>Servicios</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hosts as $host): ?>
                <tr>
                    <td><?= htmlspecialchars($host['host_name']) ?></td>
                    <td><?= htmlspecialchars($host['address']) ?></td>
                    <td><?= isset($hostStates[$host['host_name']]) ? estadoTexto($hostStates[$host['host_name']]) : 'Sin datos' ?></td>
                    <td>
                        <?php
                        if (isset($serviceStates[$host['host_name']])) {
                            foreach ($serviceStates[$host['host_name']] as $srv) {
                                echo htmlspecialchars($srv['description']) . ': ' . estadoTexto($srv['state']) . '<br>';
                            }
                        } else {
                            echo 'Sin servicios';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
