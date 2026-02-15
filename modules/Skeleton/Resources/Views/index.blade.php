@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    
    <div class="px-4 flex-auto flex flex-col overflow-hidden">
        <div class="py-4">
            {{-- Welcome Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                    {{ __('Welcome to Skeleton Module') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('This is a comprehensive demonstration of NexoPOS module capabilities.') }}
                </p>
            </div>

            {{-- Quick Links --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <a href="{{ route('skeleton.items.list') }}" class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-6 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">{{ __('Manage Items') }}</h3>
                            <p class="text-sm opacity-90">{{ __('View and manage all items') }}</p>
                        </div>
                        <i class="las la-boxes text-4xl"></i>
                    </div>
                </a>

                <a href="{{ route('skeleton.features') }}" class="bg-green-500 hover:bg-green-600 text-white rounded-lg p-6 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">{{ __('Features') }}</h3>
                            <p class="text-sm opacity-90">{{ __('Explore module features') }}</p>
                        </div>
                        <i class="las la-star text-4xl"></i>
                    </div>
                </a>

                <a href="{{ route('skeleton.settings') }}" class="bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-6 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">{{ __('Settings') }}</h3>
                            <p class="text-sm opacity-90">{{ __('Configure module settings') }}</p>
                        </div>
                        <i class="las la-cog text-4xl"></i>
                    </div>
                </a>
            </div>

            {{-- Documentation Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                    {{ __('Module Capabilities Demonstrated') }}
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('CRUD Operations') }}</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>✓ {{ __('Full CRUD implementation') }}</li>
                            <li>✓ {{ __('Form validation') }}</li>
                            <li>✓ {{ __('Bulk actions') }}</li>
                            <li>✓ {{ __('Search and filtering') }}</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-green-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Routes & Navigation') }}</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>✓ {{ __('Web routes') }}</li>
                            <li>✓ {{ __('API routes') }}</li>
                            <li>✓ {{ __('Menu integration') }}</li>
                            <li>✓ {{ __('Permission middleware') }}</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-purple-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Database & Models') }}</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>✓ {{ __('Migrations') }}</li>
                            <li>✓ {{ __('Eloquent models') }}</li>
                            <li>✓ {{ __('Relationships') }}</li>
                            <li>✓ {{ __('Query scopes') }}</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-yellow-500 pl-4">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Services & Logic') }}</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>✓ {{ __('Service classes') }}</li>
                            <li>✓ {{ __('Business logic') }}</li>
                            <li>✓ {{ __('Event handling') }}</li>
                            <li>✓ {{ __('Custom helpers') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
