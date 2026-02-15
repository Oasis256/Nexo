@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header', [
        'title' => __('WhatsApp Dashboard'),
        'description' => __('Overview of WhatsApp messaging activity and statistics.')
    ])
    
    <div class="px-4 flex-auto flex flex-col">
        @if(!$isConfigured)
        <!-- Configuration Required Alert -->
        <div class="ns-box rounded-lg shadow p-4 mb-6 border-l-4 border-warning-tertiary">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-warning-tertiary flex items-center justify-center mr-4">
                    <i class="las la-exclamation-triangle text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-warning-tertiary">{{ __m('Configuration Required', 'WhatsApp') }}</h3>
                    <p class="text-secondary text-sm">{{ __m('WhatsApp API is not configured. Please add your API credentials to start sending messages.', 'WhatsApp') }}</p>
                </div>
                <a href="{{ ns()->route('ns.dashboard.settings', ['settings' => 'whatsapp.settings']) }}" 
                   class="ns-button info px-4 py-2 rounded-lg">
                    <i class="las la-cog mr-2"></i>{{ __m('Configure Now', 'WhatsApp') }}
                </a>
            </div>
        </div>
        @elseif(!$isEnabled)
        <!-- WhatsApp Disabled Alert -->
        <div class="ns-box rounded-lg shadow p-4 mb-6 border-l-4 border-info-tertiary">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-info-tertiary flex items-center justify-center mr-4">
                    <i class="las la-info-circle text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-info-tertiary">{{ __m('WhatsApp Disabled', 'WhatsApp') }}</h3>
                    <p class="text-secondary text-sm">{{ __m('WhatsApp integration is currently disabled. Enable it in settings to start sending messages.', 'WhatsApp') }}</p>
                </div>
                <a href="{{ ns()->route('ns.dashboard.settings', ['settings' => 'whatsapp.settings']) }}" 
                   class="ns-button info px-4 py-2 rounded-lg">
                    <i class="las la-cog mr-2"></i>{{ __m('Go to Settings', 'WhatsApp') }}
                </a>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Messages -->
            <div class="ns-box rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-secondary text-sm">{{ __('Total Messages') }}</p>
                        <p class="text-2xl font-bold" id="stat-total">-</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-info-tertiary flex items-center justify-center">
                        <i class="las la-comments text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            
            <!-- Delivered -->
            <div class="ns-box rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-secondary text-sm">{{ __('Delivered') }}</p>
                        <p class="text-2xl font-bold text-success-tertiary" id="stat-delivered">-</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-success-tertiary flex items-center justify-center">
                        <i class="las la-check-double text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            
            <!-- Pending -->
            <div class="ns-box rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-secondary text-sm">{{ __('Pending') }}</p>
                        <p class="text-2xl font-bold text-warning-tertiary" id="stat-pending">-</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-warning-tertiary flex items-center justify-center">
                        <i class="las la-clock text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            
            <!-- Failed -->
            <div class="ns-box rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-secondary text-sm">{{ __('Failed') }}</p>
                        <p class="text-2xl font-bold text-error-tertiary" id="stat-failed">-</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-error-tertiary flex items-center justify-center">
                        <i class="las la-times text-2xl text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <a href="{{ ns()->route('whatsapp.send') }}" class="ns-box rounded-lg shadow p-6 hover:shadow-lg transition-shadow flex items-center">
                <div class="w-12 h-12 rounded-full bg-success-tertiary flex items-center justify-center mr-4">
                    <i class="las la-paper-plane text-2xl text-white"></i>
                </div>
                <div>
                    <h3 class="font-semibold">{{ __('Send Message') }}</h3>
                    <p class="text-secondary text-sm">{{ __('Send a new WhatsApp message') }}</p>
                </div>
            </a>
            
            <a href="{{ ns()->route('whatsapp.templates') }}" class="ns-box rounded-lg shadow p-6 hover:shadow-lg transition-shadow flex items-center">
                <div class="w-12 h-12 rounded-full bg-info-tertiary flex items-center justify-center mr-4">
                    <i class="las la-file-alt text-2xl text-white"></i>
                </div>
                <div>
                    <h3 class="font-semibold">{{ __('Message Templates') }}</h3>
                    <p class="text-secondary text-sm">{{ __('Manage notification templates') }}</p>
                </div>
            </a>
            
            <a href="{{ ns()->route('whatsapp.logs') }}" class="ns-box rounded-lg shadow p-6 hover:shadow-lg transition-shadow flex items-center">
                <div class="w-12 h-12 rounded-full bg-warning-tertiary flex items-center justify-center mr-4">
                    <i class="las la-history text-2xl text-white"></i>
                </div>
                <div>
                    <h3 class="font-semibold">{{ __('Message Logs') }}</h3>
                    <p class="text-secondary text-sm">{{ __('View sent messages history') }}</p>
                </div>
            </a>
        </div>
        
        <!-- Recent Messages -->
        <div class="ns-box rounded-lg shadow flex-1 flex flex-col">
            <div class="p-4 border-b border-box-edge flex justify-between items-center">
                <h3 class="font-semibold">{{ __('Recent Messages') }}</h3>
                <a href="{{ ns()->route('whatsapp.logs') }}" class="text-info-tertiary hover:underline text-sm">
                    {{ __('View All') }}
                </a>
            </div>
            <div class="flex-1 p-4" id="recent-messages">
                <div class="flex items-center justify-center h-full">
                    <div class="text-center text-secondary">
                        <i class="las la-spinner la-spin text-4xl"></i>
                        <p class="mt-2">{{ __('Loading...') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('layout.dashboard.footer.inject')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load statistics
    nsHttpClient.get('/api/whatsapp/statistics')
        .subscribe({
            next: function(response) {
                const stats = response.data || response;
                document.getElementById('stat-total').textContent = stats.total || 0;
                document.getElementById('stat-delivered').textContent = stats.delivered || 0;
                document.getElementById('stat-pending').textContent = stats.pending || 0;
                document.getElementById('stat-failed').textContent = stats.failed || 0;
            },
            error: function(error) {
                console.error('Failed to load statistics', error);
            }
        });
    
    // Load recent messages
    nsHttpClient.get('/api/whatsapp/logs?per_page=10')
        .subscribe({
            next: function(response) {
                const container = document.getElementById('recent-messages');
                // Handle paginated response: response.data contains the paginator, response.data.data has the actual logs
                const logs = (response.data && response.data.data) ? response.data.data : (Array.isArray(response.data) ? response.data : []);
                
                if (logs.length === 0) {
                    container.innerHTML = '<div class="text-center text-secondary py-8"><i class="las la-inbox text-4xl"></i><p class="mt-2">{{ __("No messages yet") }}</p></div>';
                    return;
                }
                
                let html = '<div class="space-y-3">';
                logs.forEach(function(log) {
                    const statusClass = {
                        'pending': 'text-warning-tertiary',
                        'sent': 'text-info-tertiary',
                        'delivered': 'text-success-tertiary',
                        'read': 'text-success-primary',
                        'failed': 'text-error-tertiary'
                    }[log.status] || '';
                    
                    html += `
                        <div class="flex items-center justify-between p-3 bg-box-background rounded-lg">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-info-tertiary flex items-center justify-center mr-3">
                                    <i class="las la-user text-white"></i>
                                </div>
                                <div>
                                    <p class="font-medium">${log.recipient_name || log.recipient_phone}</p>
                                    <p class="text-sm text-secondary truncate max-w-xs">${log.content ? log.content.substring(0, 50) + '...' : '-'}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="${statusClass} text-sm font-medium">${log.status}</span>
                                <p class="text-xs text-secondary">${log.sent_at || log.created_at}</p>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            },
            error: function(error) {
                console.error('Failed to load logs', error);
                document.getElementById('recent-messages').innerHTML = '<div class="text-center text-error-tertiary py-8"><i class="las la-exclamation-circle text-4xl"></i><p class="mt-2">{{ __("Failed to load messages") }}</p></div>';
            }
        });
});
</script>
@endsection
