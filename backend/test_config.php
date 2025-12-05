<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

$firebase_config = config('firebase');

echo "Default project: " . ($firebase_config['default'] ?? 'NOT SET') . "\n";
echo "Project config exists: " . (isset($firebase_config['projects']['app']) ? 'YES' : 'NO') . "\n";

if (isset($firebase_config['projects']['app'])) {
    $app_config = $firebase_config['projects']['app'];
    echo "Project ID: " . ($app_config['project_id'] ?? 'NOT SET') . "\n";
    echo "Credentials: " . ($app_config['credentials'] ?? 'NOT SET') . "\n";
}
