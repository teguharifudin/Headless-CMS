<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        try {
            $pages = Page::with(['bannerMedia', 'author'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($pages->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No data',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pages fetched successfully',
                'data' => $pages
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch pages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pages',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'banner_media_id' => 'nullable|exists:media,id',
                'status' => 'required|in:draft,published',
                'published_at' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:now',
            ]);

            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Page::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
            $validated['author_id'] = auth()->id();
            
            $page = Page::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Page created successfully',
                'data' => $page
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()->toArray(),
                'data' => null
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create page',
                'errors' => [$e->getMessage()],
                'data' => null
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $page = Page::with(['bannerMedia', 'author'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Page retrieved successfully',
                'data' => $page
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
                'errors' => ['id' => ['Page with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve page',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $page = Page::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'banner_media_id' => 'nullable|exists:media,id',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'status' => 'sometimes|required|in:draft,published',
                'published_at' => 'nullable|date_format:Y-m-d H:i:s',
            ]);

            if (isset($validated['title'])) {
                $slug = Str::slug($validated['title']);
                $originalSlug = $slug;
                $counter = 1;

                while (Page::where('slug', $slug)
                        ->where('id', '!=', $id)
                        ->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                $validated['slug'] = $slug;
            }

            $page->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Page updated successfully',
                'data' => $page->fresh()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
                'errors' => ['id' => ['Page with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'data' => null
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update page',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $page = Page::findOrFail($id);
            $page->delete();

            return response()->json([
                'success' => true,
                'message' => 'Page deleted successfully',
                'data' => null
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
                'errors' => ['id' => ['Page with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete page',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }
}
