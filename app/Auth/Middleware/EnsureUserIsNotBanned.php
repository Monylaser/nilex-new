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

        // حساب مُجمّد (anonymized): دفاع صريح إضافي يطرد الجلسة فوراً حتى لو
        // تطابق الباسورد بأي شكل أو بقيت جلسة قديمة سارية.
        if ($user?->isAnonymized()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('server.account.deleted_login_blocked')]);
        }

        if ($user?->is_banned) {
            $reason = $user->ban_reason ?? __('server.auth.ban_reason_default');

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('server.auth.banned', ['reason' => $reason])]);
        }

        return $next($request);
    }
}
