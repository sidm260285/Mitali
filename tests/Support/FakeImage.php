<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

class FakeImage
{
    public static function jpeg(string $name = 'test.jpg'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'fakejpeg');
        file_put_contents($path, self::minimalJpegBytes());

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    public static function minimalJpegBytes(): string
    {
        return base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k='
        );
    }
}
