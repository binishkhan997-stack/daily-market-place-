<?php
// health.php - For AWS Elastic Beanstalk health checks
require_once 'config/database.php';

$status = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'database' => 'connected',
    'environment' => getenv('APP_ENV') ?: 'development'
];

// Check database connection
try {
    $stmt = $pdo->query("SELECT 1");
    if ($stmt->fetch()) {
        $status['database'] = 'connected';
    }
} catch (Exception $e) {
    $status['database'] = 'disconnected';
    $status['status'] = 'unhealthy';
}

// Check required extensions
$required_extensions = ['pdo', 'mysqli', 'json', 'gd', 'curl'];
$missing = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}
if (!empty($missing)) {
    $status['status'] = 'unhealthy';
    $status['missing_extensions'] = $missing;
}

header('Content-Type: application/json');
echo json_encode($status, JSON_PRETTY_PRINT);
?>
