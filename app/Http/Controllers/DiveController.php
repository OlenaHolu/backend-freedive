<?php

namespace App\Http\Controllers;

use App\Models\Dive;
use App\Models\User;
use Illuminate\Http\Request;

class DiveController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = User::where('email', $request->firebase_user['email'])->first();
            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                ], 404);
            }
            
            $dives = Dive::where('user_id', $user->id)->get();

            return response()->json([
                'message' => 'Dives retrieved successfully ✅',
                'dives' => $dives,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        $user = User::where('email', $request->firebase_user['email'])->first();

        $validated = $request->validate([
            'StartTime' => 'required|date',
            'Duration' => 'required|integer',
            'MaxDepth' => 'required|numeric',
            'StartTemperature' => 'nullable|numeric',
            'BottomTemperature' => 'nullable|numeric',
            'EndTemperature' => 'nullable|numeric',
            'PreviousMaxDepth' => 'nullable|numeric',
        ]);

        $dive = Dive::create([
            'user_id' => $user->id,
            'StartTime' => $validated['StartTime'],
            'Duration' => $validated['Duration'],
            'Mode' => 3,
            'MaxDepth' => $validated['MaxDepth'],
            'StartTemperature' => $validated['StartTemperature'] ?? 0,
            'BottomTemperature' => $validated['BottomTemperature'] ?? 0,
            'EndTemperature' => $validated['EndTemperature'] ?? 0,
            'PreviousMaxDepth' => $validated['PreviousMaxDepth'] ?? null,
        ]);

        return response()->json([
            'message' => 'Dive saved successfully ✅',
            'dive' => $dive,
        ]);
    }
}
