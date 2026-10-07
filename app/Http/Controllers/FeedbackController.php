<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['message' => ['required', 'string', 'min:5', 'max:1200']]);
        Feedback::create(['user_id' => $request->user()->id, 'message' => $validated['message']]);

        return back()->with('success', 'Thanks — your feedback was submitted.');
    }
}
