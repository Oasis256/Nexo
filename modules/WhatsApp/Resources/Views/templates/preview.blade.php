@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include(Hook::filter('ns-dashboard-header-file', '../common/dashboard-header'))
    
    <div id="dashboard-content" class="px-4 flex-auto flex flex-col">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-primary font-bold">{{ $title }}</h3>
            <p class="text-secondary">{{ $description }}</p>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <!-- Template Info -->
            <div class="w-full md:w-1/3">
                <div class="ns-box rounded-lg shadow">
                    <div class="ns-box-header p-3 border-b border-box-edge">
                        <h4 class="font-semibold">{{ __m('Template Details', 'WhatsApp') }}</h4>
                    </div>
                    <div class="ns-box-body p-4">
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs text-secondary uppercase">{{ __m('Name', 'WhatsApp') }}</label>
                                <p class="font-medium">{{ $template->name }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-secondary uppercase">{{ __m('Label', 'WhatsApp') }}</label>
                                <p class="font-medium">{{ $template->label }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-secondary uppercase">{{ __m('Event', 'WhatsApp') }}</label>
                                <p class="font-medium">{{ $template->event ?? __m('Manual', 'WhatsApp') }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-secondary uppercase">{{ __m('Target', 'WhatsApp') }}</label>
                                <p class="font-medium capitalize">{{ $template->target?->value ?? $template->target ?? __m('N/A', 'WhatsApp') }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-secondary uppercase">{{ __m('Status', 'WhatsApp') }}</label>
                                @if($template->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-success-tertiary text-white">
                                        {{ __m('Active', 'WhatsApp') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-error-tertiary text-white">
                                        {{ __m('Inactive', 'WhatsApp') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Placeholders -->
                <div class="ns-box rounded-lg shadow mt-4">
                    <div class="ns-box-header p-3 border-b border-box-edge">
                        <h4 class="font-semibold">{{ __m('Available Placeholders', 'WhatsApp') }}</h4>
                    </div>
                    <div class="ns-box-body p-4">
                        <div class="max-h-64 overflow-y-auto">
                            <div class="text-xs space-y-1">
                                @foreach($placeholders as $placeholder => $description)
                                    <div class="flex justify-between items-start py-1 border-b border-box-edge last:border-0">
                                        <code class="text-info-tertiary">{{ '{' . $placeholder . '}' }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="w-full md:w-2/3">
                <div class="ns-box rounded-lg shadow">
                    <div class="ns-box-header p-3 border-b border-box-edge flex justify-between items-center">
                        <h4 class="font-semibold">{{ __m('Message Preview', 'WhatsApp') }}</h4>
                        <span class="text-xs text-secondary">{{ __m('Sample data is used for preview', 'WhatsApp') }}</span>
                    </div>
                    <div class="ns-box-body p-4">
                        <!-- WhatsApp-style message preview -->
                        <div class="max-w-md mx-auto">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 shadow-sm border border-green-200 dark:border-green-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                                        <i class="las la-store text-white"></i>
                                    </div>
                                    <span class="font-medium text-green-800 dark:text-green-200">{{ ns()->option->get('ns_store_name', 'My Store') }}</span>
                                </div>
                                <div class="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200 leading-relaxed">{{ $preview }}</div>
                                <div class="mt-2 text-right">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ date('H:i') }}</span>
                                    <i class="las la-check-double text-green-500 ml-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Original Template -->
                <div class="ns-box rounded-lg shadow mt-4">
                    <div class="ns-box-header p-3 border-b border-box-edge">
                        <h4 class="font-semibold">{{ __m('Original Template Content', 'WhatsApp') }}</h4>
                    </div>
                    <div class="ns-box-body p-4">
                        <pre class="whitespace-pre-wrap text-sm bg-input-background p-4 rounded border border-input-edge font-mono">{{ $template->content }}</pre>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 mt-4">
                    <a href="{{ ns()->route('whatsapp.templates') }}" class="ns-button info">
                        <i class="las la-arrow-left mr-2"></i>
                        {{ __m('Back to Templates', 'WhatsApp') }}
                    </a>
                    <a href="{{ ns()->url('dashboard/whatsapp/templates/edit/' . $template->id) }}" class="ns-button default">
                        <i class="las la-edit mr-2"></i>
                        {{ __m('Edit Template', 'WhatsApp') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
