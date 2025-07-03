# File Management Package

The File Management package provides a centralized and robust system for handling all file uploads, storage, and media manipulation within the IJIDeals ecosystem. It's designed to be a flexible and scalable solution for managing user-generated content and application assets.

## Core Features

-   **Driver-Based Storage**: Seamlessly switch between storage drivers like `local`, `public`, and `s3` via configuration.
-   **Polymorphic Associations**: Attach files to any model in the ecosystem (e.g., a `User`'s profile picture, a `Product`'s image, a `Post`'s video).
-   **File & Image Manipulation**: On-the-fly image resizing, cropping, and optimization.
-   **Access Control**: Secure and managed access to private files.
-   **Chunked Uploads**: Support for large file uploads.

## Key Components

### Models

-   `Attachment`: The central model representing a stored file. It uses a polymorphic relationship to link to any other model.

### Services

-   `UploadService`: A service class to handle the logic of file uploads, processing, and storage.
-   `MediaService`: Provides utilities for image manipulation and transformation.

## Dependencies

-   **`ijideals/user-management`**: For associating files with users and handling permissions.

## Configuration

Publish the configuration file to customize storage disks, paths, and allowed file types.

```sh
php artisan vendor:publish --provider="IJIDeals\FileManagement\Providers\FileManagementServiceProvider" --tag="config"
```

Configuration options include:
-   Default storage disk (`local`, `s3`, etc.).
-   Allowed MIME types and file extensions.
-   Maximum upload size.
-   Image processing settings (e.g., thumbnail sizes).

## Usage

Detailed documentation on how to use the package's features will be added here.

## Testing

```bash
composer test
```

## Security

If you discover any security-related issues, please email security@ijideals.com instead of using the issue tracker. 
