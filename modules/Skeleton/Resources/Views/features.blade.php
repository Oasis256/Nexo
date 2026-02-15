@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    
    <div class="px-4 flex-auto flex flex-col overflow-hidden">
        <div class="py-4">
            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Items') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['total_items'] }}</p>
                        </div>
                        <i class="las la-boxes text-4xl text-blue-500"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Active Items') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['active_items'] }}</p>
                        </div>
                        <i class="las la-check-circle text-4xl text-green-500"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Value') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">${{ number_format($stats['total_value'], 2) }}</p>
                        </div>
                        <i class="las la-dollar-sign text-4xl text-purple-500"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Categories') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['categories'] }}</p>
                        </div>
                        <i class="las la-tags text-4xl text-yellow-500"></i>
                    </div>
                </div>
            </div>

            {{-- Feature Showcase --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                    {{ __('Advanced Features') }}
                </h3>

                <div class="space-y-4">
                    {{-- Vue Component Integration --}}
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="las la-code mr-2"></i>{{ __('Vue Component Integration') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Modules can include custom Vue components for interactive UI elements.') }}
                        </p>
                    </div>

                    {{-- API Integration --}}
                    <div class="border-l-4 border-green-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="las la-cloud mr-2"></i>{{ __('RESTful API Endpoints') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Define custom API routes for external integrations and AJAX operations.') }}
                        </p>
                    </div>

                    {{-- Event System --}}
                    <div class="border-l-4 border-purple-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="las la-broadcast-tower mr-2"></i>{{ __('Event System') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Hook into core events or create custom events for module communication.') }}
                        </p>
                    </div>

                    {{-- Permissions --}}
                    <div class="border-l-4 border-yellow-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="las la-lock mr-2"></i>{{ __('Permission System') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Implement granular permissions for different user roles and actions.') }}
                        </p>
                    </div>

                    {{-- Settings --}}
                    <div class="border-l-4 border-red-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="las la-sliders-h mr-2"></i>{{ __('Module Settings') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Create custom settings pages integrated with NexoPOS settings system.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Code Examples --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                    {{ __('Integration Points') }}
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Hooks Available') }}</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>→ {{ __('Menu registration') }}</li>
                            <li>→ {{ __('Dashboard widgets') }}</li>
                            <li>→ {{ __('Order processing') }}</li>
                            <li>→ {{ __('Product management') }}</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Extendable Areas') }}</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>→ {{ __('Custom fields') }}</li>
                            <li>→ {{ __('Email templates') }}</li>
                            <li>→ {{ __('Report generation') }}</li>
                            <li>→ {{ __('Payment gateways') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
