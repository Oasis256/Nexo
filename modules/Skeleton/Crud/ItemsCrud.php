<?php

namespace Modules\Skeleton\Crud;

use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Modules\Skeleton\Models\SkeletonItem;

class ItemsCrud extends CrudService
{
    const AUTOLOAD = true;
    const IDENTIFIER = 'skeleton.items';

    protected $table = 'skeleton_items';
    protected $model = SkeletonItem::class;
    protected $namespace = 'skeleton.items';
    protected $slug = 'skeleton/items';

    protected $permissions = [
        'create' => 'skeleton.create.items',
        'read' => 'skeleton.read.items',
        'update' => 'skeleton.update.items',
        'delete' => 'skeleton.delete.items',
    ];

    public $fillable = ['name', 'description', 'status', 'category', 'price', 'quantity'];

    /**
     * Define labels for the CRUD interface
     */
    public function getLabels(): array
    {
        return [
            'list_title' => __('Skeleton Items'),
            'list_description' => __('Display all skeleton items.'),
            'no_entry' => __('No items have been registered'),
            'create_new' => __('Add a new item'),
            'create_title' => __('Create a new item'),
            'create_description' => __('Register a new item and save it.'),
            'edit_title' => __('Edit item'),
            'edit_description' => __('Modify an existing item.'),
            'back_to_list' => __('Return to Items'),
        ];
    }

    /**
     * Define navigation links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/skeleton/items'),
            'create' => ns()->url('dashboard/skeleton/items/create'),
            'edit' => ns()->url('dashboard/skeleton/items/edit/'),
        ];
    }

    /**
     * Define table columns
     */
    public function getTable(): array
    {
        return [
            'id' => [
                'label' => __('ID'),
                '$direction' => '',
                '$sort' => false,
                'width' => '80px',
            ],
            'name' => [
                'label' => __('Name'),
                '$direction' => '',
                '$sort' => true,
            ],
            'category' => [
                'label' => __('Category'),
                '$direction' => '',
                '$sort' => false,
                'width' => '150px',
            ],
            'status' => [
                'label' => __('Status'),
                '$direction' => '',
                '$sort' => false,
                'width' => '120px',
            ],
            'price' => [
                'label' => __('Price'),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'quantity' => [
                'label' => __('Quantity'),
                '$direction' => '',
                '$sort' => false,
                'width' => '100px',
            ],
            'created_at' => [
                'label' => __('Created'),
                '$direction' => '',
                '$sort' => true,
                'width' => '150px',
            ],
        ];
    }

    /**
     * Define row actions
     */
    protected function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->action(
            label: __('Edit'),
            identifier: 'edit',
            type: 'GOTO',
            url: ns()->url('/dashboard/skeleton/items/edit/' . $entry->id),
            permissions: 'skeleton.update.items'
        );

        $entry->action(
            label: __('Delete'),
            identifier: 'delete',
            type: 'DELETE',
            url: ns()->url('/api/crud/skeleton.items/' . $entry->id),
            permissions: 'skeleton.delete.items',
            confirm: [
                'message' => __('Are you sure you want to delete this item?'),
            ]
        );

        return $entry;
    }

    /**
     * Define form configuration
     */
    public function getForm($entry = null): array
    {
        return CrudForm::form(
            main: FormInput::text(
                label: __('Item Name'),
                name: 'name',
                value: $entry->name ?? '',
                validation: 'required|string|max:255',
                description: __('Provide a unique name for the item.')
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __('General'),
                    fields: [
                        FormInput::textarea(
                            label: __('Description'),
                            name: 'description',
                            value: $entry->description ?? '',
                            description: __('Provide a description for the item.')
                        ),
                        FormInput::select(
                            label: __('Category'),
                            name: 'category',
                            value: $entry->category ?? 'general',
                            options: [
                                ['label' => __('General'), 'value' => 'general'],
                                ['label' => __('Electronics'), 'value' => 'electronics'],
                                ['label' => __('Furniture'), 'value' => 'furniture'],
                                ['label' => __('Office Supplies'), 'value' => 'office'],
                            ],
                            validation: 'required',
                            description: __('Select the item category.')
                        ),
                        FormInput::select(
                            label: __('Status'),
                            name: 'status',
                            value: $entry->status ?? SkeletonItem::STATUS_ACTIVE,
                            options: [
                                ['label' => __('Active'), 'value' => SkeletonItem::STATUS_ACTIVE],
                                ['label' => __('Inactive'), 'value' => SkeletonItem::STATUS_INACTIVE],
                                ['label' => __('Pending'), 'value' => SkeletonItem::STATUS_PENDING],
                            ],
                            validation: 'required',
                            description: __('Select the item status.')
                        ),
                    ]
                ),
                CrudForm::tab(
                    identifier: 'pricing',
                    label: __('Pricing & Inventory'),
                    fields: [
                        FormInput::number(
                            label: __('Price'),
                            name: 'price',
                            value: $entry->price ?? 0,
                            validation: 'required|numeric|min:0',
                            description: __('Enter the item price.')
                        ),
                        FormInput::number(
                            label: __('Quantity'),
                            name: 'quantity',
                            value: $entry->quantity ?? 0,
                            validation: 'required|integer|min:0',
                            description: __('Enter the available quantity.')
                        ),
                    ]
                )
            )
        );
    }

    /**
     * Get bulk actions
     */
    public function getBulkActions(): array
    {
        return [
            [
                'label' => __('Delete Selected'),
                'identifier' => 'delete_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => 'skeleton.items']),
                'permissions' => 'skeleton.delete.items',
            ],
            [
                'label' => __('Activate Selected'),
                'identifier' => 'activate_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => 'skeleton.items']),
                'permissions' => 'skeleton.update.items',
            ],
        ];
    }
}
