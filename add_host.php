<?php
session_start();
require_once 'logger.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

//registro de acceso
logActivity($_SESSION['user'], 'Accedió a add_host.php');

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostName = trim($_POST['host_name']);
    $address = trim($_POST['address']);
    $alias = trim($_POST['alias']);
    $selectedService = $_POST['service'] ?? '';

    if ($hostName && $address && $alias) {
        $newHost = <<<EOT
define host {
    use                 linux-server
    host_name           $hostName
    alias               $alias
    address             $address
    max_check_attempts  5
    check_period        24x7
    notification_interval 30
    notification_period 24x7
}

EOT;

        $hostsFile = '/etc/nagios4/objects/hosts.cfg';
        file_put_contents($hostsFile, $newHost, FILE_APPEND | LOCK_EX);

        // Aquí añadimos el servicio seleccionado si no es "none"
        if ($selectedService !== 'none') {
            // Definimos un array con las definiciones básicas de cada servicio
            $servicesDefs = [
                'apache' => [
                    'description' => 'Apache HTTP',
                    'check_command' => 'check_http'
                ],
                'ssh' => [
                    'description' => 'SSH',
                    'check_command' => 'check_ssh'
                ],
                'ping' => [
                    'description' => 'Ping',
                    'check_command' => 'check_ping!100.0,20%!500.0,60%'
                ],
                // Agrega más servicios aquí si quieres
            ];

            if (isset($servicesDefs[$selectedService])) {
                $service = $servicesDefs[$selectedService];

                $newService = <<<EOT
define service {
    use                     generic-service
    host_name               $hostName
    service_description     {$service['description']}
    check_command           {$service['check_command']}
    max_check_attempts      3
    check_interval          5
    retry_interval          1
    check_period            24x7
    notification_interval   30
    notification_period     24x7
}

EOT;

                $servicesFile = '/etc/nagios4/objects/services.cfg';
                file_put_contents($servicesFile, $newService, FILE_APPEND | LOCK_EX);
            }
        }

        $validacion = shell_exec('sudo /usr/sbin/nagios4 -v /etc/nagios4/nagios.cfg 2>&1');

        if (strpos($validacion, 'Things look okay') !== false) {
            shell_exec('sudo systemctl restart nagios4');
            $mensaje = "✅ Host añadido correctamente." . ($selectedService !== 'none' ? " Servicio {$servicesDefs[$selectedService]['description']} añadido también." : "") . " Nagios se ha reiniciado.";
        } else {
            $mensaje = "❌ Error de configuración en Nagios:<pre>$validacion</pre>";
        }
    } else {
        $mensaje = "⚠️ Campos obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir nuevo host</title>
    <link rel="stylesheet" href="styles.css?v=2">
</head>
<body>
    <h1>Añadir nuevo host a Nagios</h1>
    <p><a href="dashboard.php">← Volver al dashboard</a></p>
    <p><a href="index.php">Volver a la página principal</a></p>
    <?php if ($mensaje): ?>
        <div style="background-color:#f0f0f0; padding:10px; margin-bottom:15px;">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Nombre del Host: <input type="text" name="host_name" required></label><br><br>
        <label>Dirección IP: <input type="text" name="address" required></label><br><br>
        <label>Alias: <input type="text" name="alias" required></label><br><br>

        <label>Servicio a añadir:
            <select name="service">
                <option value="none">-- Ninguno --</option>
                <option value="apache">Apache HTTP</option>
                <option value="ssh">SSH</option>
                <option value="ping">Ping</option>
                <!-- Agrega más opciones aquí -->
            </select>
        </label><br><br>

        <button type="submit">➕ Añadir Host</button>
    </form>
</body>
</html>
