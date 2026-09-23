<?php

namespace App\Http\Controllers;

use App\Data\WorkspaceOptionData;
use App\Http\Middleware\ResolveCurrentBusiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    /**
     * Ask a user with several businesses which one to work in.
     */
    public function choose(Request $request): Response|RedirectResponse
    {
        $memberships = $request->user()->memberships()->active()->with('business')->get();

        if ($memberships->isEmpty()) {
            return redirect()->route('workspace.none');
        }

        return Inertia::render('workspace/choose', [
            'options' => WorkspaceOptionData::collect($memberships->sortBy('business.name')->values()),
        ]);
    }

    /**
     * Switch to another business the user is an active member of.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'uuid'],
        ]);

        $membership = $request->user()->memberships()->active()->where('business_id', $validated['business_id'])->first();

        abort_if($membership === null, 403);

        $request->session()->put(ResolveCurrentBusiness::SESSION_KEY, $membership->business_id);

        return redirect()->route('dashboard');
    }

    /**
     * Tell a user who belongs to no business that there is nothing to open yet.
     */
    public function none(Request $request): Response|RedirectResponse
    {
        if ($request->user()->memberships()->active()->exists()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('workspace/none');
    }
}
