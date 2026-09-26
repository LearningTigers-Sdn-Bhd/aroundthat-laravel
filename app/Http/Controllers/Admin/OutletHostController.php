<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Outlets\SetHostOutlet;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin records which outlet another one sits inside, or clears it.
 */
class OutletHostController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function update(Request $request, Outlet $outlet, SetHostOutlet $setHost): RedirectResponse
    {
        $validated = $request->validate([
            'host_outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
        ]);

        $setHost->assign($outlet, Outlet::whereKey($validated['host_outlet_id'])->firstOrFail());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Host outlet saved.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Outlet $outlet, SetHostOutlet $setHost): RedirectResponse
    {
        $setHost->clear($outlet);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Host outlet cleared.')]);

        return back();
    }
}
