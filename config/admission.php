<?php

return [

    'storage_disk' => env('ADMISSION_STORAGE_DISK', 'admission_uploads'),

    'photo_max_dimension' => (int) env('ADMISSION_PHOTO_MAX_DIMENSION', 800),

    'document_max_dimension' => (int) env('ADMISSION_DOCUMENT_MAX_DIMENSION', 1200),

    'max_upload_size_mb' => (int) env('ADMISSION_MAX_UPLOAD_SIZE_MB', 5),

    'jpeg_quality' => (int) env('ADMISSION_JPEG_QUALITY', 80),

    'target_file_size_kb' => (int) env('ADMISSION_TARGET_FILE_SIZE_KB', 500),

];
