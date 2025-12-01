@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header', [
        'title' => __('Message Details'),
        'description' => __('View details of a sent WhatsApp message.')
    ])
    
    <div class="px-4 flex-auto flex flex-col">
        <div class="max-w-3xl mx-auto w-full">
            <div class="ns-box rounded-lg shadow">
                <div class="p-4 border-b border-box-edge flex justify-between items-center">
                    <h3 class="font-semibold">{{ __('Message Information') }}</h3>
                    @php
                        $statusValue = $log->status instanceof \Modules\WhatsApp\Enums\MessageStatus ? $log->status->value : $log->status;
                        $statusColors = [
                            'pending' => 'bg-warning-tertiary',
                            'sent' => 'bg-info-tertiary',
                            'delivered' => 'bg-success-tertiary',
                            'read' => 'bg-success-primary',
                            'failed' => 'bg-error-tertiary',
                        ];
                        $statusClass = $statusColors[$statusValue] ?? 'bg-secondary';
                    @endphp
                    <span class="{{ $statusClass }} text-white px-3 py-1 rounded-full text-sm">
                        {{ ucfirst($statusValue) }}
                    </span>
                </div>
                
                <div class="p-6">
                    <!-- Recipient Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm text-secondary mb-1">{{ __('Recipient') }}</label>
                            <p class="font-medium">{{ $log->recipient_name ?? '-' }}</p>
                            <p class="text-secondary">{{ $log->recipient_phone }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-secondary mb-1">{{ __('Type') }}</label>
                            <p class="font-medium capitalize">{{ $log->recipient_type }}</p>
                        </div>
                    </div>
                    
                    <!-- Message Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm text-secondary mb-1">{{ __('Message Type') }}</label>
                            <p class="font-medium capitalize">{{ $log->message_type }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-secondary mb-1">{{ __('Template') }}</label>
                            <p class="font-medium">{{ $log->template->label ?? __('Custom Message') }}</p>
                        </div>
                    </div>
                    
                    <!-- Timestamps -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm text-secondary mb-1">{{ __('Created') }}</label>
                            <p class="font-medium">{{ ns()->date->getFormatted($log->created_at) }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-secondary mb-1">{{ __('Sent') }}</label>
                            <p class="font-medium">{{ $log->sent_at ? ns()->date->getFormatted($log->sent_at) : '-' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-secondary mb-1">{{ __('Delivered') }}</label>
                            <p class="font-medium">{{ $log->delivered_at ? ns()->date->getFormatted($log->delivered_at) : '-' }}</p>
                        </div>
                    </div>
                    
                    <!-- WhatsApp Message ID -->
                    @if($log->whatsapp_message_id)
                    <div class="mb-6">
                        <label class="block text-sm text-secondary mb-1">{{ __('WhatsApp Message ID') }}</label>
                        <code class="bg-box-background px-2 py-1 rounded text-sm">{{ $log->whatsapp_message_id }}</code>
                    </div>
                    @endif
                    
                    <!-- Content -->
                    <div class="mb-6">
                        <label class="block text-sm text-secondary mb-1">{{ __('Message Content') }}</label>
                        <div class="bg-box-background rounded-lg p-4">
                            {!! nl2br(e($log->content)) !!}
                        </div>
                    </div>
                    
                    <!-- Error Message -->
                    @if($log->error_message)
                    <div class="mb-6">
                        <label class="block text-sm text-secondary mb-1">{{ __('Error') }}</label>
                        <div class="bg-error-tertiary/10 border border-error-tertiary rounded-lg p-4 text-error-tertiary">
                            {{ $log->error_message }}
                        </div>
                    </div>
                    @endif
                    
                    <!-- Related Entity -->
                    @if($log->related_type && $log->related_id)
                    <div class="mb-6">
                        <label class="block text-sm text-secondary mb-1">{{ __('Related To') }}</label>
                        <p class="font-medium">
                            {{ class_basename($log->related_type) }} #{{ $log->related_id }}
                        </p>
                    </div>
                    @endif
                    
                    <!-- Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-box-edge">
                        <a href="{{ ns()->route('whatsapp.logs') }}" class="ns-button default">
                            <i class="las la-arrow-left mr-2"></i>
                            {{ __('Back to Logs') }}
                        </a>
                        
                        <div class="flex gap-3">
                            @if($statusValue === 'failed')
                            <button type="button" onclick="retryMessage({{ $log->id }})" class="ns-button warning">
                                <i class="las la-redo mr-2"></i>
                                {{ __('Retry') }}
                            </button>
                            @endif
                            
                            <button type="button" onclick="deleteLog({{ $log->id }})" class="ns-button error">
                                <i class="las la-trash mr-2"></i>
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('layout.dashboard.footer.inject')
<script>
function retryMessage(id) {
    if (!confirm('{{ __("Retry sending this message?") }}')) return;
    
    nsHttpClient.post('/api/whatsapp/logs/' + id + '/retry')
        .subscribe({
            next: function(response) {
                nsSnackBar.success('{{ __("Message queued for retry") }}');
                location.reload();
            },
            error: function(error) {
                nsSnackBar.error(error.message || '{{ __("Failed to retry message") }}');
            }
        });
}

function deleteLog(id) {
    if (!confirm('{{ __("Are you sure you want to delete this log entry?") }}')) return;
    
    nsHttpClient.delete('/api/crud/whatsapp.logs/' + id)
        .subscribe({
            next: function(response) {
                nsSnackBar.success('{{ __("Log deleted successfully") }}');
                window.location.href = '{{ ns()->route("whatsapp.logs") }}';
            },
            error: function(error) {
                nsSnackBar.error(error.message || '{{ __("Failed to delete log") }}');
            }
        });
}
</script>
@endsection
