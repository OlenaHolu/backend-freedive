<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;

class PostController extends Controller
{
    public function store(Request $request)
    {
        try{
        $validated = $request->validate([
            'image_path' => 'required|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'hashtags' => 'nullable|array',
        ]);

        $post = Post::create([
            'user_id' => auth()->id(),
            'image_path' => $validated['image_path'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'hashtags' => $validated['hashtags'] ?? [],
        ]);

        return response()->json([
            'message' => 'Post saved successfully',
            'post' => $post,
        ]);
    } catch (\Exception $e) {
            return response()->json([
                'errorCode' => 1300,
                'error' => 'Failed to save post',
                'details' => $e->getMessage(),
            ], 500);
        }
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
                'errorCode' => 1000,
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
            $url = $res->json()['signedURL'] ?? null;
            Log::info('Signed URL generated:', ['url' => $url]);
            return $url;
        }
    
        Log::error('Failed to generate signed URL', [
            'response' => $res->body(),
            'path' => $path,
        ]);
         // Lanzar error con JSON válido para frontend
         throw new HttpResponseException(
            response()->json([
                'errorCode' => 1300,
                'error' => 'Failed to generate signed URL',
                'details' => $res->json() ?? $res->body(),
            ], 422)
        );

    }
    

}
