@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    <div id="dashboard-content" class="px-4 flex-auto flex flex-col overflow-y-auto py-4">
        <ns-rencommissions-payment-history-component></ns-rencommissions-payment-history-component>
    </div>
</div>
@endsection

@section('layout.dashboard.footer.inject')
    @parent
    @include('RenCommissions::dashboard.partials.routes')
    @moduleViteAssets('Resources/ts/dashboard.ts', 'RenCommissions')
@endsection

