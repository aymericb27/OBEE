<?php

// app/Http/Middleware/ApprovedMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->is_approved) {
            Auth::logout();
            return redirect('/login')->withErrors([
                'email' => "Votre compte n'est pas approuvé.",
            ]);
        }

        return $next($request);
    }
}
