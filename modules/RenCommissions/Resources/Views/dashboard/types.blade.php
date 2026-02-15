@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
        <div class="xl:col-span-4 ns-box border p-4">
            <h3 class="font-semibold mb-3">{{ __m('Create Commission Type', 'RenCommissions') }}</h3>
            <form method="POST" action="{{ ns()->route('rencommissions.types.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-xs text-secondary">{{ __m('Name', 'RenCommissions') }}</label>
                    <input name="name" required class="w-full ns-input">
                </div>
                <div>
                    <label class="text-xs text-secondary">{{ __m('Description', 'RenCommissions') }}</label>
                    <input name="description" class="w-full ns-input">
                </div>
                <div>
                    <label class="text-xs text-secondary">{{ __m('Method', 'RenCommissions') }}</label>
                    <select name="calculation_method" class="w-full ns-select">
                        @foreach($methods as $method)
                            <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" step="0.01" min="0" name="default_value" required class="ns-input" placeholder="{{ __m('Default', 'RenCommissions') }}">
                    <input type="number" step="0.01" min="0" name="min_value" class="ns-input" placeholder="{{ __m('Min', 'RenCommissions') }}">
                    <input type="number" step="0.01" min="0" name="max_value" class="ns-input" placeholder="{{ __m('Max', 'RenCommissions') }}">
                </div>
                <div>
                    <label class="text-xs text-secondary">{{ __m('Priority', 'RenCommissions') }}</label>
                    <input type="number" min="0" name="priority" value="0" class="w-full ns-input">
                </div>
                <button class="ns-button info">{{ __m('Save Type', 'RenCommissions') }}</button>
            </form>
        </div>

        <div class="xl:col-span-8 ns-box border overflow-auto">
            <table class="table ns-table w-full text-sm">
                <thead>
                    <tr class="info text-left">
                        <th class="p-3">{{ __m('Name', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Method', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Default', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Min/Max', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Priority', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Status', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Action', 'RenCommissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-t">
                            <td class="p-3 font-semibold">{{ $row->name }}</td>
                            <td class="p-3 capitalize">{{ str_replace('_', ' ', $row->calculation_method) }}</td>
                            <td class="p-3">{{ $row->default_value }}</td>
                            <td class="p-3">{{ $row->min_value ?? '-' }} / {{ $row->max_value ?? '-' }}</td>
                            <td class="p-3">{{ $row->priority }}</td>
                            <td class="p-3">{{ $row->is_active ? __m('Active', 'RenCommissions') : __m('Disabled', 'RenCommissions') }}</td>
                            <td class="p-3">
                                <form method="POST" action="{{ ns()->route('rencommissions.types.toggle', ['type' => $row->id]) }}">
                                    @csrf
                                    <button class="text-info-secondary">{{ $row->is_active ? __m('Disable', 'RenCommissions') : __m('Enable', 'RenCommissions') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-secondary" colspan="7">{{ __m('No commission types found.', 'RenCommissions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t">{{ $rows->links() }}</div>
        </div>
    </div>
</div>
</div>
@endsection
