<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Events\UserStatusUpdated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], Response::HTTP_CREATED);
    }

public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    // Update user online status
    $user->update([
        'is_online' => true,
        'last_seen_at' => now(),
    ]);

    // Broadcast status update
    broadcast(new UserStatusUpdated($user->id, true, now()->toISOString()));

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
    ]);
}

public function logout(Request $request)
{
    \Log::info('Logout started for user: ' . ($request->user() ? $request->user()->id : 'No user'));
    
    try {
        // Update user offline status BEFORE deleting token
        if ($request->user()) {
            $user = $request->user();
            $user->update([
                'is_online' => false,
                'last_seen_at' => now(),
            ]);
            
            // Broadcast status update
            broadcast(new UserStatusUpdated($user->id, false, now()->toISOString()));
        }
    } catch (\Exception $e) {
        \Log::error('Error updating user status during logout: ' . $e->getMessage());
    }

    // Delete token after updating status
    if ($request->user()) {
        $request->user()->currentAccessToken()->delete();
    }

    return response()->json(['message' => 'Logged out successfully']);
}

    public function user(Request $request)
    {
         return response()->json($request->user()->loadMissing([]));
    }
}