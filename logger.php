<?php
function logActivity($user, $action) {
    $file = __DIR__ . '/activity.log';
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logLine = "[$date] [$ip] Usuario: $user - Acción: $action" . PHP_EOL;
    file_put_contents($file, $logLine, FILE_APPEND);
}
?>
