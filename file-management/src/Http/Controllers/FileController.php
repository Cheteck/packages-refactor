<?php

namespace IJIDeals\FileManagement\Http\Controllers;

use IJIDeals\FileManagement\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class FileController extends BaseController
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function uploadChunk(Request $request)
    {
        $request->validate([
            'dzuuid' => 'required|string',
            'dzchunkindex' => 'required|integer',
            'dzchunksize' => 'required|integer',
            'dztotalfilesize' => 'required|integer',
            'dzchunkbyteoffset' => 'required|integer',
            'dzchunktotalbytes' => 'required|integer',
            'file' => 'required|file',
            'filename' => 'required|string',
            'folder' => 'nullable|string',
            'visibility' => 'nullable|string',
        ]);

        $chunk = $request->file('file');
        $dzuuid = $request->input('dzuuid');
        $filename = $request->input('filename');
        $chunkIndex = $request->input('dzchunkindex');
        $totalChunks = ceil($request->input('dztotalfilesize') / $request->input('dzchunksize'));

        $this->uploadService->storeChunk($chunk, $dzuuid, $filename, $chunkIndex, $totalChunks);

        return Response::json(['success' => true]);
    }

    public function combineChunks(Request $request)
    {
        $request->validate([
            'dzuuid' => 'required|string',
            'filename' => 'required|string',
            'folder' => 'nullable|string',
            'visibility' => 'nullable|string',
            'total_filesize' => 'required|integer',
            'mimetype' => 'required|string',
        ]);

        $finalFile = $this->uploadService->combineChunks(
            $request->input('dzuuid'),
            $request->input('filename'),
            $request->input('folder', 'general'),
            $request->input('visibility', 'public'),
            $request->input('total_filesize'),
            $request->input('mimetype')
        );

        return Response::json($finalFile);
    }

    /**
     * Maps a string type to a fully qualified model class name.
     * This is a critical part for security and flexibility.
     * It should ideally use a predefined map from a configuration file.
     */
    protected function mapAttachableTypeToModelClass(string $type): ?string
    {
        // Fetch mapping from config to make it maintainable and secure
        // Ensure the config key matches what you'll use (e.g., 'ijideals-file-management.attachable_models')
        $map = config('ijideals-file-management.attachable_models_map', [
            // Example: 'user_profile_avatar' maps to User class for avatar relationship
            // 'user' => \IJIDeals\UserManagement\Models\User::class,
            // 'post' => \IJIDeals\Social\Models\Post::class, // Example for a generic Post model
            // More specific types:
            // 'user_avatar' => \IJIDeals\UserManagement\Models\User::class,
            // 'team_logo' => \IJIDeals\UserManagement\Models\Team::class,
        ]);

        // Normalize the input type (e.g., 'user_avatar' or 'UserAvatar' -> 'user_avatar')
        $normalizedType = strtolower(Str::snake($type));

        $modelClass = $map[$normalizedType] ?? null;

        if ($modelClass && class_exists($modelClass) && is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
            return $modelClass;
        }

        // Fallback for simple direct class name if not in map (less secure, use with caution)
        // $studlyType = Str::studly($type);
        // Potential model namespaces to check:
        // $namespaces = [
        //     'App\\Models\\',
        //     'App\\Packages\\IJIDeals\\UserManagement\\Models\\',
        //     // Add other relevant package model namespaces
        // ];
        // foreach ($namespaces as $namespace) {
        //     if (class_exists($namespace . $studlyType) && is_subclass_of($namespace . $studlyType, \Illuminate\Database\Eloquent\Model::class)) {
        //         return $namespace . $studlyType;
        //     }
        // }

        return null; // Type not found or not a valid model
    }
}
