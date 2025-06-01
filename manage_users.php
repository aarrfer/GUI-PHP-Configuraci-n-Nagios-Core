<?php
session_start();
require_once 'logger.php';
// Solo admin puede entrar
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    echo "Acceso denegado.";
    exit;
}

//control acceso
logActivity($_SESSION['user'], 'Accedió a manage_users.php');

$htdigestFile = '/etc/nagios4/htdigest.users';
$realm = 'Nagios4';

//Función para cargar usuarios
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

//Añadir usuario
if (isset($_POST['add_user'])) {
    $newUser = trim($_POST['new_user']);
    $newPass = trim($_POST['new_pass']);
    if ($newUser && $newPass) {
        $hash = md5($newUser . ':' . $realm . ':' . $newPass);
        file_put_contents($htdigestFile, "$newUser:$realm:$hash\n", FILE_APPEND);
        $message = "Usuario añadido correctamente.";
    }
}

//Borrar usuario
if (isset($_POST['delete_user'])) {
    $deleteUser = $_POST['delete_user'];
    $lines = file($htdigestFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newLines = [];
    foreach ($lines as $line) {
        list($user, $fileRealm, $hash) = explode(':', $line);
        if ($user !== $deleteUser) {
            $newLines[] = $line;
        }
    }
    file_put_contents($htdigestFile, implode("\n", $newLines) . "\n");
    $message = "Usuario eliminado correctamente.";
}

$users = getUsers($htdigestFile, $realm);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Usuarios - Nagios</title>
    <link rel="stylesheet" href="styles.css?v=2">
</head>
<body>
    <h1>Administrar Usuarios</h1>

    <p><a href="index.php">Volver</a></p>

    <?php if (isset($message)) echo "<p><strong>$message</strong></p>"; ?>

    <h2>Usuarios Actuales</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>Usuario</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($users as $username => $hash): ?>
        <tr>
            <td><?php echo htmlspecialchars($username); ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_user" value="<?php echo htmlspecialchars($username); ?>">
                    <input type="submit" value="Eliminar" onclick="return confirm('¿Seguro que quieres eliminar este usuario?');">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>Añadir Usuario Nuevo</h2>
    <form method="post">
        <label>Nombre de usuario:</label>
        <input type="text" name="new_user" required>
        <label>Contraseña:</label>
        <input type="password" name="new_pass" required>
        <input type="submit" name="add_user" value="Añadir Usuario">
    </form>

</body>
</html>
