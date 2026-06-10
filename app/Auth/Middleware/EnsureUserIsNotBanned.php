<?php

namespace App\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->is_banned) {
            $reason = $user->ban_reason ?? 'مخالفة سياسات المنصة';

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => "تم تعليق حسابك. السبب: {$reason}"]);
        }

        return $next($request);
    }
}
