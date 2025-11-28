@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    
    <div class="px-4 flex-auto flex flex-col overflow-hidden">
        <div class="py-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                    {{ __('Module Settings') }}
                </h3>
                
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Configure module-specific settings here. This demonstrates how to create custom settings pages.') }}
                </p>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-500">
                        {{ __('To implement full settings functionality, create a Settings class extending SettingsPage and register it in the module provider.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
