<?php
return [
    'project_id' => env('FIREBASE_PROJECT_ID', 'kamgus'),
    // Path to service account JSON. Default: storage/app/firebase/ (NOT publicly accessible).
    // Override with FIREBASE_CREDENTIALS_PATH env var if needed.
    'firebase_credentials' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase/service-account.json')),
    'authentication_key' => env('AUTHENTICATION_KEY_FIREBASE')

];
