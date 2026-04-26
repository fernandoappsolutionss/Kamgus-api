<?php

namespace App\Classes;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Supabase Storage helper using Supabase's native HTTP API (NOT the S3 protocol).
 *
 * Reason: aws-sdk-php strips path components from endpoints, breaking Supabase's
 * /storage/v1/s3 endpoint. The native API is also feature-richer (image transforms,
 * resumable uploads) and authenticates with a single Bearer token.
 *
 * Two buckets:
 *  - kamgus-public  → service photos, vehicle photos, profile photos, app catalog
 *  - kamgus-private → driver documents (cedula, license, soat, propiedad)
 *
 * Auth uses SUPABASE_SERVICE_ROLE_KEY (server-only secret, NEVER expose to client).
 */
class SupabaseStorage
{
    public const BUCKET_PUBLIC  = 'kamgus-public';
    public const BUCKET_PRIVATE = 'kamgus-private';

    /**
     * Upload a file to the public bucket. Returns the public URL.
     *
     * @return string|false  Public URL on success, false on failure.
     */
    public static function uploadPublic(UploadedFile $file, string $folder, ?string $filename = null): string|false
    {
        $bucket = env('SUPABASE_BUCKET_PUBLIC', self::BUCKET_PUBLIC);
        $path   = self::buildPath($folder, $filename ?? self::sanitizeFilename($file));

        if (!self::putFile($bucket, $path, $file)) {
            return false;
        }
        return self::publicUrl($path);
    }

    /**
     * Upload a file to the private bucket. Returns the storage path.
     *
     * @return string|false  Path on success, false on failure.
     */
    public static function uploadPrivate(UploadedFile $file, string $folder, ?string $filename = null): string|false
    {
        $bucket = env('SUPABASE_BUCKET_PRIVATE', self::BUCKET_PRIVATE);
        $path   = self::buildPath($folder, $filename ?? self::sanitizeFilename($file));

        if (!self::putFile($bucket, $path, $file)) {
            return false;
        }
        return $path;
    }

    /**
     * Upload raw content (string or stream) to a bucket.
     */
    public static function putRaw(string $bucket, string $path, string $content, string $contentType = 'application/octet-stream'): bool
    {
        $url = self::baseUrl() . "/object/{$bucket}/{$path}";

        // Use Http::send() with explicit body so Content-Type isn't overridden.
        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . self::serviceRoleKey(),
                'Content-Type'  => $contentType,
                'x-upsert'      => 'true',
            ])
            ->timeout(60)
            ->send('PUT', $url, ['body' => $content]);

        if (!$response->successful()) {
            Log::warning('SupabaseStorage putRaw failed', [
                'bucket' => $bucket, 'path' => $path,
                'status' => $response->status(), 'body' => $response->body(),
            ]);
            return false;
        }
        return true;
    }

    /**
     * Get a temporary signed URL for a private-bucket object. Default expiry: 60 min.
     *
     * @return string|false  Full signed URL on success, false on failure.
     */
    public static function signedUrl(string $path, int $expiresInSeconds = 3600, ?string $bucket = null): string|false
    {
        $bucket = $bucket ?? env('SUPABASE_BUCKET_PRIVATE', self::BUCKET_PRIVATE);
        $url = self::baseUrl() . "/object/sign/{$bucket}/{$path}";

        $response = Http::withToken(self::serviceRoleKey())
            ->asJson()
            ->timeout(15)
            ->post($url, ['expiresIn' => $expiresInSeconds]);

        if (!$response->successful()) {
            Log::warning('SupabaseStorage signedUrl failed', [
                'bucket' => $bucket, 'path' => $path,
                'status' => $response->status(), 'body' => $response->body(),
            ]);
            return false;
        }
        $signed = $response->json('signedURL');
        return $signed ? rtrim(env('SUPABASE_URL'), '/') . '/storage/v1' . $signed : false;
    }

    /**
     * Build the public URL for a public-bucket object (no signature, anyone can fetch).
     */
    public static function publicUrl(string $path, ?string $bucket = null): string
    {
        $bucket = $bucket ?? env('SUPABASE_BUCKET_PUBLIC', self::BUCKET_PUBLIC);
        return rtrim(env('SUPABASE_URL'), '/') . "/storage/v1/object/public/{$bucket}/{$path}";
    }

    /**
     * Delete an object.
     */
    public static function delete(string $bucket, string $path): bool
    {
        $url = self::baseUrl() . "/object/{$bucket}/{$path}";
        $response = Http::withToken(self::serviceRoleKey())->timeout(15)->delete($url);
        return $response->successful();
    }

    /**
     * Check whether an object exists.
     */
    public static function exists(string $bucket, string $path): bool
    {
        $url = self::baseUrl() . "/object/info/{$bucket}/{$path}";
        $response = Http::withToken(self::serviceRoleKey())->timeout(15)->get($url);
        return $response->successful();
    }

    // ========================================================================
    // Internals
    // ========================================================================

    private static function putFile(string $bucket, string $path, UploadedFile $file): bool
    {
        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            return false;
        }
        try {
            return self::putRaw($bucket, $path, stream_get_contents($stream), $file->getMimeType() ?: 'application/octet-stream');
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private static function buildPath(string $folder, string $filename): string
    {
        $folder = trim($folder, '/');
        return $folder ? "{$folder}/{$filename}" : $filename;
    }

    private static function sanitizeFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug      = Str::slug($basename) ?: 'file';
        $unique    = Str::random(8);
        return "{$slug}-{$unique}.{$extension}";
    }

    private static function baseUrl(): string
    {
        return rtrim(env('SUPABASE_URL'), '/') . '/storage/v1';
    }

    private static function serviceRoleKey(): string
    {
        $key = env('SUPABASE_SERVICE_ROLE_KEY');
        if (empty($key)) {
            throw new \RuntimeException('SUPABASE_SERVICE_ROLE_KEY env var is not set.');
        }
        return $key;
    }
}
