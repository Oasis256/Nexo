@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header', [
        'title' => __('Message Templates'),
        'description' => __('Manage templates for automated WhatsApp notifications.')
    ])
    
    <div class="px-4 flex-auto flex flex-col">
        <ns-crud
            src="{{ ns()->url('/api/crud/whatsapp.templates') }}"
            create-url="{{ ns()->route('whatsapp.templates.create') }}"
            identifier="whatsapp.templates">
        </ns-crud>
    </div>
</div>
@endsection
