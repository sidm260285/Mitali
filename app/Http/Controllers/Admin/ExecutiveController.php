<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExecutiveRequest;
use App\Http\Requests\UpdateExecutiveRequest;
use App\Models\User;
use App\Support\PasswordGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::executives()->orderBy('name');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%'.$request->string('phone').'%');
        }

        $executives = $query->paginate(25)->withQueryString();

        return view('admin.executives.index', compact('executives'));
    }

    public function create(): View
    {
        return view('admin.executives.create', [
            'defaultPassword' => PasswordGenerator::random(),
        ]);
    }

    public function store(StoreExecutiveRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'role' => User::ROLE_EXECUTIVE,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        return redirect()->route('admin.executives.index')
            ->with('success', 'Executive created successfully.');
    }

    public function show(User $executive): View
    {
        $this->ensureExecutive($executive);

        return view('admin.executives.show', compact('executive'));
    }

    public function edit(User $executive): View
    {
        $this->ensureExecutive($executive);

        return view('admin.executives.edit', compact('executive'));
    }

    public function update(UpdateExecutiveRequest $request, User $executive): RedirectResponse
    {
        $this->ensureExecutive($executive);

        $executive->update($request->validated());

        return redirect()->route('admin.executives.index')
            ->with('success', 'Executive updated successfully.');
    }

    public function deactivate(User $executive): RedirectResponse
    {
        $this->ensureExecutive($executive);

        $executive->update(['is_active' => false]);

        return redirect()->route('admin.executives.index')
            ->with('success', 'Executive deactivated successfully.');
    }

    public function activate(User $executive): RedirectResponse
    {
        $this->ensureExecutive($executive);

        $executive->update(['is_active' => true]);

        return redirect()->route('admin.executives.index')
            ->with('success', 'Executive activated successfully.');
    }

    public function resetPassword(User $executive): RedirectResponse
    {
        $this->ensureExecutive($executive);

        $newPassword = PasswordGenerator::random();

        $executive->update([
            'password' => $newPassword,
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('admin.executives.reset-password.reveal', $executive)
            ->with('reset_password', $newPassword)
            ->with('success', 'Password reset successfully. Copy the new password now.');
    }

    public function revealResetPassword(User $executive): View|RedirectResponse
    {
        $this->ensureExecutive($executive);

        $password = session('reset_password');

        if (! $password) {
            return redirect()->route('admin.executives.index')
                ->with('error', 'Password reveal is no longer available. Please reset the password again.');
        }

        session()->forget('reset_password');

        return view('admin.executives.reset-password-reveal', [
            'executive' => $executive,
            'password' => $password,
        ]);
    }

    private function ensureExecutive(User $executive): void
    {
        if (! $executive->isExecutive()) {
            abort(404);
        }
    }
}
