<?php

namespace AppTank\Horus\Core\File;

/**
 * Class MimeType
 *
 * Class that contains the mime types for different file types.
 *
 * @package AppTank\Horus\Core\File
 *
 * @author John Ospina
 * Year: 2024
 */
class MimeType
{
    public const array IMAGES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        'image/tiff',
        'image/x-icon',
        'image/vnd.microsoft.icon',
        'image/vnd.wap.wbmp',
        'image/x-xbitmap',
        'image/x-portable-bitmap',
        'image/x-pixmap',
        'image/x-portable-graymap',
        'image/heic' // High Efficiency Image Format (APPLE)
    ];

    public const array AUDIOS = [
        'audio/midi',
        'audio/mpeg',
        'audio/webm',
        'audio/ogg',
        'audio/wav',
        'audio/x-wav',
        'audio/x-pn-realaudio',
        'audio/x-pn-realaudio-plugin',
        'audio/x-realaudio',
        'audio/x-aiff',
        'audio/x-mpegurl',
        'audio/x-scpls',
        'audio/x-ms-wax',
        'audio/x-ms-wma',
        'audio/xm',
        'audio/x-mod',
        'audio/x-s3m',
        'audio/x-it',
        'audio/x-gsm',
        'audio/x-flac',
        'audio/x-mp3',
        'audio/x-mpeg',
        'audio/x-mpeg-3',
        'audio/x-mpeg3',
        'audio/x-mpg',
        'audio/x-mpg3',
        'audio/x-mpegaudio',
        'audio/x-mpg',
        'audio/x-mpg3',
        'audio/x-mpeg',
        'audio/x-mpeg-3',
        'audio/x-mpeg3',
        'audio/x-mp3'];

    public const array PDF = [
        'application/pdf'
    ];

}