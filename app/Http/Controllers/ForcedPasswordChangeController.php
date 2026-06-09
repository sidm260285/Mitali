<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForcePasswordChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ForcedPasswordChangeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()?->must_change_password) {
            return redirect()->to($request->user()->dashboardRoute());
        }

        return view('auth.force-password-change');
    }

    public function update(ForcePasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'password' => $request->validated('password'),
            'must_change_password' => false,
        ]);

        return redirect()->to($user->dashboardRoute())
            ->with('success', 'Password updated successfully.');
    }
}
