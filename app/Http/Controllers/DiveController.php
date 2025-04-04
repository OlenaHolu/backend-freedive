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

    public function destroy(Request $request, Dive $dive)
    {
        if ($dive->user_id !== $request->firebase_user['sub']) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        $dive->delete();

        return response()->json([
            'message' => 'Dive deleted successfully ✅',
        ]);
    }

    public function storeBulk(Request $request)
    {
        $user = User::where('email', $request->firebase_user['email'])->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        $divesData = $request->all();
        $saved = [];

        DB::beginTransaction();

        try {
            foreach ($divesData as $diveData) {
                // Validaciones mínimas por inmersión
                if (!isset($diveData['StartTime'], $diveData['Duration'], $diveData['MaxDepth'])) {
                    continue; // skip this dive if required fields are missing
                }
                $dive = new Dive();
                $dive->user_id = $user->id;
                $dive->StartTime = $diveData['StartTime'];
                $dive->Duration = $diveData['Duration'];
                $dive->MaxDepth = $diveData['MaxDepth'];
                $dive->AvgDepth = $diveData['AvgDepth'] ?? null;
                $dive->Source = $diveData['Source'] ?? null;
                $dive->Note = $diveData['Note'] ?? null;
                $dive->SampleInterval = $diveData['SampleInterval'] ?? null;
                $dive->AltitudeMode = $diveData['AltitudeMode'] ?? null;
                $dive->PersonalMode = $diveData['PersonalMode'] ?? null;
                $dive->DiveNumberInSerie = $diveData['DiveNumberInSerie'] ?? null;
                $dive->SurfaceTime = $diveData['SurfaceTime'] ?? null;
                $dive->SurfacePressure = $diveData['SurfacePressure'] ?? null;
                $dive->DiveTime = $diveData['DiveTime'] ?? null;
                $dive->Deleted = $diveData['Deleted'] ?? false;
                $dive->Weight = $diveData['Weight'] ?? null;
                $dive->Weather = $diveData['Weather'] ?? null;
                $dive->Visibility = $diveData['Visibility'] ?? null;
                $dive->Software = $diveData['Software'] ?? null;
                $dive->SerialNumber = $diveData['SerialNumber'] ?? '';
                $dive->TimeFromReset = $diveData['TimeFromReset'] ?? null;
                $dive->Battery = $diveData['Battery'] ?? null;
                $dive->LastDecoStopDepth = $diveData['LastDecoStopDepth'] ?? 3.0;
                $dive->AscentMode = $diveData['AscentMode'] ?? 0;
                $dive->Mode = $diveData['Mode'] ?? 3; // Freedive por defecto
                $dive->StartTemperature = $diveData['StartTemperature'] ?? 0;
                $dive->BottomTemperature = $diveData['BottomTemperature'] ?? 0;
                $dive->EndTemperature = $diveData['EndTemperature'] ?? 0;
                $dive->PreviousMaxDepth = $diveData['PreviousMaxDepth'] ?? null;
                $dive->save();

                if (!empty($diveData['samples'])) {
                    foreach ($diveData['samples'] as $sample) {
                        $dive->samples()->create([
                            'time' => $sample['time'],
                            'depth' => $sample['depth'] ?? null,
                            'temperature' => $sample['temperature'] ?? null,
                        ]);
                    }
                }

                $saved[] = $dive;
            }

            DB::commit();

            return response()->json([
                'message' => 'Dives saved successfully ✅',
                'saved' => count($saved),
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
}
