<?php

namespace IJIDeals\FileManagement\Services;

use IJIDeals\FileManagement\Models\Attachment;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image; // Use static proxy for Intervention

/**
 * Class MediaService
 *
 * Handles media manipulations like resizing, cropping, and optimization.
 */
class MediaService
{
    protected FilesystemFactory $storage;

    /**
     * MediaService constructor.
     */
    public function __construct(FilesystemFactory $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Placeholder for resizing an image.
     *
     * In a real implementation, this method would:
     * 1. Get the original image from storage using $attachment->disk and $attachment->filepath.
     * 2. Use an image manipulation library (e.g., Intervention Image, Spatie MediaLibrary)
     *    to resize the image.
     * 3. Store the resized image (e.g., in a new path or by overwriting, depending on strategy).
     * 4. Potentially update the Attachment model or create a new one for the variation.
     *
     * @param  Attachment  $attachment  The attachment record of the image to resize.
     * @param  int  $width  The target width.
     * @param  int  $height  The target height.
     * @param  array  $options  Additional options for resizing (e.g., aspect ratio, quality).
     * @return string|null Path to the resized image or null if operation failed/not implemented.
     */
    public function resize(Attachment $attachment, int $width, int $height, array $options = []): ?string
    {
        Log::info("[MediaService] Placeholder: Resize called for attachment ID {$attachment->id} to {$width}x{$height}.", $options);

        // In a real implementation, this would return the new path of the resized image.
        // return $this->storage->disk($attachment->disk)->url($attachment->filepath);
        return $this->storage->disk($attachment->disk)->url($attachment->filepath);
    }

    /**
     * Placeholder for cropping an image.
     *
     * Similar to resize, this would involve:
     * 1. Fetching the image.
     * 2. Using a library to perform the crop operation.
     * 3. Storing the cropped image.
     * 4. Updating database records as necessary.
     *
     * @param  Attachment  $attachment  The attachment record of the image to crop.
     * @param  int  $x  The x-coordinate of the top-left corner of the crop.
     * @param  int  $y  The y-coordinate of the top-left corner of the crop.
     * @param  int  $width  The width of the crop area.
     * @param  int  $height  The height of the crop area.
     * @param  array  $options  Additional options for cropping.
     * @return string|null Path to the cropped image or null.
     */
    public function crop(Attachment $attachment, int $x, int $y, int $width, int $height, array $options = []): ?string
    {
        Log::info("[MediaService] Placeholder: Crop called for attachment ID {$attachment->id} with dimensions {$width}x{$height} at ({$x},{$y}).", $options);

        // return $this->storage->disk($attachment->disk)->url($attachment->filepath);
        return $this->storage->disk($attachment->disk)->url($attachment->filepath);
    }

    /**
     * Placeholder for optimizing an image.
     *
     * This would typically involve:
     * 1. Using tools/libraries (e.g., jpegoptim, optipng, Spatie Image Optimizer)
     *    to reduce file size without significant quality loss.
     * 2. The optimized image might replace the original or be stored as a new version.
     *
     * @param  Attachment  $attachment  The attachment record of the image to optimize.
     * @param  array  $options  Additional options for optimization (e.g., quality settings).
     * @return string|null Path to the optimized image or null.
     */
    public function optimize(Attachment $attachment, array $options = []): ?string
    {
        Log::info("[MediaService] Placeholder: Optimize called for attachment ID {$attachment->id}.", $options);

        // return $this->storage->disk($attachment->disk)->url($attachment->filepath);
        return $this->storage->disk($attachment->disk)->url($attachment->filepath);
    }

    /**
     * Generates different responsive image versions (thumbnails, breakpoints).
     * This is a more complex operation that might orchestrate multiple resizes.
     *
     * @param  array  $presetKeys  Defined preset keys for responsive versions (e.g., 'thumb', 'medium', 'large')
     * @return array An array of paths or URLs to the generated image versions.
     */
    public function generateResponsiveVersions(Attachment $attachment, array $presetKeys = []): array
    {
        if (! Str::startsWith($attachment->mimetype ?? '', 'image/')) {
            Log::warning("[MediaService] Attempted to generate responsive versions for non-image attachment ID {$attachment->id}.");

            return [];
        }

        $allPresets = config('file-management.image_processing.thumbnail_presets', []);
        $targetPresets = [];

        if (empty($presetKeys)) {
            $targetPresets = $allPresets;
        } else {
            foreach ($presetKeys as $key) {
                if (isset($allPresets[$key])) {
                    $targetPresets[$key] = $allPresets[$key];
                } else {
                    Log::warning("[MediaService] Preset '{$key}' not found in configuration for attachment ID {$attachment->id}.");
                }
            }
        }

        if (empty($targetPresets)) {
            Log::info("[MediaService] No valid presets to generate for attachment ID {$attachment->id}.");

            return [];
        }

        $versions = [];
        $originalFileContent = null;

        try {
            $originalFileContent = $this->storage->disk($attachment->disk)->get($attachment->filepath);
        } catch (\Exception $e) {
            Log::error("[MediaService] Failed to read original file for attachment ID {$attachment->id}: ".$e->getMessage());

            return [];
        }

        if (! $originalFileContent) {
            Log::error("[MediaService] Original file content is empty for attachment ID {$attachment->id}.");

            return [];
        }

        foreach ($targetPresets as $presetName => $config) {
            try {
                $img = Image::make($originalFileContent);

                if (config('file-management.image_processing.auto_orient', true)) {
                    $img->orientate();
                }

                $width = $config['width'] ?? null;
                $height = $config['height'] ?? null;
                $crop = $config['crop'] ?? false;
                $quality = $config['quality'] ?? config('file-management.image_processing.default_quality', 90);

                if ($crop) {
                    $img->fit($width, $height, function ($constraint) {
                        // $constraint->upsize(); // Prevent upsizing if source is smaller
                    });
                } else {
                    $img->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        // $constraint->upsize(); // Prevent upsizing
                    });
                }

                // Define thumbnail path structure
                $pathParts = pathinfo($attachment->filepath);
                $thumbnailDirectory = ($pathParts['dirname'] === '.' ? '' : $pathParts['dirname'].'/').'thumbnails/'.$presetName;
                $thumbnailFilename = $pathParts['basename']; // Keep original filename for the thumb
                $thumbnailPath = $thumbnailDirectory.'/'.$thumbnailFilename;

                $encodedImage = (string) $img->encode(null, $quality); // Auto-detects format from original, applies quality

                $this->storage->disk($attachment->disk)->put($thumbnailPath, $encodedImage);
                $versions[$presetName] = $thumbnailPath; // Store relative path
                Log::info("[MediaService] Generated responsive version '{$presetName}' for attachment ID {$attachment->id} at {$thumbnailPath}.");

            } catch (\Exception $e) {
                Log::error("[MediaService] Failed to generate preset '{$presetName}' for attachment ID {$attachment->id}: ".$e->getMessage());
                $versions[$presetName] = null;
            }
        }

        return $versions;
    }

    /**
     * Generates a thumbnail for an attachment based on a named preset.
     *
     * @param  string  $presetName  Key of the preset in config('file-management.image_processing.thumbnail_presets')
     * @return string|null Path to the generated thumbnail or null on failure.
     */
    public function generateThumbnail(Attachment $attachment, string $presetName = 'small'): ?string
    {
        if (! Str::startsWith($attachment->mimetype ?? '', 'image/')) {
            Log::warning("[MediaService] Cannot generate thumbnail for non-image attachment ID {$attachment->id}.");

            return null;
        }

        $presetConfig = config("file-management.image_processing.thumbnail_presets.{$presetName}");

        if (! $presetConfig) {
            Log::error("[MediaService] Thumbnail preset '{$presetName}' not found for attachment ID {$attachment->id}.");

            return null;
        }

        try {
            $originalFileContent = $this->storage->disk($attachment->disk)->get($attachment->filepath);
            if (! $originalFileContent) {
                Log::error("[MediaService] Original file content is empty for attachment ID {$attachment->id} for preset {$presetName}.");

                return null;
            }
            $img = Image::make($originalFileContent);

            if (config('file-management.image_processing.auto_orient', true)) {
                $img->orientate();
            }

            $width = $presetConfig['width'] ?? null;
            $height = $presetConfig['height'] ?? null;
            $crop = $presetConfig['crop'] ?? false;
            $quality = $presetConfig['quality'] ?? config('file-management.image_processing.default_quality', 90);

            if ($crop) {
                $img->fit($width, $height, function ($constraint) {
                    // $constraint->upsize(); // Prevent upsizing
                });
            } else {
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    // $constraint->upsize(); // Prevent upsizing
                });
            }

            // Define thumbnail path structure
            // e.g., original: path/to/image.jpg -> thumbnail: path/to/thumbnails/small/image.jpg
            $pathParts = pathinfo($attachment->filepath);
            $thumbnailDirectory = ($pathParts['dirname'] === '.' ? '' : $pathParts['dirname'].'/').'thumbnails/'.$presetName;
            $thumbnailFilename = $pathParts['basename'];
            $thumbnailPath = $thumbnailDirectory.'/'.$thumbnailFilename;

            $encodedImage = (string) $img->encode(null, $quality);

            $this->storage->disk($attachment->disk)->put($thumbnailPath, $encodedImage);
            Log::info("[MediaService] Thumbnail '{$presetName}' generated for attachment ID {$attachment->id} at {$thumbnailPath}.");

            return $thumbnailPath;

        } catch (\Exception $e) {
            Log::error("[MediaService] Failed to generate thumbnail '{$presetName}' for attachment ID {$attachment->id}: ".$e->getMessage());

            return null;
        }
    }

    // Removed getAttachmentUrl and getAttachmentThumbnailUrl as they duplicate Attachment model accessors
}
