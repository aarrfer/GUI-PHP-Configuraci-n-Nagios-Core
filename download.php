<?php
session_start();
require_once 'logger.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

//control acceso
logActivity($_SESSION['user'], 'Accedió a download.php');

$basePath = "/etc/nagios4/";
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("No se especificó archivo.");
}

$file = $_GET['file'];
$filePath = realpath($basePath . '/' . $file);

//Seguridad: evitar acceder fuera del directorio base
if ($filePath === false || strpos($filePath, realpath($basePath)) !== 0 || !is_file($filePath)) {
    die("Archivo inválido.");
}

//Forzar descarga
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
