<?php

return [

    /*
     * The disk on which to store added files.
     * Choose one or more of the disks you've configured in config/filesystems.php.
     */
    'disk' => env('GENIE_DISK', 'public'),

    /*
     * Indicate that the uploaded file should be no more than the given number of kilobytes.
     * Adding a larger file will result in an exception.
     */
    'max_file_size' => [
        'image' => 1024 * 15, // 15MB
        'gif' => 1024 * 15, // 15MB
        'video' => 1024 * 200 // 200MB
    ],

    /*
     * Accepted mime types for media library upload.
     * These are all supported mime types for the image and video files. We do not guarantee that it will work with other types.
     * If you need to remove certain mime types, you are free to do so from here.
     */
    'mime_types' => [
        'image/jpg',
        'image/jpeg',
        'image/gif',
        'image/png',
        'video/mp4',
        'video/x-m4v'
    ],

    /*
     * Define cache prefix
     */
    'cache_prefix' => env('GENIE_CACHE_PREFIX', 'genie')
];

