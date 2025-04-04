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

    public function storeMany(Request $request)
    {
        $user = User::where('email', $request->firebase_user['email'])->first();
    
        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }
    
        $divesData = $request->all();
    
        DB::beginTransaction();
    
        try {
            $now = now();
            $divesToInsert = [];
            $samplesToInsert = [];
    
            foreach ($divesData as $diveData) {
                // Validaciones mínimas por inmersión
                if (!isset($diveData['StartTime'], $diveData['Duration'], $diveData['MaxDepth'])) {
                    continue;
                }
    
                $divesToInsert[] = [
                    'user_id' => $user->id,
                    'StartTime' => $diveData['StartTime'],
                    'Duration' => $diveData['Duration'],
                    'MaxDepth' => $diveData['MaxDepth'],
                    'AvgDepth' => $diveData['AvgDepth'] ?? null,
                    'Source' => $diveData['Source'] ?? null,
                    'Note' => $diveData['Note'] ?? null,
                    'SampleInterval' => $diveData['SampleInterval'] ?? null,
                    'AltitudeMode' => $diveData['AltitudeMode'] ?? null,
                    'PersonalMode' => $diveData['PersonalMode'] ?? null,
                    'DiveNumberInSerie' => $diveData['DiveNumberInSerie'] ?? null,
                    'SurfaceTime' => $diveData['SurfaceTime'] ?? null,
                    'SurfacePressure' => $diveData['SurfacePressure'] ?? null,
                    'DiveTime' => $diveData['DiveTime'] ?? null,
                    'Deleted' => $diveData['Deleted'] ?? false,
                    'Weight' => $diveData['Weight'] ?? null,
                    'Weather' => $diveData['Weather'] ?? null,
                    'Visibility' => $diveData['Visibility'] ?? null,
                    'Software' => $diveData['Software'] ?? null,
                    'SerialNumber' => $diveData['SerialNumber'] ?? '',
                    'TimeFromReset' => $diveData['TimeFromReset'] ?? null,
                    'Battery' => $diveData['Battery'] ?? null,
                    'LastDecoStopDepth' => $diveData['LastDecoStopDepth'] ?? 3.0,
                    'AscentMode' => $diveData['AscentMode'] ?? 0,
                    'Mode' => $diveData['Mode'] ?? 3,
                    'StartTemperature' => $diveData['StartTemperature'] ?? 0,
                    'BottomTemperature' => $diveData['BottomTemperature'] ?? 0,
                    'EndTemperature' => $diveData['EndTemperature'] ?? 0,
                    'PreviousMaxDepth' => $diveData['PreviousMaxDepth'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
    
            // Insertar inmersiones y recuperar IDs
            $chunked = array_chunk($divesToInsert, 1000); // por si hay muchas
            $insertedDiveIds = [];
    
            foreach ($chunked as $chunk) {
                $firstIdBefore = DB::table('dives')->max('id');
                DB::table('dives')->insert($chunk);
                $lastIdAfter = DB::table('dives')->max('id');
                for ($i = $firstIdBefore + 1; $i <= $lastIdAfter; $i++) {
                    $insertedDiveIds[] = $i;
                }
            }
    
            // Asignar samples si vienen (opcional)
            foreach ($divesData as $index => $diveData) {
                if (!empty($diveData['samples']) && isset($insertedDiveIds[$index])) {
                    foreach ($diveData['samples'] as $sample) {
                        $samplesToInsert[] = [
                            'dive_id' => $insertedDiveIds[$index],
                            'time' => $sample['time'],
                            'depth' => $sample['depth'] ?? null,
                            'temperature' => $sample['temperature'] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
    
            if (!empty($samplesToInsert)) {
                DB::table('samples')->insert($samplesToInsert);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Dives saved successfully ✅',
                'saved' => count($divesToInsert),
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
