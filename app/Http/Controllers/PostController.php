<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

        $post = Post::create([
            'user_id' => auth()->id(),
            'image_url' => $validated['image_url'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'hashtags' => $validated['hashtags'] ?? null,
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
                ->get();

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
            // 1. Extraer path relativo
            $imagePath = $this->extractSupabasePath($post->image_url);
    
            // 2. Eliminar imagen de Supabase
            if ($imagePath) {
                Http::withToken(env('SUPABASE_SERVICE_ROLE'))->delete(
                    env('SUPABASE_URL') . "/storage/v1/object/" . env('SUPABASE_BUCKET') . "/" . $imagePath
                );
            }
    
            // 3. Eliminar post de DB
            $post->delete();
    
            return response()->json(['message' => 'Post and image deleted']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete post or image',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
    private function extractSupabasePath($signedUrl)
    {
        $matches = [];
        preg_match('/sign\/(.+?)\?token=/', $signedUrl, $matches);
        return $matches[1] ?? null;
    }
}
