<?php

return [

    'max_upload_kb' => (int) env('CCTV_MAX_UPLOAD_KB', 2048),

    'chunk_max_kb' => (int) env('CCTV_CHUNK_MAX_KB', 51200),

    'disk' => env('CCTV_DISK', 'cctv'),

    'admin_name' => env('ADMIN_NAME', 'Admin'),

    'admin_email' => env('ADMIN_EMAIL'),

    'admin_password' => env('ADMIN_PASSWORD'),

];
