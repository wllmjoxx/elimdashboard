<?php

namespace App\Modules\Gallery\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores an uploaded photo compressed (PRD 4.8 "kompres otomatis"): the
 * longest side is reduced and the file saved as WebP, plus a thumbnail.
 * Without the GD extension, or when GD cannot read the file, the original
 * is kept and used for both sizes.
 */
class PhotoProcessor
{
    /**
     * @return array{image: string, thumbnail: string, width: int|null, height: int|null}
     */
    public function store(UploadedFile $file, string $folder): array
    {
        $disk = Storage::disk(config('modules.gallery.disk'));
        $directory = trim(config('modules.gallery.directory').'/'.$folder, '/');
        $size = @getimagesize($file->getRealPath()) ?: [null, null];

        $source = $this->canProcess() ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;

        if (! $source instanceof GdImage) {
            $path = $file->store($directory, config('modules.gallery.disk'));

            return ['image' => $path, 'thumbnail' => $path, 'width' => $size[0], 'height' => $size[1]];
        }

        $name = Str::random(40);
        [$extension, $encode] = $this->encoder();
        $config = config('modules.gallery.compress');

        $large = $this->resize($source, (int) $config['max_side']);
        $thumb = $this->resize($source, (int) $config['thumb_side']);

        $image = "{$directory}/{$name}.{$extension}";
        $thumbnail = "{$directory}/{$name}-thumb.{$extension}";

        $disk->put($image, $encode($large, (int) $config['quality']));
        $disk->put($thumbnail, $encode($thumb, (int) $config['quality']));

        return ['image' => $image, 'thumbnail' => $thumbnail, 'width' => imagesx($large), 'height' => imagesy($large)];
    }

    public function canProcess(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromstring');
    }

    private function resize(GdImage $source, int $maxSide): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxSide / max($width, $height));

        if ($scale >= 1) {
            return $source;
        }

        $target = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);

        return $target;
    }

    /**
     * @return array{0: string, 1: callable(GdImage, int): string}
     */
    private function encoder(): array
    {
        $capture = function (callable $write): string {
            ob_start();
            $write();

            return (string) ob_get_clean();
        };

        if (function_exists('imagewebp')) {
            return ['webp', fn (GdImage $image, int $quality) => $capture(fn () => imagewebp($image, null, $quality))];
        }

        return ['jpg', fn (GdImage $image, int $quality) => $capture(fn () => imagejpeg($image, null, $quality))];
    }
}
