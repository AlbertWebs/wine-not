@extends('layouts.app')

@section('title', 'M-Pesa C2B URL Registration')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">M-Pesa C2B URL Registration</h1>
        <p class="text-gray-600 mt-1">Register confirmation and validation URLs with Safaricom.</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Environment</p>
                <p class="font-semibold text-gray-900">{{ $config['environment'] ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Shortcode</p>
                <p class="font-semibold text-gray-900">{{ $config['shortcode'] ?? 'N/A' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-gray-500">Confirmation URL</p>
                <p class="font-mono text-xs text-gray-900 break-all">{{ $config['confirmation_url'] ?? 'N/A' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-gray-500">Validation URL</p>
                <p class="font-mono text-xs text-gray-900 break-all">{{ $config['validation_url'] ?? 'N/A' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('mpesa.registerC2BUrls') }}" class="pt-2">
            @csrf
            <input type="hidden" name="response_type" value="Completed">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold transition">
                Register C2B URLs
            </button>
        </form>

        <p class="text-xs text-gray-500">
            This calls Safaricom C2B register URL API using your configured production/sandbox credentials.
        </p>
    </div>
</div>
@endsection
