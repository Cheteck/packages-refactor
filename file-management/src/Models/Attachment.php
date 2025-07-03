<?php

namespace IJIDeals\FileManagement\Models;

use IJIDeals\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Assuming User model location
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // For getUrlAttribute error logging
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Schema(
 *     schema="Attachment",
 *     title="Attachment",
 *     description="Modèle pour la gestion des fichiers attachés",
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID unique de la pièce jointe"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         nullable=true,
 *         description="ID de l'utilisateur qui a téléversé la pièce jointe"
 *     ),
 *     @OA\Property(
 *         property="disk",
 *         type="string",
 *         description="Disque de stockage où le fichier est stocké (e.g., 'public', 's3')"
 *     ),
 *     @OA\Property(
 *         property="filepath",
 *         type="string",
 *         description="Chemin relatif du fichier sur le disque de stockage"
 *     ),
 *     @OA\Property(
 *         property="filename",
 *         type="string",
 *         description="Nom original du fichier téléversé par le client"
 *     ),
 *     @OA\Property(
 *         property="mimetype",
 *         type="string",
 *         nullable=true,
 *         description="Type MIME du fichier (e.g., 'image/jpeg', 'application/pdf')"
 *     ),
 *     @OA\Property(
 *         property="size_bytes",
 *         type="integer",
 *         format="int64",
 *         description="Taille du fichier en octets"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         nullable=true,
 *         description="Titre de la pièce jointe"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         description="Description de la pièce jointe"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         description="Catégorie générale du fichier (e.g., 'image', 'video', 'document')"
 *     ),
 *     @OA\Property(
 *         property="attachable_id",
 *         type="integer",
 *         format="int64",
 *         description="ID du modèle parent auquel la pièce jointe est attachée"
 *     ),
 *     @OA\Property(
 *         property="attachable_type",
 *         type="string",
 *         description="Type du modèle parent auquel la pièce jointe est attachée (polymorphique)"
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         nullable=true,
 *         description="Métadonnées supplémentaires du fichier (JSON)"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         format="url",
 *         readOnly=true,
 *         description="URL publique de la pièce jointe"
 *     ),
 *     @OA\Property(
 *         property="thumbnail_url",
 *         type="string",
 *         format="url",
 *         readOnly=true,
 *         description="URL de la miniature (pour les images)"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de création de la pièce jointe"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Date de dernière mise à jour de la pièce jointe"
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Date de suppression douce de la pièce jointe"
 *     )
 * )
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $disk
 * @property string $filepath
 * @property string $filename
 * @property string $mimetype
 * @property int $size
 * @property string|null $title
 * @property string|null $description
 * @property string|null $type
 * @property int $attachable_id
 * @property string $attachable_type
 * @property int $size_bytes
 * @property array|null $metadata
 * @property-read \IJIDeals\UserManagement\Models\User|null $user
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Attachment extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'attachable_id',
        'attachable_type',
        'user_id', // Uploader
        'disk',
        'filepath',
        'filename', // Original client filename
        'mimetype',
        'type' => \App\Enums\MediaType::class,     // General category: 'image', 'video', 'document', etc.
        'size_bytes' => 'integer',
        'metadata', // JSON for additional info
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size_bytes' => 'integer',
        'metadata' => 'array', // Automatically handles JSON to array and vice-versa
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // 'deleted_at' is handled by SoftDeletes
    ];

    /**
     * Get the parent attachable model (e.g., Post, Message, User profile, etc.).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the public URL for the attachment.
     */
    public function getUrlAttribute(): string
    {
        if ($this->disk && $this->filepath) {
            try {
                /** @var \Illuminate\Contracts\Filesystem\Filesystem $disk */
                $disk = Storage::disk($this->disk);

                return $disk->url($this->filepath);
            } catch (\Exception $e) {
                // Log error or return a default/placeholder URL if disk or file is missing
                Log::error("Error generating URL for attachment ID {$this->id} on disk '{$this->disk}': ".$e->getMessage());

                return asset('placeholder.jpg'); // Example placeholder
            }
        }

        return asset('placeholder.jpg'); // Fallback if disk/filepath is not set
    }

    /**
     * Append the URL and Thumbnail URL accessors to model arrays.
     *
     * @var array<int, string>
     */
    protected $appends = ['url', 'thumbnail_url']; // Added thumbnail_url

    /**
     * Validate the attachment data before creation or update.
     * (Typically called manually before mass assignment if not using Form Requests)
     *
     * @throws ValidationException
     */
    public static function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'attachable_id' => 'required', // morphs_id can be string or int depending on related model
            'attachable_type' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id', // Assumes users.id is PK, adjust if UUID.
            'disk' => 'required|string|max:255',
            'filepath' => 'required|string|max:1024', // Max path length
            'filename' => 'required|string|max:255',
            'mimetype' => 'nullable|string|max:255',
            'type' => 'required|string|max:50', // General category like 'image', 'document'
            'size_bytes' => 'required|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Deletes the physical file associated with this attachment from the storage disk.
     *
     * @return bool True if the file was deleted successfully or if it didn't exist, false on error.
     */
    public function deleteFile(): bool
    {
        if ($this->disk && $this->filepath && \Illuminate\Support\Facades\Storage::disk($this->disk)->exists($this->filepath)) {
            try {
                return \Illuminate\Support\Facades\Storage::disk($this->disk)->delete($this->filepath);
            } catch (\Exception $e) {
                Log::error("Failed to delete file for attachment ID {$this->id} from disk '{$this->disk}' at path '{$this->filepath}': ".$e->getMessage());

                return false; // Indicate failure
            }
        }

        // Optionally log if file was expected but not found, though this might be normal if record exists but file was lost
        // Log::info("File for attachment ID {$this->id} not found on disk '{$this->disk}' at path '{$this->filepath}' during deleteFile call.");
        return true; // Return true if file doesn't exist, as the goal (file is gone) is achieved.
        // Or change to false if "file not found" should be an error state for this method.
        // For consistency with Storage::delete, it returns true if file is gone or never existed.
    }

    /**
     * Override the default delete method to also remove the physical file.
     * This is called when $attachment->delete() is executed.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            // This event is triggered before the model is actually deleted from DB.
            // Useful if you want to ensure file is deleted before DB record is gone,
            // or if soft deleting, this will still run.
            // If hard deleting, an 'deleted' event could also be used, but 'deleting' is safer.
            if ($attachment->forceDeleting) { // Check if it's a force delete for soft-deleting models
                $attachment->deleteFile();
            } elseif (! $attachment->isSoftDeleting()) { // It's a hard delete on a non-soft-deleting model (though ours is)
                $attachment->deleteFile();
            }
            // If it's a soft delete, we usually keep the file.
            // If you want to delete file on soft delete, remove the forceDeleting check.
        });
    }

    // SCOPES

    /**
     * Scope a query to only include attachments of a specific type.
     *
     * @param  string  $type  The general type (e.g., 'image', 'video', 'document')
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include image attachments.
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', 'image');
    }

    /**
     * Scope a query to only include video attachments.
     */
    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', 'video');
    }

    public function getThumbnailUrlAttribute(): ?string // Return type can be nullable
    {
        if (! $this->disk || ! $this->filepath) {
            return null; // Or a default placeholder thumbnail URL
        }

        // For images, generate a thumbnail URL. For other files, return the original URL or null.
        if (Str::startsWith($this->mimetype ?? '', 'image/')) {
            // Convention: thumbnails are stored in a 'thumbnails' subdirectory within the same directory as the original.
            // e.g., original: 'uploads/ YYYY/MM/DD/image.jpg'
            //       thumbnail: 'uploads/YYYY/MM/DD/thumbnails/image.jpg'
            // Or, a more common one: 'thumbnails/uploads/YYYY/MM/DD/image.jpg' if thumbnails are in a separate root.
            // The current logic in MediaService seems to suggest a different path structure for thumbnails.
            // Let's assume a simple convention for now: thumbnails are in a subfolder named after a preset.
            // This needs to be consistent with MediaService's thumbnail generation path.

            // Placeholder for thumbnail path logic - this needs to align with MediaService.
            // For now, let's assume a generic thumbnail path or return original if no specific thumb logic.
            // A more robust solution would involve MediaService providing the thumbnail path or URL.
            // This path structure must match the one used in MediaService::generateThumbnail
            $presetName = 'small'; // Default preset to show in this accessor
            $pathParts = pathinfo($this->filepath);
            $thumbnailDirectory = ($pathParts['dirname'] === '.' ? '' : $pathParts['dirname'].'/').'thumbnails/'.$presetName;
            $thumbnailFilename = $pathParts['basename']; // MediaService uses original basename for thumb
            $thumbnailPath = $thumbnailDirectory.'/'.$thumbnailFilename;

            try {
                /** @var \Illuminate\Contracts\Filesystem\Filesystem $disk */
                $disk = Storage::disk($this->disk);
                if ($disk->exists($thumbnailPath)) {
                    return $disk->url($thumbnailPath);
                }

                // Fallback to original URL if specific preset thumbnail doesn't exist
                // Log::info("Thumbnail {$thumbnailPath} not found for attachment {$this->id}, falling back to original.");
                return $disk->url($this->filepath);
            } catch (\Exception $e) {
                Log::error("Error generating thumbnail URL for attachment ID {$this->id} (preset: {$presetName}): ".$e->getMessage());

                return $this->getUrlAttribute(); // Fallback to original URL on any error
            }
        }

        // For non-images, or if no thumbnail logic, return original URL or null
        return $this->getUrlAttribute();
    }
}
