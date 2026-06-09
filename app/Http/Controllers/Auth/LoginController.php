<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('username', 'password');

        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        if (! $user || ! $user->is_active) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Invalid credentials or account is deactivated.']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Invalid credentials or account is deactivated.']);
        }

        $request->session()->regenerate();

        $authenticatedUser = Auth::user();

        if ($authenticatedUser->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return redirect()->intended($authenticatedUser->dashboardRoute());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
