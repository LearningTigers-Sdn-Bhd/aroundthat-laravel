<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\MembershipData;
use App\Data\Admin\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserBusinessController extends Controller
{
    /**
     * The Businesses tab of a login: the businesses it belongs to, its role and outlets in each.
     */
    public function index(User $user): Response
    {
        $user->load('suspendedBy');

        return Inertia::render('admin/users/businesses', [
            'user' => UserData::fromModel($user),
            'memberships' => MembershipData::collect(
                $user->memberships()->with(['business', 'outlets'])->get()->sortBy('business.name')->values(),
            ),
        ]);
    }
}
