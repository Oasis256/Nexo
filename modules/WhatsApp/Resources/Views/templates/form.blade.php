@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header', [
        'title' => isset($entry) ? __('Edit Template') : __('Create Template'),
        'description' => isset($entry) ? __('Modify an existing message template.') : __('Create a new message template for notifications.')
    ])
    
    <div class="px-4 flex-auto flex flex-col">
        <ns-crud-form
            src="{{ isset($entry) ? ns()->url('/api/crud/whatsapp.templates/form-config/' . $entry->id) : ns()->url('/api/crud/whatsapp.templates/form-config') }}"
            submit-url="{{ isset($entry) ? ns()->url('/api/crud/whatsapp.templates/' . $entry->id) : ns()->url('/api/crud/whatsapp.templates') }}"
            submit-method="{{ isset($entry) ? 'PUT' : 'POST' }}"
            return-url="{{ ns()->route('whatsapp.templates') }}">
        </ns-crud-form>
    </div>
</div>
@endsection
