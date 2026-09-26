<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\BusinessData;
use App\Data\InvitationData;
use App\Data\MemberData;
use App\Http\Controllers\Controller;
use App\Models\Business;
use Inertia\Inertia;
use Inertia\Response;

class BusinessMemberController extends Controller
{
    /**
     * The Members tab of a business: its staff and their open invitations.
     */
    public function index(Business $business): Response
    {
        $business->load(['approvedBy', 'suspendedBy']);

        return Inertia::render('admin/businesses/members', [
            'business' => BusinessData::fromModel($business),
            'members' => MemberData::collect(
                $business->memberships()->with(['user', 'outlets'])->get()->sortBy('user.name')->values(),
            ),
            'invitations' => InvitationData::collect(
                $business->invitations()->open()->with(['invitedBy', 'outlets'])->latest()->get(),
            ),
        ]);
    }
}
