<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait UploadAble
{
    
    public function uploadFile(UploadedFile $file, string $folder, ?string $disk = null): string|bool
    {
        $disk = $disk ?? config('filesystems.default');
        return $file->store($folder, $disk);
    }

    public function deleteFile(?string $path, ?string $disk = null): bool
    {
        $disk = $disk ?? config('filesystems.default');
        if ($path && Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        return false;
    }
}
