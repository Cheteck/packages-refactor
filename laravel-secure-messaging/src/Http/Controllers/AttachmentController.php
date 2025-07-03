<?php

namespace Acme\SecureMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class AttachmentController extends Controller
{
    /**
     * Store a newly uploaded attachment.
     * The attachment is expected to be already encrypted by the client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $maxSizeKb = config('messaging.features.attachments.max_size_kb', 10240); // Default 10MB
        $allowedMimesConfig = config('messaging.features.attachments.allowed_mime_types', []); // Default empty (allow all) or specific types

        $request->validate([
            'attachment' => [
                'required',
                File::types($allowedMimesConfig)->max($maxSizeKb),
            ]
        ]);

        if (!config('messaging.features.attachments.enabled')) {
            return response()->json(['message' => 'Attachment uploads are disabled.'], 403);
        }

        $file = $request->file('attachment');
        $user = $request->user();

        // Generate a unique path/filename for the attachment
        // The file is stored encrypted, so its original extension might not be relevant for storage,
        // but original mime type and name are stored in the Message model.
        $disk = config('messaging.features.attachments.storage_disk', 'local');
        $pathPrefix = config('messaging.features.attachments.storage_path_prefix', 'messaging_attachments');

        // Create a filename that includes user ID to prevent direct guessing and to help with organization/cleanup
        // The actual file content is encrypted, so extension doesn't matter much for the stored file itself.
        $filename = Str::uuid() . '.encrypted'; // Store with a generic encrypted extension
        $fullPath = $pathPrefix . '/' . $user->id . '/' . $filename;

        try {
            // Store the file using Laravel's file storage
            $storedPath = Storage::disk($disk)->putFileAs(
                $pathPrefix . '/' . $user->id, // Directory within the disk
                $file,                         // UploadedFile object
                $filename                      // Custom filename
            );

            if (!$storedPath) {
                throw new \Exception("Failed to store attachment.");
            }

            // $storedPath will be something like "messaging_attachments/1/uuid.encrypted"
            // This is what will be saved in the `attachment_path` of the Message model.

            return response()->json([
                'message' => 'Attachment uploaded successfully.',
                'data' => [
                    'attachment_path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_kb' => round($file->getSize() / 1024, 2),
                ]
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Attachment upload failed: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Attachment upload failed. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve an attachment.
     * This method would typically be used by the client to download the encrypted attachment.
     * The client would then decrypt it.
     *
     * @param Request $request
     * @param string $messageId - Or some other identifier to locate the attachment
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    // public function show(Request $request, $attachmentPath) // This needs more thought on how path is passed
    // {
    //     // This is complex because $attachmentPath could be anything.
    //     // A better way is to link it to a message ID or a secure token.
    //     // For now, let's assume the client has the direct 'attachment_path' from the message
    //     // and this controller is NOT responsible for serving it directly, but the path is used with Storage facade.

    //     // If direct download via controller:
    //     // $user = $request->user();
    //     // $message = Message::where('attachment_path', $attachmentPath) -> firstOrFail();
    //     // Check if user is a recipient of this message and allowed to download
    //     // ... authorization logic ...
    //     // $disk = config('messaging.features.attachments.storage_disk', 'local');
    //     // if (Storage::disk($disk)->exists($attachmentPath)) {
    //     //     return Storage::disk($disk)->download($attachmentPath, $message->attachment_original_name);
    //     // }
    //     // return response()->json(['message' => 'Attachment not found.'], 404);
    //     return response()->json(['message' => 'Direct download via this endpoint is not yet implemented. Use the attachment_path with client-side storage access if applicable, or a dedicated download URL.']);
    // }
}
