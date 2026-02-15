@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    <div id="dashboard-content" class="px-4 flex-auto flex flex-col overflow-y-auto">
        <div id="rencommissions-dashboard-root" class="py-4">
            <ns-rencommissions-dashboard></ns-rencommissions-dashboard>
        </div>
    </div>
</div>
@endsection

@section('layout.dashboard.footer.inject')
    @parent
    @include('RenCommissions::dashboard.partials.routes')
    @moduleViteAssets('Resources/ts/dashboard-entry.ts', 'RenCommissions')
@endsection

