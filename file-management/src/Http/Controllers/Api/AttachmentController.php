<?php

namespace IJIDeals\FileManagement\Http\Controllers\Api;

use Exception;
use IJIDeals\FileManagement\Models\Attachment;
use IJIDeals\FileManagement\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA; // Import OpenApi namespace

/**
 * @OA\Tag(
 *     name="Attachments",
 *     description="API Endpoints for managing file attachments."
 * )
 */
class AttachmentController extends Controller
{
    protected UploadService $uploadService;

    /**
     * AttachmentController constructor.
     */
    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
        // Middleware for authentication/authorization can be applied here or in route definitions
        // e.g., $this->middleware('auth:api')->except(['show']);
    }

    /**
     * Handles chunked file uploads.
     * If it's the last chunk, it combines them and creates the Attachment.
     *
     * @OA\Post(
     *     path="/api/v1/attachments/chunk-upload",
     *     operationId="handleChunkUpload",
     *     summary="Upload file in chunks or combine uploaded chunks",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="file", type="string", format="binary", description="The file chunk to upload."),
     *                 @OA\Property(property="resumableChunkNumber", type="integer", description="The index of the chunk being uploaded (1-based)."),
     *                 @OA\Property(property="resumableTotalChunks", type="integer", description="The total number of chunks."),
     *                 @OA\Property(property="resumableIdentifier", type="string", description="A unique identifier for the file being uploaded."),
     *                 @OA\Property(property="resumableFilename", type="string", description="The original filename."),
     *                 @OA\Property(property="resumableTotalSize", type="integer", description="The total size of the file in bytes."),
     *                 @OA\Property(property="resumableChunkSize", type="integer", description="The size of each chunk in bytes."),
     *                 @OA\Property(property="attachable_type", type="string", nullable=true, description="Morph type of the model this attachment belongs to (e.g., 'App\\Models\\Post')."),
     *                 @OA\Property(property="attachable_id", type="integer", nullable=true, description="ID of the model this attachment belongs to."),
     *                 @OA\Property(property="title", type="string", nullable=true, description="Optional title for the attachment."),
     *                 @OA\Property(property="description", type="string", nullable=true, description="Optional description for the attachment."),
     *                 @OA\Property(property="disk", type="string", nullable=true, description="Optional storage disk to use (e.g., 's3', 'public'). Defaults to 'file-management.default_disk' config."),
     *                 @OA\Property(property="extras", type="object", nullable=true, description="Optional JSON object for additional metadata.")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Chunk uploaded successfully (file not yet complete).",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Chunk uploaded successfully."),
     *             @OA\Property(property="resumableIdentifier", type="string", example="unique-file-id"),
     *             @OA\Property(property="chunk_number", type="integer", example=5)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="File combined and attachment created successfully.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Attachment")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to create attachments.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="This action is unauthorized."))
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="errors", type="object", example={"file": {"The file field is required."}})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error during upload.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Chunk upload failed: An internal server error occurred."))
     *     )
     * )
     */
    public function handleChunkUpload(Request $request): JsonResponse
    {
        // Authorization for creating attachments (applied generally for upload process)
        Gate::authorize('create', Attachment::class);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'resumableChunkNumber' => 'required|integer|min:1',
            'resumableTotalChunks' => 'required|integer|min:1',
            'resumableIdentifier' => 'required|string',
            'resumableFilename' => 'required|string',
            'resumableTotalSize' => 'required|integer|min:1',
            'resumableChunkSize' => 'required|integer|min:1',
            // Attachable details can also be passed with the first chunk or with the combine request
            'attachable_type' => 'sometimes|required_with:attachable_id|string|morph_alias_exists',
            'attachable_id' => 'sometimes|required_with:attachable_type|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            // Disk for chunks is typically local, final disk can be configured
            // $chunkDisk = 'local'; // This is handled within UploadService for now

            $chunkStatus = $this->uploadService->handleChunk($request, $user);

            if ($chunkStatus['all_chunks_uploaded']) {
                // All chunks are uploaded, proceed to combine them
                $attachable = null;
                if ($request->filled('attachable_type') && $request->filled('attachable_id')) {
                    $attachableModelClass = $request->input('attachable_type');
                    if (class_exists($attachableModelClass) && method_exists($attachableModelClass, 'findOrFail')) {
                        $attachable = $attachableModelClass::findOrFail($request->input('attachable_id'));
                    } else {
                        // Clean up chunks if attachable is invalid? Or let it be and fail on combine?
                        // For now, let combineChunks handle it or throw an error.
                        Log::warning("Invalid attachable_type provided during chunk upload completion: {$attachableModelClass}");
                    }
                }

                $options = $request->only(['title', 'description', 'extras']);
                // Final disk for the combined file
                $finalDisk = $request->input('disk', config('file-management.default_disk'));

                $attachment = $this->uploadService->combineChunks(
                    $request->input('resumableIdentifier'),
                    $request->input('resumableFilename'),
                    (int) $request->input('resumableTotalChunks'),
                    $finalDisk,
                    $attachable,
                    $user,
                    $options
                );

                return response()->json($attachment, 201);
            }

            // Not all chunks uploaded yet, or an intermediate chunk
            // Resumable.js expects 200 OK for chunks, or specific error codes.
            // For successful chunk upload but not complete file:
            return response()->json([
                'message' => 'Chunk uploaded successfully.',
                'resumableIdentifier' => $chunkStatus['resumableIdentifier'],
                'chunk_number' => $chunkStatus['chunk_number'],
            ], 200);

        } catch (Exception $e) {
            Log::error("Chunk upload failed for identifier '{$request->input('resumableIdentifier')}': ".$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Resumable.js might expect 500 or specific error codes for retries.
            return response()->json(['message' => 'Chunk upload failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Store a newly uploaded file (direct upload, not chunked).
     *
     * @OA\Post(
     *     path="/api/v1/attachments",
     *     operationId="storeAttachment",
     *     summary="Upload a single file directly",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="file", type="string", format="binary", description="The file to upload."),
     *                 @OA\Property(property="attachable_type", type="string", nullable=true, description="Morph type of the model this attachment belongs to (e.g., 'App\\Models\\Post')."),
     *                 @OA\Property(property="attachable_id", type="integer", nullable=true, description="ID of the model this attachment belongs to."),
     *                 @OA\Property(property="title", type="string", nullable=true, description="Optional title for the attachment."),
     *                 @OA\Property(property="description", type="string", nullable=true, description="Optional description for the attachment."),
     *                 @OA\Property(property="disk", type="string", nullable=true, description="Optional storage disk to use (e.g., 's3', 'public'). Defaults to 'file-management.default_disk' config."),
     *                 @OA\Property(property="extras", type="object", nullable=true, description="Optional JSON object for additional metadata.")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Attachment created successfully.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Attachment")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to create attachments.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="This action is unauthorized."))
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="errors", type="object", example={"file": {"The file field is required."}})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error during upload.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="File upload failed: An internal server error occurred."))
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Attachment::class);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file', // Uses validation rules from config/file-management.php via UploadService
            'attachable_type' => 'sometimes|required_with:attachable_id|string', // Consider morph_alias_exists
            'attachable_id' => 'sometimes|required_with:attachable_type|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'extras' => 'nullable|array',
            // Add other fields from $options in UploadService if they can be passed via API
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $uploadedFile = $request->file('file');
            $attachable = null;
            if ($request->filled('attachable_type') && $request->filled('attachable_id')) {
                $attachableModelClass = $request->input('attachable_type');
                // Basic check, consider using MorphMap for safety or pre-validated morph alias
                if (class_exists($attachableModelClass) && method_exists($attachableModelClass, 'findOrFail')) {
                    $attachable = $attachableModelClass::findOrFail($request->input('attachable_id'));
                } elseif ($request->filled('attachable_type')) { // Only throw if type was provided but invalid
                    throw new Exception("Invalid attachable_type provided: {$attachableModelClass}");
                }
            }

            /** @var \IJIDeals\UserManagement\Models\User|null $user */
            $user = $request->user();
            $options = $request->only(['title', 'description', 'extras']);
            $disk = $request->input('disk'); // Allow disk specification for direct uploads too

            $attachment = $this->uploadService->upload($uploadedFile, $attachable, $user, $disk, $options);

            return response()->json($attachment, 201);
        } catch (Exception $e) {
            Log::error('Direct file upload failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => 'File upload failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified attachment.
     *
     * @OA\Get(
     *     path="/api/v1/attachments/{attachment}",
     *     operationId="showAttachment",
     *     summary="Retrieve a specific attachment by ID",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="attachment",
     *         in="path",
     *         required=true,
     *         description="ID of the attachment to retrieve",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Attachment")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to view this attachment.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="This action is unauthorized."))
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Attachment not found.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\FileManagement\\Models\\Attachment] 123"))
     *     )
     * )
     */
    public function show(Attachment $attachment): JsonResponse
    {
        Gate::authorize('view', $attachment);

        return response()->json($attachment);
    }

    /**
     * Remove the specified attachment from storage and database.
     *
     * @OA\Delete(
     *     path="/api/v1/attachments/{attachment}",
     *     operationId="deleteAttachment",
     *     summary="Delete a specific attachment by ID",
     *     tags={"Attachments"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="attachment",
     *         in="path",
     *         required=true,
     *         description="ID of the attachment to delete",
     *
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attachment deleted successfully.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Attachment deleted successfully."))
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to delete this attachment.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="This action is unauthorized."))
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Attachment not found.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="No query results for model [IJIDeals\\FileManagement\\Models\\Attachment] 123"))
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error during deletion.",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Failed to delete attachment: An internal server error occurred."))
     *     )
     * )
     */
    public function destroy(Attachment $attachment): JsonResponse
    {
        Gate::authorize('delete', $attachment);

        try {
            $disk = $attachment->disk;
            $filepath = $attachment->filepath;

            // 1. Delete the physical file
            if (Storage::disk($disk)->exists($filepath)) {
                Storage::disk($disk)->delete($filepath);
            } else {
                Log::warning("File not found on disk '{$disk}' at path '{$filepath}' for attachment ID {$attachment->id}. Proceeding to delete DB record.");
            }

            // Also delete thumbnail if it exists
            if (Str::startsWith($attachment->mimetype, 'image/')) {
                $thumbnailPath = 'thumbnails/'.pathinfo($attachment->filepath, PATHINFO_BASENAME);
                if (Storage::disk($attachment->disk)->exists($thumbnailPath)) {
                    Storage::disk($attachment->disk)->delete($thumbnailPath);
                }
            }

            // 2. Delete the Attachment record
            $attachment->delete();

            return response()->json(['message' => 'Attachment deleted successfully.'], 200);
        } catch (Exception $e) {
            Log::error("Failed to delete attachment ID {$attachment->id}: ".$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => 'Failed to delete attachment: '.$e->getMessage()], 500);
        }
    }
}
