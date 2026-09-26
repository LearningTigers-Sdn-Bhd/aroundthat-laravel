<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\ActivityData;
use App\Data\Admin\UserData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserActivityController extends Controller
{
    /**
     * The Activity tab of a login: the latest changes to it, including recovery steps.
     */
    public function index(User $user): Response
    {
        $user->load('suspendedBy');

        return Inertia::render('admin/users/activity', [
            'user' => UserData::fromModel($user),
            'activities' => Inertia::defer(fn () => ActivityData::collect(
                Activity::forSubject($user)->with('causer')->latest('id')->limit(100)->get(),
            )),
        ]);
    }
}
