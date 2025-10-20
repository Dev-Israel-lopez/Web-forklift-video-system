<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Body JSON: { "user": "nombre", "password": "secreto" }
     * Respuesta: { "token": "...", "user": { "id": 1, "name": "..." } }
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'user' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Usamos 'name' como identificador (segï¿½n tu requerimiento).
        $user = User::where('name', $data['user'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales invï¿½lidas'], 401);
        }

        // Genera nuevo token (revoca el anterior para simplificar).
        $user->api_token = Str::random(60);
        $user->save();

        return response()->json([
            'token' => $user->api_token,
            'user'  => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }
}
