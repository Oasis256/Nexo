@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header', [
        'title' => __('Message Logs'),
        'description' => __('View all sent WhatsApp messages and their delivery status.')
    ])
    
    <div class="px-4 flex-auto flex flex-col">
        <ns-crud
            src="{{ ns()->url('/api/crud/whatsapp.logs') }}"
            identifier="whatsapp.logs">
        </ns-crud>
    </div>
</div>
@endsection
