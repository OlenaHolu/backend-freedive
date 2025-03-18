<?php

return [
    'credentials' => env('FIREBASE_CREDENTIALS') 
        ? json_decode(env('FIREBASE_CREDENTIALS'), true) // Railway (JSON en variable de entorno)
        : storage_path(env('FIREBASE_CREDENTIALS', 'storage/firebase_credentials.json')), // Local (archivo JSON)
];
