<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base Directory for Uploads
    |--------------------------------------------------------------------------
    |
    | This is the base directory under which files will be stored on the
    | 'default_disk'. The UploadService might append further structures
    | (e.g., date-based folders or model-specific paths) to this base.
    |
    */
    'base_directory' => env('FILE_MANAGEMENT_BASE_DIR', 'uploads'),

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | This option defines the default storage disk where files will be uploaded.
    | You can set this to any of the disks defined in your `config/filesystems.php`
    | file (e.g., 'public', 's3', 'local').
    |
    */
    'default_disk' => env('FILE_MANAGEMENT_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | Specify the MIME types that are allowed for upload. This helps in
    | restricting the types of files users can upload. An empty array
    | would mean all MIME types are allowed (not recommended).
    |
    */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword', // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/vnd.ms-excel', // .xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'text/plain', // .txt
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload File Size (KB)
    |--------------------------------------------------------------------------
    |
    | Define the maximum file size for uploads in kilobytes (KB).
    | This value will be used to validate uploaded files.
    | For example, 2048 KB = 2MB.
    |
    */
    'max_upload_size_kb' => env('FILE_MANAGEMENT_MAX_SIZE_KB', 5120), // 5MB default

    /*
    |--------------------------------------------------------------------------
    | Image Processing Settings
    |--------------------------------------------------------------------------
    |
    | Configure options related to image processing. These settings will be
    | utilized by the MediaService for operations like auto-orientation,
    | setting default quality, and defining thumbnail presets.
    |
    */
    'image_processing' => [

        /*
        |----------------------------------------------------------------------
        | Auto-orient Images
        |----------------------------------------------------------------------
        |
        | If true, images will be automatically oriented based on their EXIF
        | data during processing. This is useful for correcting rotation issues
        | from cameras and mobile devices.
        |
        */
        'auto_orient' => true,

        /*
        |----------------------------------------------------------------------
        | Default Image Quality
        |----------------------------------------------------------------------
        |
        | Set the default quality for processed images (0-100). This affects
        | the compression level for formats like JPEG.
        |
        */
        'default_quality' => 90,

        /*
        |----------------------------------------------------------------------
        | Thumbnail Presets
        |----------------------------------------------------------------------
        |
        | Define various thumbnail presets for automatic generation.
        | Each preset should specify dimensions and cropping behavior.
        | - 'width': Target width in pixels.
        | - 'height': Target height in pixels.
        | - 'crop': Boolean, true to crop to exact dimensions, false to resize
        |           while maintaining aspect ratio (fit within dimensions).
        | - 'quality': (Optional) Override default quality for this preset.
        |
        */
        'thumbnail_presets' => [
            'small' => [
                'width' => 150,
                'height' => 150,
                'crop' => true,
                'quality' => 85,
            ],
            'medium' => [
                'width' => 600,
                'height' => 600,
                'crop' => false, // Fit within, maintaining aspect ratio
                'quality' => 90,
            ],
            'large' => [
                'width' => 1200,
                'height' => 1200,
                'crop' => false,
                'quality' => 90,
            ],
        ],
    ],

];
