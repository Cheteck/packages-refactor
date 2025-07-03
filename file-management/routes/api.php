<?php

use IJIDeals\FileManagement\Http\Controllers\Api\AttachmentController;
use IJIDeals\FileManagement\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for File Management Package
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your package. These
| routes are loaded by the FileManagementServiceProvider within a group which
| is typically prefixed with 'api'.
|
*/

// It's good practice to version your API.
// The prefix 'api/v1/file-management' will be applied by the RouteServiceProvider of the package or main app.
// Here, we define routes relative to that prefix.

Route::middleware(['auth:sanctum']) // Ensure only authenticated users can access these routes
     // ->prefix('v1/file-management') // This prefix might be applied by the service provider or app's RouteServiceProvider
    ->name('file-management.api.') // Route name prefix for easier URL generation
    ->group(function () {

        /**
         * @OA\Post(
         *     path="/upload",
         *     summary="Upload a new file",
         *     tags={"File Management"},
         *     security={{"sanctum":{}}},
         *
         *     @OA\RequestBody(
         *         required=true,
         *
         *         @OA\MediaType(
         *             mediaType="multipart/form-data",
         *
         *             @OA\Schema(
         *                 required={"file"},
         *
         *                 @OA\Property(property="file", type="string", format="binary", description="The file to upload."),
         *                 @OA\Property(property="attachable_type", type="string", example="user_avatar", description="Short type of the model to attach to (e.g., 'user_avatar', 'post_image'). Needs mapping in backend."),
         *                 @OA\Property(property="attachable_id", type="integer", example=1, description="ID of the model instance to attach to."),
         *                 @OA\Property(property="disk", type="string", example="public", description="Optional: Specific storage disk to use."),
         *                 @OA\Property(property="directory", type="string", example="custom/path", description="Optional: Specific directory path within the disk."),
         *                 @OA\Property(property="custom_type", type="string", example="profile_picture", description="Optional: User-defined category for the attachment.")
         *             )
         *         )
         *     ),
         *
         *     @OA\Response(response=201, description="File uploaded successfully, returns Attachment model."),
         *     @OA\Response(response=401, description="Unauthenticated."),
         *     @OA\Response(response=404, description="Attachable entity not found."),
         *     @OA\Response(response=422, description="Validation error (e.g., file type, size, invalid attachable_type)."),
         *     @OA\Response(response=500, description="File upload failed on server.")
         * )
         */
        Route::post('/upload', [FileController::class, 'upload'])->name('upload');

        // Future routes for file management could go here:
        // Route::get('/files', [FileController::class, 'index'])->name('index');
        // Route::get('/files/{attachment}', [FileController::class, 'show'])->name('show');
        // Route::delete('/files/{attachment}', [FileController::class, 'destroy'])->name('destroy');

        // Group file management routes
        Route::prefix('files')->group(function () {
            Route::post('/upload', [FileController::class, 'upload']);
            Route::post('/upload-chunk', [FileController::class, 'uploadChunk']);
            Route::post('/combine-chunks', [FileController::class, 'combineChunks']);

            // Attachment management
            Route::middleware(['auth:sanctum'])->group(function () {
                Route::get('/', [AttachmentController::class, 'index']);
                Route::post('/', [AttachmentController::class, 'store']);
                Route::get('/{attachment}', [AttachmentController::class, 'show']);
                Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
            });
        });
    });
