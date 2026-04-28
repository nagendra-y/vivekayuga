<?php

$config = [
    'admin_password_hash' => password_hash('admin123', PASSWORD_DEFAULT),

    'max_file_size_mb' => 10,

    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],

    'content_folder' => 'nano_photos_content',

    'session_timeout_minutes' => 60,

    'max_files_per_upload' => 50,

    'thumbnail_folder_name' => '_thumbnails',

    'jpeg_quality' => 85,

    'create_thumbnails_on_upload' => true,

    'thumbnail_max_width' => 400,
    'thumbnail_max_height' => 400,

    'max_filename_length' => 200,
];

return $config;
