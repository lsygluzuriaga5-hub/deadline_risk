<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'profile_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('profile_image')) {
            if ($request->user()->profile_image) {
                Storage::disk('public')->delete($request->user()->profile_image);
            }
            $validated['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $request->user()->update($validated);

        return back()->with('success', 'Student profile updated.');
    }
}
