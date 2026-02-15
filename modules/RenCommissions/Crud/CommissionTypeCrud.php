<?php

namespace Modules\RenCommissions\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use Modules\RenCommissions\Models\CommissionType;
use Modules\RenCommissions\Support\StoreContext;

class CommissionTypeCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'rencommissions.types';

    protected $table = 'rencommissions_types';

    protected $model = CommissionType::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'rencommissions/types';

    protected $permissions = [
        'create' => 'nexopos.rencommissions.create.types',
        'read' => 'nexopos.rencommissions.read.types',
        'update' => 'nexopos.rencommissions.update.types',
        'delete' => 'nexopos.rencommissions.delete.types',
    ];

    public $fillable = [
        'store_id',
        'name',
        'description',
        'calculation_method',
        'default_value',
        'min_value',
        'max_value',
        'is_active',
        'priority',
        'author',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function hook($query): void
    {
        $storeId = StoreContext::id();
        if ($storeId !== null) {
            $query->where('rencommissions_types.store_id', $storeId);
        }
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Commission Types', 'RenCommissions'),
            list_description: __m('Manage available commission methods and defaults.', 'RenCommissions'),
            no_entry: __m('No commission types found.', 'RenCommissions'),
            create_new: __m('Create Commission Type', 'RenCommissions'),
            create_title: __m('Create Commission Type', 'RenCommissions'),
            create_description: __m('Create a new commission calculation type.', 'RenCommissions'),
            edit_title: __m('Edit Commission Type', 'RenCommissions'),
            edit_description: __m('Update commission type details.', 'RenCommissions'),
            back_to_list: __m('Return to Commission Types', 'RenCommissions')
        );
    }

    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(label: __m('Name', 'RenCommissions'), identifier: 'name', sort: true, width: '180px'),
            CrudTable::column(label: __m('Method', 'RenCommissions'), identifier: 'calculation_method', sort: true, width: '140px'),
            CrudTable::column(label: __m('Default', 'RenCommissions'), identifier: 'default_value', sort: true, width: '120px'),
            CrudTable::column(label: __m('Min', 'RenCommissions'), identifier: 'min_value', sort: true, width: '100px'),
            CrudTable::column(label: __m('Max', 'RenCommissions'), identifier: 'max_value', sort: true, width: '100px'),
            CrudTable::column(label: __m('Priority', 'RenCommissions'), identifier: 'priority', sort: true, width: '100px'),
            CrudTable::column(label: __m('Status', 'RenCommissions'), identifier: 'is_active', sort: true, width: '100px')
        );
    }

    public function getForm($entry = null): array
    {
        return [
            'main' => [
                'label' => __m('Name', 'RenCommissions'),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'validation' => 'required|string|max:100',
                'description' => __m('Unique label for this commission type.', 'RenCommissions'),
            ],
            'tabs' => [
                'general' => [
                    'label' => __m('General', 'RenCommissions'),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'description',
                            'label' => __m('Description', 'RenCommissions'),
                            'description' => __m('Optional description for internal reference.', 'RenCommissions'),
                            'value' => $entry->description ?? '',
                        ],
                        [
                            'type' => 'select',
                            'name' => 'calculation_method',
                            'label' => __m('Method', 'RenCommissions'),
                            'validation' => 'required|in:fixed,percentage,on_the_house',
                            'options' => [
                                ['label' => __m('Fixed', 'RenCommissions'), 'value' => 'fixed'],
                                ['label' => __m('Percentage', 'RenCommissions'), 'value' => 'percentage'],
                                ['label' => __m('On The House', 'RenCommissions'), 'value' => 'on_the_house'],
                            ],
                            'value' => $entry->calculation_method ?? 'fixed',
                        ],
                        [
                            'type' => 'number',
                            'name' => 'default_value',
                            'label' => __m('Default Value', 'RenCommissions'),
                            'validation' => 'required|numeric|min:0',
                            'value' => (string) ($entry->default_value ?? 0),
                        ],
                        [
                            'type' => 'number',
                            'name' => 'min_value',
                            'label' => __m('Min Value', 'RenCommissions'),
                            'validation' => 'nullable|numeric|min:0',
                            'value' => (string) ($entry->min_value ?? ''),
                        ],
                        [
                            'type' => 'number',
                            'name' => 'max_value',
                            'label' => __m('Max Value', 'RenCommissions'),
                            'validation' => 'nullable|numeric|min:0',
                            'value' => (string) ($entry->max_value ?? ''),
                        ],
                        [
                            'type' => 'number',
                            'name' => 'priority',
                            'label' => __m('Priority', 'RenCommissions'),
                            'validation' => 'nullable|integer|min:0',
                            'value' => (string) ($entry->priority ?? 0),
                        ],
                        [
                            'type' => 'switch',
                            'name' => 'is_active',
                            'label' => __m('Status', 'RenCommissions'),
                            'options' => [
                                ['label' => __m('Active', 'RenCommissions'), 'value' => '1'],
                                ['label' => __m('Disabled', 'RenCommissions'), 'value' => '0'],
                            ],
                            'value' => (string) ((int) ($entry->is_active ?? 1)),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function beforePost($request)
    {
        $request['store_id'] = StoreContext::id();
        $request['author'] = auth()->id();

        return $request;
    }

    public function filterPostInputs($inputs)
    {
        $inputs['store_id'] = StoreContext::id();
        $inputs['author'] = auth()->id();
        $inputs['is_active'] = (int) ($inputs['is_active'] ?? 1);

        return $inputs;
    }

    public function filterPutInputs($inputs, CommissionType $entry)
    {
        $inputs['store_id'] = (int) $entry->store_id;
        $inputs['is_active'] = (int) ($inputs['is_active'] ?? $entry->is_active);

        return $inputs;
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->calculation_method = ucfirst(str_replace('_', ' ', (string) $entry->calculation_method));
        $entry->default_value = number_format((float) $entry->default_value, 2);
        $entry->min_value = $entry->min_value !== null ? number_format((float) $entry->min_value, 2) : '-';
        $entry->max_value = $entry->max_value !== null ? number_format((float) $entry->max_value, 2) : '-';
        $entry->is_active = (int) $entry->is_active === 1
            ? __m('Active', 'RenCommissions')
            : __m('Disabled', 'RenCommissions');

        $entry->action(
            identifier: 'edit',
            label: __m('Edit', 'RenCommissions'),
            type: 'GOTO',
            url: ns()->url('/dashboard/rencommissions/types/edit/' . $entry->id)
        );

        $entry->action(
            identifier: 'delete',
            label: __m('Delete', 'RenCommissions'),
            type: 'DELETE',
            url: ns()->url('/api/crud/' . self::IDENTIFIER . '/' . $entry->id),
            confirm: [
                'message' => __m('Would you like to delete this commission type?', 'RenCommissions'),
            ]
        );

        return $entry;
    }

    public function bulkAction(Request $request)
    {
        if ($request->input('action') === 'delete_selected') {
            $ids = collect($request->input('entries', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($ids->isNotEmpty()) {
                CommissionType::query()
                    ->where('store_id', StoreContext::id())
                    ->whereIn('id', $ids->all())
                    ->delete();
            }

            return [
                'status' => 'success',
                'message' => __m('Selected commission types deleted.', 'RenCommissions'),
            ];
        }

        return false;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url('dashboard/rencommissions/types'),
            create: ns()->url('dashboard/rencommissions/types/create'),
            edit: ns()->url('dashboard/rencommissions/types/edit/{id}'),
            post: ns()->url('api/crud/' . self::IDENTIFIER),
            put: ns()->url('api/crud/' . self::IDENTIFIER . '/{id}')
        );
    }
}
