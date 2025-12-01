@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header', [
        'title' => __('Send WhatsApp Message'),
        'description' => __('Send a WhatsApp message to a customer or staff member.')
    ])
    
    <div class="px-4 flex-auto flex flex-col pb-4">
        <div class="flex-1 flex gap-4 max-w-6xl mx-auto w-full">
            <!-- Left Panel: Settings -->
            <div class="w-80 flex-shrink-0">
                <div class="ns-box rounded-lg shadow h-full flex flex-col">
                    <div class="p-4 border-b border-box-edge bg-success-tertiary rounded-t-lg">
                        <h3 class="font-semibold text-white flex items-center">
                            <i class="lab la-whatsapp text-2xl mr-2"></i>
                            {{ __m('New Message', 'WhatsApp') }}
                        </h3>
                    </div>
                    
                    <div class="p-4 flex-1 overflow-y-auto">
                        <form id="send-message-form" class="space-y-4">
                            <!-- Recipient Type -->
                            <div>
                                <label class="block text-sm font-medium mb-2">{{ __('Send to') }}</label>
                                <div class="space-y-2">
                                    <label class="flex items-center p-2 rounded-lg cursor-pointer hover:bg-box-background transition-colors">
                                        <input type="radio" name="recipient_type" value="customer" class="mr-3" checked>
                                        <i class="las la-user-circle text-xl mr-2 text-info-tertiary"></i>
                                        <span>{{ __('Customer') }}</span>
                                    </label>
                                    <label class="flex items-center p-2 rounded-lg cursor-pointer hover:bg-box-background transition-colors">
                                        <input type="radio" name="recipient_type" value="user" class="mr-3">
                                        <i class="las la-user-tie text-xl mr-2 text-warning-tertiary"></i>
                                        <span>{{ __('Staff') }}</span>
                                    </label>
                                    <label class="flex items-center p-2 rounded-lg cursor-pointer hover:bg-box-background transition-colors">
                                        <input type="radio" name="recipient_type" value="custom" class="mr-3">
                                        <i class="las la-phone text-xl mr-2 text-success-tertiary"></i>
                                        <span>{{ __('Custom Number') }}</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Customer Select -->
                            <div id="customer-select-wrapper">
                                <label class="block text-sm font-medium mb-2">{{ __('Select Customer') }}</label>
                                <ns-search-select 
                                    :src="'{{ ns()->url('/api/customers/search?with_phone=1') }}'"
                                    name="customer_id"
                                    :placeholder="'{{ __('Search customer...') }}'"
                                    @change="onCustomerSelect">
                                </ns-search-select>
                            </div>
                            
                            <!-- User Select -->
                            <div class="hidden" id="user-select-wrapper">
                                <label class="block text-sm font-medium mb-2">{{ __('Select Staff') }}</label>
                                <ns-search-select 
                                    :src="'{{ ns()->url('/api/users/search') }}'"
                                    name="user_id"
                                    :placeholder="'{{ __('Search staff...') }}'"
                                    @change="onUserSelect">
                                </ns-search-select>
                            </div>
                            
                            <!-- Custom Phone -->
                            <div class="hidden" id="custom-phone-wrapper">
                                <label class="block text-sm font-medium mb-2">{{ __('Phone Number') }}</label>
                                <input type="text" 
                                       name="phone" 
                                       id="custom-phone"
                                       class="w-full ns-input" 
                                       placeholder="+1234567890">
                            </div>
                            
                            <!-- Template Select -->
                            <div>
                                <label class="block text-sm font-medium mb-2">{{ __('Template') }}</label>
                                <select name="template_id" id="template-select" class="w-full ns-input text-sm">
                                    <option value="">{{ __('Custom message') }}</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel: Chat Interface -->
            <div class="flex-1 flex flex-col">
                <div class="ns-box rounded-lg shadow flex-1 flex flex-col overflow-hidden">
                    <!-- Chat Header -->
                    <div class="p-4 border-b border-box-edge flex items-center" style="background: linear-gradient(135deg, #075E54 0%, #128C7E 100%);">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mr-3">
                            <i class="las la-user text-white text-xl" id="recipient-icon"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-white" id="recipient-name">{{ __m('Select a recipient', 'WhatsApp') }}</h4>
                            <p class="text-white/70 text-sm" id="recipient-phone">-</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center text-white">
                                <i class="las la-video"></i>
                            </button>
                            <button class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center text-white">
                                <i class="las la-phone"></i>
                            </button>
                            <button class="w-8 h-8 rounded-full hover:bg-white/10 flex items-center justify-center text-white">
                                <i class="las la-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Chat Background -->
                    <div class="flex-1 p-4 overflow-y-auto" id="chat-area" style="background-color: #ECE5DD; background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23c8c3bb&quot; fill-opacity=&quot;0.15&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
                        
                        <!-- Welcome Message -->
                        <div class="flex justify-center mb-4">
                            <div class="bg-white/80 rounded-lg px-4 py-2 text-center shadow-sm">
                                <i class="las la-lock text-gray-500 text-xs"></i>
                                <p class="text-xs text-gray-500">{{ __m('Messages are sent via WhatsApp Business API', 'WhatsApp') }}</p>
                            </div>
                        </div>
                        
                        <!-- Preview Messages Container -->
                        <div id="preview-messages" class="space-y-2">
                            <!-- Messages will be added here -->
                        </div>
                    </div>
                    
                    <!-- Message Input Area -->
                    <div class="p-3 border-t border-box-edge bg-gray-100">
                        <div class="flex items-end gap-2">
                            <button type="button" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 hover:text-gray-700 shadow-sm">
                                <i class="las la-smile text-xl"></i>
                            </button>
                            <button type="button" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 hover:text-gray-700 shadow-sm">
                                <i class="las la-paperclip text-xl"></i>
                            </button>
                            <div class="flex-1 relative">
                                <textarea 
                                    name="message" 
                                    id="message-content"
                                    rows="1"
                                    class="w-full rounded-2xl border-0 bg-white px-4 py-3 pr-12 text-sm resize-none shadow-sm focus:ring-2 focus:ring-success-tertiary"
                                    placeholder="{{ __m('Type a message', 'WhatsApp') }}"
                                    style="max-height: 120px;"
                                    required></textarea>
                                <span class="absolute right-3 bottom-3 text-xs text-gray-400" id="char-count">0/4096</span>
                            </div>
                            <button type="button" id="send-btn" class="w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg transition-all" style="background: #00a884;">
                                <i class="las la-paper-plane text-xl" id="send-icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #message-content {
        overflow-y: auto;
    }
    #message-content:focus {
        outline: none;
    }
    .whatsapp-bubble {
        max-width: 65%;
        animation: bubbleIn 0.2s ease-out;
    }
    .whatsapp-bubble.outgoing {
        margin-left: auto;
        background: #DCF8C6;
        border-radius: 8px 8px 0 8px;
    }
    .whatsapp-bubble.incoming {
        background: white;
        border-radius: 8px 8px 8px 0;
    }
    @keyframes bubbleIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(10px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    .whatsapp-tail-out {
        position: relative;
    }
    .whatsapp-tail-out::after {
        content: '';
        position: absolute;
        right: -8px;
        bottom: 0;
        width: 0;
        height: 0;
        border: 8px solid transparent;
        border-left-color: #DCF8C6;
        border-bottom: 0;
        border-right: 0;
    }
    /* Dark mode adjustments */
    .ns-box {
        background: var(--box-background);
    }
</style>
@endsection

@section('layout.dashboard.footer.inject')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('send-message-form');
    const recipientRadios = document.querySelectorAll('input[name="recipient_type"]');
    const customerWrapper = document.getElementById('customer-select-wrapper');
    const userWrapper = document.getElementById('user-select-wrapper');
    const customPhoneWrapper = document.getElementById('custom-phone-wrapper');
    const templateSelect = document.getElementById('template-select');
    const messageContent = document.getElementById('message-content');
    const charCount = document.getElementById('char-count');
    const chatArea = document.getElementById('chat-area');
    const previewMessages = document.getElementById('preview-messages');
    const recipientName = document.getElementById('recipient-name');
    const recipientPhone = document.getElementById('recipient-phone');
    const recipientIcon = document.getElementById('recipient-icon');
    const sendBtn = document.getElementById('send-btn');
    const sendIcon = document.getElementById('send-icon');
    
    let selectedCustomer = null;
    let selectedUser = null;
    let templates = [];
    
    // Load templates
    nsHttpClient.get('/api/whatsapp/templates?active_only=1')
        .subscribe({
            next: function(response) {
                templates = (response.data && response.data.data) ? response.data.data : (Array.isArray(response.data) ? response.data : []);
                templates.forEach(function(template) {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.label;
                    option.dataset.content = template.content || '';
                    templateSelect.appendChild(option);
                });
            },
            error: function(error) {
                console.error('Failed to load templates', error);
            }
        });
    
    // Handle recipient type change
    recipientRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            customerWrapper.classList.add('hidden');
            userWrapper.classList.add('hidden');
            customPhoneWrapper.classList.add('hidden');
            
            // Reset recipient display
            recipientName.textContent = '{{ __m("Select a recipient", "WhatsApp") }}';
            recipientPhone.textContent = '-';
            
            switch(this.value) {
                case 'customer':
                    customerWrapper.classList.remove('hidden');
                    recipientIcon.className = 'las la-user-circle text-white text-xl';
                    break;
                case 'user':
                    userWrapper.classList.remove('hidden');
                    recipientIcon.className = 'las la-user-tie text-white text-xl';
                    break;
                case 'custom':
                    customPhoneWrapper.classList.remove('hidden');
                    recipientIcon.className = 'las la-phone text-white text-xl';
                    recipientName.textContent = '{{ __m("Custom Number", "WhatsApp") }}';
                    break;
            }
        });
    });
    
    // Handle custom phone input
    document.getElementById('custom-phone').addEventListener('input', function() {
        if (this.value) {
            recipientPhone.textContent = this.value;
        } else {
            recipientPhone.textContent = '-';
        }
    });
    
    // Handle template change
    templateSelect.addEventListener('change', function() {
        if (this.value) {
            const selected = this.options[this.selectedIndex];
            messageContent.value = selected.dataset.content || '';
            updateCharCount();
            autoResize();
            updatePreview();
        }
    });
    
    // Character counter and auto-resize
    messageContent.addEventListener('input', function() {
        updateCharCount();
        autoResize();
        updatePreview();
    });
    
    function updateCharCount() {
        const length = messageContent.value.length;
        charCount.textContent = length + '/4096';
        if (length > 4096) {
            charCount.classList.add('text-error-tertiary');
        } else {
            charCount.classList.remove('text-error-tertiary');
        }
    }
    
    function autoResize() {
        messageContent.style.height = 'auto';
        messageContent.style.height = Math.min(messageContent.scrollHeight, 120) + 'px';
    }
    
    function updatePreview() {
        const message = messageContent.value.trim();
        previewMessages.innerHTML = '';
        
        if (message) {
            const now = new Date();
            const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            
            const bubble = document.createElement('div');
            bubble.className = 'whatsapp-bubble outgoing whatsapp-tail-out p-2 shadow-sm';
            bubble.innerHTML = `
                <p class="text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(message)}</p>
                <div class="flex items-center justify-end gap-1 mt-1">
                    <span class="text-xs text-gray-500">${time}</span>
                    <i class="las la-check-double text-xs text-blue-500"></i>
                </div>
            `;
            previewMessages.appendChild(bubble);
            
            // Scroll to bottom
            chatArea.scrollTop = chatArea.scrollHeight;
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Send button click
    sendBtn.addEventListener('click', function() {
        sendMessage();
    });
    
    // Enter to send (Shift+Enter for new line)
    messageContent.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    function sendMessage() {
        const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
        const message = messageContent.value.trim();
        
        if (!message) {
            nsSnackBar.error('{{ __m("Please enter a message", "WhatsApp") }}');
            return;
        }
        
        let payload = {
            message: message
        };
        
        if (recipientType === 'customer' && selectedCustomer) {
            payload.phone = selectedCustomer.phone;
            payload.recipient_name = selectedCustomer.name;
        } else if (recipientType === 'user' && selectedUser) {
            payload.phone = selectedUser.phone;
            payload.recipient_name = selectedUser.username;
        } else if (recipientType === 'custom') {
            payload.phone = document.getElementById('custom-phone').value;
            if (!payload.phone) {
                nsSnackBar.error('{{ __m("Please enter a phone number", "WhatsApp") }}');
                return;
            }
        } else {
            nsSnackBar.error('{{ __m("Please select a recipient", "WhatsApp") }}');
            return;
        }
        
        // Update button to loading state
        sendBtn.disabled = true;
        sendIcon.className = 'las la-spinner la-spin text-xl';
        
        nsHttpClient.post('/api/whatsapp/send', payload)
            .subscribe({
                next: function(response) {
                    nsSnackBar.success('{{ __m("Message sent successfully!", "WhatsApp") }}');
                    
                    // Add sent indicator to the preview
                    const lastBubble = previewMessages.querySelector('.whatsapp-bubble:last-child');
                    if (lastBubble) {
                        const checkIcon = lastBubble.querySelector('.la-check-double');
                        if (checkIcon) {
                            checkIcon.className = 'las la-check-double text-xs text-green-500';
                        }
                    }
                    
                    // Clear input
                    messageContent.value = '';
                    updateCharCount();
                    autoResize();
                    
                    // Reset button
                    sendBtn.disabled = false;
                    sendIcon.className = 'las la-paper-plane text-xl';
                },
                error: function(error) {
                    nsSnackBar.error(error.message || '{{ __m("Failed to send message", "WhatsApp") }}');
                    
                    // Mark as failed
                    const lastBubble = previewMessages.querySelector('.whatsapp-bubble:last-child');
                    if (lastBubble) {
                        lastBubble.classList.add('border', 'border-red-300');
                        const checkIcon = lastBubble.querySelector('.la-check-double');
                        if (checkIcon) {
                            checkIcon.className = 'las la-exclamation-circle text-xs text-red-500';
                        }
                    }
                    
                    // Reset button
                    sendBtn.disabled = false;
                    sendIcon.className = 'las la-paper-plane text-xl';
                }
            });
    }
    
    // Customer select handler
    window.onCustomerSelect = function(customer) {
        selectedCustomer = customer;
        if (customer) {
            recipientName.textContent = customer.name || customer.first_name || '{{ __m("Customer", "WhatsApp") }}';
            recipientPhone.textContent = customer.phone || '-';
        }
    };
    
    // User select handler
    window.onUserSelect = function(user) {
        selectedUser = user;
        if (user) {
            recipientName.textContent = user.username || user.name || '{{ __m("Staff", "WhatsApp") }}';
            recipientPhone.textContent = user.phone || '-';
        }
    };
});
</script>
@endsection
