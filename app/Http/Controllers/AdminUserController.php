<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    public function pending()
    {
        return User::query()
            ->where('is_approved', false)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'created_at']);
    }

    public function approve(User $user)
    {
        Log::debug('user' . $user->role);
        $user->update(['is_approved' => true, 'approved_at' => now()]);
        return $user;
        return response()->json(['message' => 'Utilisateur approuvé']);
    }

    // optionnel
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }
}
