<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Upload a file to the specified folder within public directory.
 *
 * @param UploadedFile $file The uploaded file instance.
 * @param string $folder Folder path relative to the `public` directory.
 * @param string|null $customName Optional custom file name without extension.
 * @return string|null Relative file path or null on failure.
 */
function uploadFile(UploadedFile $file, string $folder, ?string $customName = null): ?string
{
    try {
        $folderPath = public_path($folder);

        if (! File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        $fileName = $customName
            ? $customName . '.' . $file->getClientOriginalExtension()
            : time() . '.' . $file->getClientOriginalExtension();

        $file->move($folderPath, $fileName);

        return '/' . trim($folder, '/') . '/' . $fileName;
    } catch (Exception $e) {

        return $e;
    }
}

/**
 * Delete a file from the public path or storage path.
 *
 * @param string|null $filePath Path to the file (relative to public/ or storage/)
 * @param bool $isPublic Whether the path is from the public folder (default: true)
 * @return bool True if deleted, false otherwise
 */
function deleteFile(?string $filePath, bool $isPublic = true): bool
{
    if (! $filePath) {
        return false;
    }

    try {
        $fullPath = $isPublic ? public_path($filePath) : storage_path($filePath);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
            Log::info(message: "File deleted: {$fullPath}");
            return true;
        }
    } catch (\Exception $e) {
        Log::error("File deletion failed: {$filePath} - " . $e->getMessage());
    }

    return false;
}

/**
 * Delete multiple files from public or storage path.
 *
 * @param array $filePaths Array of file paths (relative to public/ or storage/)
 * @param bool $isPublic Whether the paths are from the public folder (default: true)
 * @return array An array with 'deleted' and 'failed' file paths
 */
function deleteFiles(array $filePaths, bool $isPublic = true): array
{
    $deleted = [];
    $failed  = [];

    foreach ($filePaths as $filePath) {
        if (! $filePath) {
            continue;
        }

        try {
            $fullPath = $isPublic ? public_path($filePath) : storage_path($filePath);

            if (File::exists($fullPath)) {
                File::delete($fullPath);
                $deleted[] = $filePath;
                Log::info("File deleted: {$fullPath}");
            } else {
                $failed[] = $filePath;
                Log::warning("File not found: {$fullPath}");
            }
        } catch (\Exception $e) {
            $failed[] = $filePath;
            Log::error("File deletion failed: {$filePath} - " . $e->getMessage());
        }
    }

    return [
        'deleted' => $deleted,
        'failed'  => $failed,
    ];
}

function getFileType(string $path): string
{
    $extension = pathinfo($path, PATHINFO_EXTENSION);

    return match (strtolower($extension)) {
        'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
        'mp4', 'mov', 'avi', 'mkv' => 'video',
        'mp3', 'wav', 'ogg' => 'audio',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt' => 'file',
        default => 'file',
    };
}
