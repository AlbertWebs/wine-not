<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function resetPin(Request $request, $id)
    {
        $request->validate([
            'new_pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
        ], [
            'new_pin.size' => 'PIN must be exactly 4 digits.',
            'new_pin.regex' => 'PIN must contain only numbers (0-9).',
        ]);

        $user = User::findOrFail($id);
        $user->pin = $request->new_pin; // Model Hashed cast will hash it
        $user->resetLoginAttempts(); // Also reset login attempts when PIN is reset
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', "PIN has been reset for {$user->name}.");
    }

    public function unlockAccount($id)
    {
        $user = User::findOrFail($id);
        $user->resetLoginAttempts();
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', "Account unlocked for {$user->name}.");
    }

    public function resetAttempts($id)
    {
        $user = User::findOrFail($id);
        $user->resetLoginAttempts();
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', "Login attempts reset for {$user->name}.");
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:191|unique:users,username',
            'pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'role' => ['required', 'string', Rule::in(['super_admin', 'cashier'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ], [
            'username.unique' => 'This username is already taken.',
            'pin.size' => 'PIN must be exactly 4 digits.',
            'pin.regex' => 'PIN must contain only numbers (0-9).',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'pin' => $request->pin, // Model Hashed cast will hash it
            'role' => $request->role,
            'status' => $request->status,
            'login_attempts' => 0,
        ]);

        // Assign role using Spatie if available
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($request->role);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been created successfully.");
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:191', Rule::unique('users', 'username')->ignore($id)],
            'pin' => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'role' => ['required', 'string', Rule::in(['super_admin', 'cashier'])],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ], [
            'username.unique' => 'This username is already taken.',
            'pin.size' => 'PIN must be exactly 4 digits.',
            'pin.regex' => 'PIN must contain only numbers (0-9).',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->role = $request->role;
        $user->status = $request->status;

        // Only update PIN if provided
        if ($request->filled('pin')) {
            $user->pin = $request->pin; // Model Hashed cast will hash it
        }

        $user->save();

        // Update role using Spatie
        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles([$request->role]);
        } elseif (method_exists($user, 'assignRole')) {
            $user->assignRole($request->role);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been updated successfully.");
    }
}
