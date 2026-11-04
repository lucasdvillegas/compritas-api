<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Perfil obtenido correctamente.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'pin' => $user->pin,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->name = $validated['name'];
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Perfil actualizado correctamente.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    public function regeneratePin(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->pin = User::generatePin();
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'PIN regenerado correctamente.',
            'data' => [
                'pin' => $user->pin,
            ],
        ]);
    }
}
