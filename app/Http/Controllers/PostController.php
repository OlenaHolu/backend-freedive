<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

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
}
    