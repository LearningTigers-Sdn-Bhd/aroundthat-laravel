<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Routes a user with a temporary password may still reach.
     *
     * @var list<string>
     */
    protected const ALLOWED_ROUTES = [
        'security.edit',
        'user-password.update',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'logout',
    ];

    /**
     * Send a user with a temporary password to the security page until they change it.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->must_change_password || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        Inertia::flash('toast', ['type' => 'warning', 'message' => __('Set a new password to continue.')]);

        return redirect()->route('security.edit');
    }
}
