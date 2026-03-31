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

        <div x-data="c2bRegistration()" class="pt-2 space-y-3">
            <button
                type="button"
                @click="register()"
                :disabled="loading"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold transition disabled:bg-blue-300 disabled:cursor-not-allowed"
            >
                <span x-show="!loading">Register C2B URLs</span>
                <span x-show="loading">Working...</span>
            </button>

            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <span class="font-semibold">1. Generate Access Token:</span>
                    <span :class="stepTokenClass()" x-text="stepTokenText()"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-semibold">2. Register C2B URLs:</span>
                    <span :class="stepRegisterClass()" x-text="stepRegisterText()"></span>
                </div>
            </div>

            <div x-show="message" class="text-sm rounded px-3 py-2" :class="success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'" x-text="message"></div>
        </div>

        <p class="text-xs text-gray-500">
            This calls Safaricom C2B register URL API using your configured production/sandbox credentials.
        </p>
    </div>
</div>

<script>
function c2bRegistration() {
    return {
        loading: false,
        tokenStatus: 'idle',      // idle|processing|completed|failed
        registerStatus: 'idle',   // idle|processing|completed|failed
        success: false,
        message: '',
        stepTokenText() {
            if (this.tokenStatus === 'processing') return 'Generating...';
            if (this.tokenStatus === 'completed') return 'Completed';
            if (this.tokenStatus === 'failed') return 'Failed';
            return 'Pending';
        },
        stepRegisterText() {
            if (this.registerStatus === 'processing') return 'Registering...';
            if (this.registerStatus === 'completed') return 'Completed';
            if (this.registerStatus === 'failed') return 'Failed';
            return 'Pending';
        },
        stepTokenClass() {
            if (this.tokenStatus === 'completed') return 'text-green-700 font-semibold';
            if (this.tokenStatus === 'failed') return 'text-red-700 font-semibold';
            if (this.tokenStatus === 'processing') return 'text-blue-700 font-semibold';
            return 'text-gray-500';
        },
        stepRegisterClass() {
            if (this.registerStatus === 'completed') return 'text-green-700 font-semibold';
            if (this.registerStatus === 'failed') return 'text-red-700 font-semibold';
            if (this.registerStatus === 'processing') return 'text-blue-700 font-semibold';
            return 'text-gray-500';
        },
        async register() {
            this.loading = true;
            this.success = false;
            this.message = '';
            this.tokenStatus = 'processing';
            this.registerStatus = 'idle';

            try {
                const response = await fetch('{{ route('mpesa.registerC2BUrls') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ response_type: 'Completed' }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    if (data.phase === 'token_generation') {
                        this.tokenStatus = 'failed';
                        this.registerStatus = 'idle';
                    } else if (data.phase === 'url_registration') {
                        this.tokenStatus = 'completed';
                        this.registerStatus = 'failed';
                    } else {
                        this.tokenStatus = 'failed';
                        this.registerStatus = 'failed';
                    }
                    this.message = data.error || data.message || 'Registration failed.';
                    return;
                }

                this.tokenStatus = 'completed';
                this.registerStatus = 'completed';
                this.success = true;
                this.message = data.message || 'C2B URLs registered successfully.';
            } catch (e) {
                this.tokenStatus = 'failed';
                this.registerStatus = 'failed';
                this.message = 'Request failed. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
