@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <div class="ns-box border border-box-edge p-4 mb-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="text-xs text-secondary uppercase">{{ __m('Biweekly Window', 'RenCommissions') }}</label>
                <select name="biweekly" class="ns-select">
                    <option value="first_half" @selected($biweekly === 'first_half')>{{ __m('1st - 14th', 'RenCommissions') }}</option>
                    <option value="second_half" @selected($biweekly === 'second_half')>{{ __m('15th - End', 'RenCommissions') }}</option>
                    <option value="all" @selected($biweekly === 'all')>{{ __m('Full Month', 'RenCommissions') }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-secondary uppercase">{{ __m('Daily Date', 'RenCommissions') }}</label>
                <input type="date" name="daily_date" value="{{ $dailyDate }}" class="ns-input">
            </div>
            <div>
                <label class="text-xs text-secondary uppercase">{{ __m('Scope', 'RenCommissions') }}</label>
                <select name="scope" class="ns-select">
                    <option value="global" @selected($scope === 'global')>{{ __m('Global', 'RenCommissions') }}</option>
                    <option value="earner" @selected($scope === 'earner')>{{ __m('Per Earner', 'RenCommissions') }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-secondary uppercase">{{ __m('Earner (Optional)', 'RenCommissions') }}</label>
                <select name="earner_id" class="ns-select">
                    <option value="0">{{ __m('All Earners', 'RenCommissions') }}</option>
                    @foreach($earners as $earner)
                        <option value="{{ $earner->id }}" @selected((int) $earnerId === (int) $earner->id)>{{ $earner->username }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button class="ns-button info w-full">{{ __m('Apply Filters', 'RenCommissions') }}</button>
            </div>
        </form>
    </div>

    <div class="ns-box border border-box-edge">
        <ns-crud src="{{ $src }}" :query-params='@json($queryParams)' create-url="">
            <template v-slot:bulk-label>{{ __( 'Bulk Actions' ) }}</template>
        </ns-crud>
    </div>
</div>
@endsection
