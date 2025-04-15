<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Http;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image_url' => 'required|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'hashtags' => 'nullable|array',
        ]);

        if (!str_contains($validated['image_url'], env('SUPABASE_URL'))) {
            return response()->json(['error' => 'Invalid image source'], 400);
        }

        Log::info('image_url recibido:', [$validated['image_url']]);

        $imagePath = $this->extractSupabasePath($validated['image_url']);

        if (!$imagePath) {
            return response()->json([
                'error' => 'Invalid or missing image_url',
                'debug' => $validated['image_url'],
            ], 400);
        }


        if (!$imagePath) {
            return response()->json(['error' => 'Invalid image URL'], 400);
        }

        $post = Post::create([
            'user_id' => auth()->id(),
            'image_path' => $imagePath, // 🔥 ahora está garantizado
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'hashtags' => $validated['hashtags'] ?? [],
        ]);

        return response()->json([
            'message' => 'Post saved successfully',
            'post' => $post,
        ]);
    }


    public function index()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                ], 404);
            }

            $posts = Post::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($post) {
                    $post->image_url = $this->generateSignedUrl($post->image_path);
                    return $post;
                });

            return response()->json([
                'message' => 'Posts retrieved successfully',
                'posts' => $posts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $post = Post::where('user_id', $user->id)->findOrFail($id);

        try {
            $imagePath = $post->image_path;

            if ($imagePath) {
                Http::withToken(env('SUPABASE_SERVICE_ROLE'))->delete(
                    env('SUPABASE_URL') . "/storage/v1/object/" . env('SUPABASE_BUCKET') . "/" . $imagePath
                );
            }

            $post->delete();

            return response()->json(['message' => 'Post and image deleted']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete post or image',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function generateSignedUrl($path)
    {
        $res = Http::withToken(env('SUPABASE_SERVICE_ROLE'))->post(
            env('SUPABASE_URL') . '/storage/v1/object/sign/' . env('SUPABASE_BUCKET') . '/' . $path,
            ['expiresIn' => 3600]
        );

        if ($res->successful()) {
            return env('SUPABASE_URL') . ($res->json()['signedURL'] ?? '');
        }

        return null;
    }

    private function extractSupabasePath($signedUrl)
    {
        $matches = [];
        preg_match('/sign\/(.+?)\?token=/', $signedUrl, $matches);
        return $matches[1] ?? null; // ejemplo: posts/posts/1744625601371.jpg
    }
}
