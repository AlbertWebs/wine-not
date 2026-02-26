<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:191', Rule::unique('users', 'username')->ignore($user->id)],
            'pin' => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
        ], [
            'username.unique' => 'This username is already taken.',
            'pin.size' => 'PIN must be exactly 4 digits.',
            'pin.regex' => 'PIN must contain only numbers (0-9).',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;

        if ($request->filled('pin')) {
            $user->pin = $request->pin; // Model Hashed cast will hash it
        }

        $user->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Your profile has been updated.');
    }
}
