<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

echo "FIREBASE_PROJECT_ID (env): " . env('FIREBASE_PROJECT_ID') . PHP_EOL;
echo "FIREBASE_CREDENTIALS (env): " . env('FIREBASE_CREDENTIALS') . PHP_EOL;
echo "FIREBASE_PROJECT (env): " . env('FIREBASE_PROJECT') . PHP_EOL;

echo "\nFrom config:" . PHP_EOL;
echo "firebase.projects.app.project_id: " . app('config')->get('firebase.projects.app.project_id') . PHP_EOL;
echo "firebase.projects.app.credentials: " . app('config')->get('firebase.projects.app.credentials') . PHP_EOL;
echo "firebase.default: " . app('config')->get('firebase.default') . PHP_EOL;
