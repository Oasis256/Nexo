@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    
    <div class="px-4 flex-auto flex flex-col">
        <ns-crud
            src="{{ ns()->url('/api/crud/skeleton.items') }}"
            create-url="{{ route('skeleton.items.create') }}"
            namespace="skeleton.items">
        </ns-crud>
    </div>
</div>
@endsection
