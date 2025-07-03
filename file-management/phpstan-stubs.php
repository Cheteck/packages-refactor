<?php

namespace Intervention\Image;

class Image
{
    public function orientate(): static { return $this; }

    public function fit(?int $width = null, ?int $height = null, ?callable $callback = null, string $position = 'center'): static { return $this; }

    public function resize(?int $width = null, ?int $height = null, ?callable $callback = null): static { return $this; }

    public function encode(?string $format = null, ?int $quality = null): string { return ''; }
}

class ImageManagerStatic
{
    public static function make(mixed $data = null): Image { return new Image(); }
}
