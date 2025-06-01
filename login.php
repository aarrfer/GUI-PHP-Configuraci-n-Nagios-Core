<?php
session_start();
require_once 'logger.php';

//Ruta al archivo htdigest
$htdigestFile = '/etc/nagios4/htdigest.users';
$realm = 'Nagios4';

//Función para leer usuarios del archivo htdigest
function getUsers($file, $realm) {
    $users = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($user, $fileRealm, $hash) = explode(':', $line);
            if ($fileRealm === $realm) {
                $users[$user] = $hash;
            }
        }
    }
    return $users;
}

//Si ya está logueado, lo mandamos al index
if (isset($_SESSION['user'])) {
    logActivity($username, 'Inicio de sesión');
    header('Location: index.php');
    exit;
}

//Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $users = getUsers($htdigestFile, $realm);

    if (isset($users[$username])) {
        $expectedHash = md5($username . ':' . $realm . ':' . $password);
        if ($users[$username] === $expectedHash) {
            $_SESSION['user'] = $username;
            header('Location: index.php');
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Configuración de Nagios</title>
    <link rel="stylesheet" href="styles.css?v=2">
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="post">
        <label>Usuario:</label>
        <input type="text" name="username" required>

        <label>Contraseña:</label>
        <input type="password" name="password" required>

        <input type="submit" value="Entrar">
    </form>
</body>
</html>
