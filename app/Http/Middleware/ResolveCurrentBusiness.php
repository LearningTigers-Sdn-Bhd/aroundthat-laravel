<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use App\Support\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves which business the user is working in. Users with one active membership never have to choose.
 */
class ResolveCurrentBusiness
{
    public const SESSION_KEY = 'current_business_id';

    public function __construct(protected Workspace $workspace) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $memberships = $user->memberships()->active()->with('business')->get();

        $current = $memberships->firstWhere('business_id', $request->session()->get(self::SESSION_KEY))
            ?? ($memberships->count() === 1 ? $memberships->first() : null);

        if (! $current instanceof Membership) {
            return match (true) {
                $memberships->isNotEmpty() => redirect()->route('workspace.choose'),
                $user->can('admin') => redirect()->route('admin.dashboard'),
                default => redirect()->route('workspace.none'),
            };
        }

        $request->session()->put(self::SESSION_KEY, $current->business_id);

        $this->workspace->set($current->setRelation('user', $user));

        return $next($request);
    }
}
