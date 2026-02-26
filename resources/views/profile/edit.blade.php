@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
        <p class="text-gray-600 mt-1">Update your name, username, and PIN</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                <input type="text" name="name" id="name" required
                    value="{{ old('name', $user->name) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-wine-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    placeholder="Your full name">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                <input type="text" name="username" id="username" required
                    value="{{ old('username', $user->username) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-wine-500 focus:border-transparent @error('username') border-red-500 @enderror"
                    placeholder="Login username">
                <p class="mt-1 text-xs text-gray-500">Must be unique. Leave as is if you don't want to change it.</p>
                @error('username')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="pin" class="block text-sm font-medium text-gray-700 mb-2">New PIN (4 digits)</label>
                <input type="text" name="pin" id="pin" maxlength="4" pattern="[0-9]{4}" inputmode="numeric"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-wine-500 focus:border-transparent @error('pin') border-red-500 @enderror"
                    placeholder="Leave blank to keep current PIN">
                <p class="mt-1 text-xs text-gray-500">Enter exactly 4 digits (0–9), or leave blank to keep your current PIN.</p>
                @error('pin')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ url()->previous() }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-wine-600 text-white rounded-lg hover:bg-wine-700 focus:ring-2 focus:ring-wine-500">
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('pin')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
});
</script>
@endsection
