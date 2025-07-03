<?php

namespace IJIDeals\FileManagement\Services;

use App\Enums\MediaType;
use IJIDeals\FileManagement\Exceptions\FileCombinationException;
use IJIDeals\FileManagement\Exceptions\FileStorageException;
use IJIDeals\FileManagement\Exceptions\FileValidationException;
use IJIDeals\FileManagement\Models\Attachment;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class UploadService
 */
class UploadService
{
    protected FilesystemFactory $storage;

    /**
     * UploadService constructor.
     */
    public function __construct(FilesystemFactory $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Uploads a file and creates an Attachment record.
     *
     *
     * @throws FileValidationException
     * @throws FileStorageException
     */
    public function upload(
        UploadedFile $file,
        ?Model $attachable = null,
        ?User $user = null,
        ?string $disk = null,
        array $options = []
    ): Attachment {
        $this->validateFile($file, $file->getClientOriginalName(), $file->getMimeType(), $file->getSize());

        $diskName = $disk ?? config('file-management.default_disk', 'public');
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $newFilename = (string) Str::uuid().($extension ? '.'.$extension : '');

        $uploadPathDir = rtrim(config('file-management.base_directory', 'uploads'), '/').'/'.date('Y/m');

        $storedPath = $this->storage->disk($diskName)->putFileAs($uploadPathDir, $file, $newFilename);

        if (! $storedPath) {
            throw new FileStorageException("Failed to store file '{$originalFilename}'.");
        }

        return $this->createAttachmentRecord(
            $user,
            $attachable,
            $diskName,
            $storedPath,
            $newFilename,
            $originalFilename,
            $file->getMimeType(),
            $file->getSize(),
            $options
        );
    }

    /**
     * Handles a single chunk upload.
     *
     *
     * @throws FileValidationException
     * @throws FileStorageException
     */
    public function handleChunk(Request $request, ?User $user = null, ?string $disk = null): array
    {
        $file = $request->file('file');
        $chunkNumber = (int) $request->input('resumableChunkNumber');
        $totalChunks = (int) $request->input('resumableTotalChunks');
        $identifier = $request->input('resumableIdentifier');
        $originalFilename = $request->input('resumableFilename');
        $totalSize = (int) $request->input('resumableTotalSize');

        if (! $file || ! $file->isValid()) {
            throw new FileValidationException('Invalid file chunk uploaded.');
        }

        if ($chunkNumber === 1) {
            $this->validateFile(null, $originalFilename, $file->getMimeType(), $totalSize, true);
        }

        $tempDisk = 'local';
        $chunkDir = 'chunks/'.$identifier;
        $chunkPath = $chunkDir.'/'.$chunkNumber.'.part';

        if (! $this->storage->disk($tempDisk)->put($chunkPath, $file->get())) {
            throw new FileStorageException("Failed to store chunk #{$chunkNumber} for '{$identifier}'.");
        }

        $allChunksUploaded = $this->verifyAllChunksExist($tempDisk, $chunkDir, $totalChunks);

        return [
            'status' => 'chunk_uploaded',
            'chunk_number' => $chunkNumber,
            'all_chunks_uploaded' => $allChunksUploaded,
            'resumableIdentifier' => $identifier,
        ];
    }

    /**
     * Combines chunks into a single file and creates an Attachment record.
     *
     *
     * @throws FileCombinationException
     * @throws FileNotFoundException
     * @throws FileValidationException
     * @throws FileStorageException
     */
    public function combineChunks(
        string $uploadId,
        string $originalFilename,
        int $totalChunks,
        ?string $finalDisk = null,
        ?Model $attachable = null,
        ?User $user = null,
        array $options = []
    ): Attachment {
        $tempDisk = 'local';
        $chunkDir = 'chunks/'.$uploadId;

        if (! $this->verifyAllChunksExist($tempDisk, $chunkDir, $totalChunks)) {
            throw new FileCombinationException("Not all chunks found for upload ID '{$uploadId}'. Cannot combine.");
        }

        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $newFilename = (string) Str::uuid().($extension ? '.'.$extension : '');

        $tempCombinedFilePath = $chunkDir.'/'.$newFilename;
        $outputPath = $this->storage->disk($tempDisk)->path($tempCombinedFilePath);

        $outputStream = fopen($outputPath, 'w+');
        if ($outputStream === false) {
            throw new FileCombinationException("Unable to open stream for combined file '{$tempCombinedFilePath}'.");
        }

        $finalFileSize = 0;
        $finalMimeType = null;

        try {
            for ($i = 1; $i <= $totalChunks; $i++) {
                $chunkPath = $chunkDir.'/'.$i.'.part';
                $chunkStream = $this->storage->disk($tempDisk)->readStream($chunkPath);
                if ($chunkStream === false) {
                    throw new FileNotFoundException("Chunk #{$i} not found at '{$chunkPath}'.");
                }
                if ($i === 1) {
                    $finalMimeType = mime_content_type(storage_path('app/'.$chunkPath));
                }
                while (! feof($chunkStream)) {
                    $buffer = fread($chunkStream, 8192);
                    if ($buffer === false) {
                        fclose($chunkStream);
                        throw new FileCombinationException("Error reading chunk #{$i}.");
                    }
                    fwrite($outputStream, $buffer);
                    $finalFileSize += strlen($buffer);
                }
                fclose($chunkStream);
            }
        } finally {
            fclose($outputStream);
        }

        $this->validateFile(null, $originalFilename, $finalMimeType, $finalFileSize, false);

        $finalDiskName = $finalDisk ?? config('file-management.default_disk', 'public');
        $uploadPathDir = rtrim(config('file-management.base_directory', 'uploads'), '/').'/'.date('Y/m');

        $finalStoredPath = Storage::disk($finalDiskName)
            ->putFileAs($uploadPathDir, new \Illuminate\Http\File($outputPath), $newFilename);

        if (! $finalStoredPath) {
            $this->storage->disk($tempDisk)->delete($tempCombinedFilePath);
            throw new FileStorageException("Failed to move combined file to final storage for '{$originalFilename}'.");
        }

        $this->storage->disk($tempDisk)->deleteDirectory($chunkDir);

        return $this->createAttachmentRecord(
            $user,
            $attachable,
            $finalDiskName,
            $finalStoredPath,
            $newFilename,
            $originalFilename,
            $finalMimeType,
            $finalFileSize,
            $options
        );
    }

    /**
     * Vérifie si tous les chunks existent pour un upload donné.
     */
    protected function verifyAllChunksExist(string $disk, string $chunkDir, int $totalChunks): bool
    {
        for ($i = 1; $i <= $totalChunks; $i++) {
            if (! $this->storage->disk($disk)->exists($chunkDir.'/'.$i.'.part')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Crée un enregistrement Attachment en base de données.
     */
    protected function createAttachmentRecord(
        ?User $user,
        ?Model $attachable,
        string $disk,
        string $filepath,
        string $filename,
        string $originalFilename,
        ?string $mimetype,
        ?int $sizeBytes,
        array $options = []
    ): Attachment {
        $attachmentData = [
            'user_id' => $user?->id ?? (Auth::check() ? Auth::id() : null),
            'attachable_id' => $attachable?->id,
            'attachable_type' => $attachable ? get_class($attachable) : null,
            'disk' => $disk,
            'filepath' => $filepath,
            'filename' => $filename,
            'mimetype' => $mimetype,
            'type' => $this->determineMediaTypeFromMime($mimetype),
            'size_bytes' => $sizeBytes,
            'metadata' => array_merge($options['metadata'] ?? [], ['original_filename' => $originalFilename]),
            'title' => $options['title'] ?? pathinfo($originalFilename, PATHINFO_FILENAME),
            'description' => $options['description'] ?? null,
        ];

        return Attachment::create($attachmentData);
    }

    /**
     * Valide le fichier (UploadedFile ou fichier combiné).
     *
     * @throws FileValidationException
     */
    protected function validateFile(
        ?UploadedFile $file,
        string $originalFilename,
        ?string $mimeType,
        ?int $filesize,
        bool $isChunk = false
    ): void {
        if ($file && ! $file->isValid() && ! $isChunk) {
            throw new FileValidationException('Invalid file upload: '.$file->getErrorMessage());
        }
        if ($isChunk && $file && ! $file->isValid()) {
            throw new FileValidationException('Invalid file chunk upload: '.$file->getErrorMessage());
        }

        $effectiveMimeType = $file ? $file->getMimeType() : $mimeType;

        $allowedMimes = config('file-management.allowed_mime_types', []);
        if (! empty($allowedMimes) && $effectiveMimeType && ! in_array($effectiveMimeType, $allowedMimes, true)) {
            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            $allowedByExtension = false;
            foreach ($allowedMimes as $allowedMime) {
                if (Str::endsWith($allowedMime, $extension)) {
                    $allowedByExtension = true;
                    break;
                }
            }
            if (! $allowedByExtension) {
                throw new FileValidationException("Invalid file type: {$effectiveMimeType} (from file '{$originalFilename}').");
            }
        }

        $maxSizeKb = config('file-management.max_upload_size_kb', 2048);
        if ($filesize === null && $file) {
            $filesize = $file->getSize();
        }

        if ($filesize !== null && $filesize > ($maxSizeKb * 1024)) {
            throw new FileValidationException("File '{$originalFilename}' is too large ({$filesize} bytes). Maximum size is {$maxSizeKb}KB.");
        }
    }

    /**
     * Détermine le type de média à partir du mime type.
     */
    protected function determineMediaTypeFromMime(?string $mimeType): MediaType
    {
        if (empty($mimeType)) {
            return MediaType::UNKNOWN;
        }

        if (Str::startsWith($mimeType, 'image/')) {
            return MediaType::IMAGE;
        }

        if (Str::startsWith($mimeType, 'video/')) {
            return MediaType::VIDEO;
        }

        if (Str::startsWith($mimeType, 'audio/')) {
            return MediaType::AUDIO;
        }

        if (Str::startsWith($mimeType, 'text/')) {
            return MediaType::TEXT;
        }

        if (Str::contains($mimeType, [
            '/pdf',
            '/msword',
            '/vnd.openxmlformats-officedocument.wordprocessingml',
            '/vnd.ms-excel',
            '/vnd.openxmlformats-officedocument.spreadsheetml',
            '/vnd.ms-powerpoint',
            '/vnd.openxmlformats-officedocument.presentationml',
        ])) {
            return MediaType::DOCUMENT;
        }

        if (Str::contains($mimeType, [
            '/zip',
            '/x-rar-compressed',
            '/x-tar',
            '/gzip',
        ])) {
            return MediaType::ARCHIVE;
        }

        return MediaType::UNKNOWN;
    }
}
