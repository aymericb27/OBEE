<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    /**
     * Liste des utilisateurs en attente de validation
     */
    public function pending()
    {
        return User::query()
            ->where('is_approved', false)
            ->with(['university:id,name'])
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'email',
                'university_id',
                'created_at',
            ]);
    }

    /**
     * Approuver un utilisateur
     */
    public function approve(User $user)
    {
        if ($user->is_approved) {
            return response()->json(['message' => 'Utilisateur déjà approuvé'], 200);
        }

        $user->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Utilisateur approuvé'], 200);
    }

    /**
     * Refuser / supprimer un utilisateur (optionnel)
     */
    public function destroy(User $user)
    {
        // Sécurité: éviter de supprimer un admin (optionnel)
        if (strtolower((string) $user->role) === 'admin') {
            return response()->json(['message' => 'Impossible de supprimer un administrateur'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé'], 200);
    }

    /**
     * Liste des universités
     */
    public function universitiesIndex()
    {
        return University::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'created_at']);
    }

    /**
     * Créer une université
     */
    public function universitiesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:universities,code'],
        ]);

        $uni = University::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
        ]);

        return response()->json([
            'message' => 'Université créée',
            'university' => $uni,
        ], 201);
    }

    /**
     * Supprimer une université
     * - Refuse si des users y sont rattachés (plus sûr)
     */
    public function universitiesDestroy(University $university)
    {
        $hasUsers = User::query()->where('university_id', $university->id)->exists();

        if ($hasUsers) {
            return response()->json([
                'message' => "Impossible de supprimer : des utilisateurs sont rattachés à cette université.",
            ], 422);
        }

        $university->delete();

        return response()->json(['message' => 'Université supprimée'], 200);
    }
}
