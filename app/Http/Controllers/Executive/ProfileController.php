<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExecutiveProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('executive.profile.show', [
            'user' => auth()->user(),
        ]);
    }

    public function edit(): View
    {
        return view('executive.profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(ExecutiveProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('executive.profile.show')
            ->with('success', 'Profile updated successfully.');
    }
}
