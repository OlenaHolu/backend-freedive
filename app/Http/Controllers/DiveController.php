<?php

namespace App\Http\Controllers;

use App\Models\Dive;
use App\Models\User;
use Illuminate\Http\Request;

class DiveController extends Controller
{
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
