@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    
    <div class="px-4 flex-auto flex flex-col">
        <ns-crud-form
            src="{{ ns()->url('/api/crud/skeleton.items/form-config' . (isset($entry) ? '/' . $entry->id : '')) }}"
            submit-url="{{ ns()->url('/api/crud/skeleton.items' . (isset($entry) ? '/' . $entry->id : '')) }}"
            submit-method="{{ isset($entry) ? 'PUT' : 'POST' }}"
            return-url="{{ route('skeleton.items.list') }}">
        </ns-crud-form>
    </div>
</div>
@endsection
