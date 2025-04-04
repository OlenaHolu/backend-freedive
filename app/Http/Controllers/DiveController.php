<?php

namespace App\Http\Controllers;

use App\Models\Dive;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function update(Request $request, $id)
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

        $dive = Dive::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $dive->update(array_merge($validated, [
            'Mode' => $validated['Mode'] ?? 3, // Freedive por defecto
        ]));

        return response()->json([
            'message' => 'Dive updated successfully ✅',
            'dive' => $dive,
        ]);
    }

    public function storeBulk(Request $request)
{
    $user = User::where('email', $request->firebase_user['email'])->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $divesData = $request->all();
    $divesToInsert = [];
    $samplesToInsert = [];

    DB::beginTransaction();

    try {
        foreach ($divesData as $diveData) {
            // Validaciones mínimas
            if (!isset($diveData['StartTime'], $diveData['Duration'], $diveData['MaxDepth'])) {
                continue;
            }

            $dive = new Dive();
            $dive->user_id = $user->id;
            $dive->StartTime = $diveData['StartTime'];
            $dive->Duration = $diveData['Duration'];
            $dive->MaxDepth = $diveData['MaxDepth'];
            $dive->AvgDepth = $diveData['AvgDepth'] ?? null;
            $dive->Note = $diveData['Note'] ?? null;
            $dive->Mode = $diveData['Mode'] ?? 3;
            $dive->StartTemperature = $diveData['StartTemperature'] ?? 0;
            $dive->BottomTemperature = $diveData['BottomTemperature'] ?? 0;
            $dive->EndTemperature = $diveData['EndTemperature'] ?? 0;
            $dive->PreviousMaxDepth = $diveData['PreviousMaxDepth'] ?? null;
            $dive->save(); // 👈 Aquí obtenemos el ID

            // Agregamos samples con ese ID
            if (!empty($diveData['samples'])) {
                foreach ($diveData['samples'] as $sample) {
                    $samplesToInsert[] = [
                        'dive_id' => $dive->id,
                        'time' => $sample['time'],
                        'depth' => $sample['depth'] ?? null,
                        'temperature' => $sample['temperature'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (!empty($samplesToInsert)) {
            DB::table('dive_samples')->insert($samplesToInsert);
        }

        DB::commit();

        return response()->json([
            'message' => 'Dives saved successfully ✅',
            'saved' => count($divesData),
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Failed to save dives ❌',
            'details' => $e->getMessage(),
        ], 500);
    }
}

    
    public function show(Request $request, $id)
    {
        $user = User::where('email', $request->firebase_user['email'])->first();

        $dive = Dive::with('samples')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'message' => 'Dive loaded successfully ✅',
            'dive' => $dive,
        ]);
    }

    public function destroy(Request $request, $id)
{
    try {
        $user = User::where('email', $request->firebase_user['email'])->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        $dive = Dive::where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

        if (!$dive) {
            return response()->json([
                'error' => 'Dive not found or does not belong to the user',
            ], 404);
        }

        $dive->delete();

        return response()->json([
            'message' => 'Dive deleted successfully ✅',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to delete dive ❌',
            'details' => $e->getMessage(),
        ], 500);
    }
}

public function destroyMany(Request $request)
{
    try {
        $user = User::where('email', $request->firebase_user['email'])->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $deleted = Dive::where('user_id', $user->id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return response()->json([
            'message' => 'Dives deleted successfully ✅',
            'deleted' => $deleted,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to delete dives ❌',
            'details' => $e->getMessage(),
        ], 500);
    }
}

}
