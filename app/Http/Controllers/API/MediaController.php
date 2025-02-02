<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Media::query();
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $media = $query->latest()->get();

            if ($media->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No data',
                    'data' => []
                ], 200);
            }

            $media->transform(function ($item) {
                $item->url = Storage::disk($item->disk)->url($item->path);
                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => 'Media fetched successfully',
                'data' => $media
            ], 200);

        } catch (\Exception $e) {
            Log::error('Media fetch failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch media',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:102400|mimes:jpeg,png,jpg,gif,mp4,pdf,doc,docx', // 100MB max
                'name' => 'required|string|max:255'
            ]);

            $file = $request->file('file');
            $mimeType = $file->getMimeType();
            
            $type = match (true) {
                str_starts_with($mimeType, 'image/') => 'image',
                str_starts_with($mimeType, 'video/') => 'video',
                default => 'document'
            };

            $filename = time() . '_' . $file->hashName();
            $path = $file->storeAs(
                "media/{$type}s",
                $filename,
                'public'
            );

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            $media = Media::create([
                'name' => $validated['name'],
                'file_name' => $filename,
                'mime_type' => $mimeType,
                'type' => $type,
                'path' => $path,
                'disk' => 'public',
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'path' => $path,
                    'disk' => 'public',
                    'file_name' => $media->file_name,
                    'type' => $media->type,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => Storage::disk('public')->url($media->path),
                    'created_at' => $media->created_at,
                    'updated_at' => $media->updated_at
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'data' => null
            ], 422);

        } catch (\Exception $e) {
            Log::error('Media upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'file_name' => $request->hasFile('file') ? $request->file('file')->getClientOriginalName() : null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media',
                'errors' => ['upload' => ['Failed to process file upload']],
                'data' => null
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $media = Cache::remember("media_{$id}", now()->addHour(), function () use ($id) {
                $media = Media::findOrFail($id);
                $media->url = Storage::disk($media->disk)->url($media->path);
                return $media;
            });

            return response()->json([
                'success' => true,
                'message' => 'Media retrieved successfully',
                'data' => $media
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
                'errors' => ['id' => ['Media with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve media', [
                'error' => $e->getMessage(),
                'media_id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $media = Media::findOrFail($id);
            
            $filePath = $media->path;
            $diskName = $media->disk;

            if (Storage::disk($diskName)->exists($filePath)) {
                if (!Storage::disk($diskName)->delete($filePath)) {
                    throw new \Exception('Failed to delete file from storage');
                }
            }

            $media->delete();
            
            Cache::forget("media_{$id}");

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
                'data' => null
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
                'errors' => ['id' => ['Media with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete media', [
                'error' => $e->getMessage(),
                'media_id' => $id,
                'file_path' => $filePath ?? null,
                'disk' => $diskName ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }
}
