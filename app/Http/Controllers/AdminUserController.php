<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Liste complete des utilisateurs (gestion admin).
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['university:id,name'])
            ->orderByDesc('created_at');

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $role = $request->query('role');
        if (in_array($role, ['admin', 'user'], true)) {
            $query->where('role', $role);
        }

        $status = $request->query('status');
        if ($status === 'approved') {
            $query->where('is_approved', true);
        } elseif ($status === 'pending') {
            $query->where('is_approved', false);
        }

        return $query->get([
            'id',
            'name',
            'firstname',
            'email',
            'role',
            'is_approved',
            'approved_at',
            'university_id',
            'created_at',
        ]);
    }

    /**
     * Liste des utilisateurs en attente de validation.
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
                'firstname',
                'email',
                'role',
                'is_approved',
                'approved_at',
                'university_id',
                'created_at',
            ]);
    }

    /**
     * Mettre a jour un utilisateur (role, statut, universite, nom).
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:admin,user'],
            'is_approved' => ['nullable', 'boolean'],
            'university_id' => ['nullable', 'exists:universities,id'],
        ]);

        if (array_key_exists('role', $validated)) {
            $isSelf = (int) $request->user()->id === (int) $user->id;
            if ($isSelf && $validated['role'] !== 'admin') {
                return response()->json([
                    'message' => 'Impossible de retirer votre propre role admin.',
                ], 422);
            }
        }

        if (array_key_exists('is_approved', $validated)) {
            $validated['approved_at'] = $validated['is_approved'] ? now() : null;
        }

        $user->update($validated);
        $user->load('university:id,name');

        return response()->json([
            'message' => 'Utilisateur mis a jour',
            'user' => $user,
        ], 200);
    }

    /**
     * Approuver un utilisateur.
     */
    public function approve(User $user)
    {
        if ($user->is_approved) {
            return response()->json(['message' => 'Utilisateur deja approuve'], 200);
        }

        $user->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Utilisateur approuve'], 200);
    }

    /**
     * Supprimer un utilisateur.
     */
    public function destroy(User $user)
    {
        $authUser = request()->user();
        if ($authUser && (int) $authUser->id === (int) $user->id) {
            return response()->json(['message' => 'Impossible de supprimer votre propre compte.'], 422);
        }

        if (strtolower((string) $user->role) === 'admin') {
            return response()->json(['message' => 'Impossible de supprimer un administrateur'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprime'], 200);
    }

    /**
     * Liste des universites.
     */
    public function universitiesIndex()
    {
        return University::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'created_at']);
    }

    /**
     * Creer une universite.
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
            'message' => 'Universite creee',
            'university' => $uni,
        ], 201);
    }

    /**
     * Supprimer une universite.
     */
    public function universitiesDestroy(University $university)
    {
        $hasUsers = User::query()->where('university_id', $university->id)->exists();

        if ($hasUsers) {
            return response()->json([
                'message' => 'Impossible de supprimer : des utilisateurs sont rattaches a cette universite.',
            ], 422);
        }

        $university->delete();

        return response()->json(['message' => 'Universite supprimee'], 200);
    }
}
