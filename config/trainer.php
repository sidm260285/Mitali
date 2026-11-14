<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trainer Document Storage Disk
    |--------------------------------------------------------------------------
    |
    | Disk defined in config/filesystems.php. Trainer files are stored
    | privately in the S3 bucket configured by AWS_BUCKET.
    |
    */

    'storage_disk' => env('TRAINER_STORAGE_DISK', 'trainer_uploads'),

    /*
    |--------------------------------------------------------------------------
    | Image Max Dimension (pixels)
    |--------------------------------------------------------------------------
    |
    | After upload, images are resized so neither width nor height exceeds
    | this value. Aspect ratio is preserved.
    |
    */

    'image_max_dimension' => (int) env('TRAINER_IMAGE_MAX_DIMENSION', 900),

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size (megabytes)
    |--------------------------------------------------------------------------
    |
    | PDFs are rejected above this limit. Images must also be within this
    | limit after resizing.
    |
    */

    'max_file_size_mb' => (int) env('TRAINER_MAX_FILE_SIZE_MB', 2),

];
