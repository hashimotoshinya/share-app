<?php

use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Factory;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/firebase-test', function () {
    $factory = (new Factory)->withServiceAccount(config('firebase.projects.app.credentials'))
                            ->withDatabaseUri(config('firebase.projects.app.database.url'));

    $database = $factory->createDatabase();

    $newPost = $database
        ->getReference('test/data')
        ->set(['message' => 'Hello from Laravel!']);

    return '✅ Firebase write success!';
});
