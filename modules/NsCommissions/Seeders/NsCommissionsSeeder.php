<?php

namespace Modules\NsCommissions\Seeders;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\RewardSystem;
use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\NsCommissions\Models\Commission;
use Modules\NsCommissions\Models\CommissionProductCategory;
use Modules\NsCommissions\Models\CommissionProductValue;

class NsCommissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test data for NsCommissions module including:
     * - Walk In Customer (default customer, can't earn rewards)
     * - Sample product categories and products
     * - Fixed commission as default type
     * - Sample commissions of all types
     */
    public function run(): void
    {
        $this->command->info('Seeding NsCommissions test data...');

        // Create admin user if not exists
        $admin = $this->createAdminUser();
        
        // Create unit group and units first
        $unitGroup = $this->createUnitGroup($admin);
        
        // Create product categories
        $categories = $this->createProductCategories($admin);
        
        // Create products with units
        $products = $this->createProducts($admin, $categories, $unitGroup);
        
        // Create customer group (no rewards)
        $customerGroup = $this->createCustomerGroup($admin);
        
        // Create Walk In Customer
        $walkInCustomer = $this->createWalkInCustomer($admin, $customerGroup);
        
        // Set Walk In Customer as default
        $this->setDefaultCustomer($walkInCustomer);
        
        // Create sample cashier users for commission earning
        $cashiers = $this->createCashierUsers($admin);
        
        // Create commissions (Fixed as default)
        $this->createCommissions($admin, $categories, $products, $cashiers);
        
        $this->command->info('NsCommissions test data seeded successfully!');
    }

    /**
     * Create admin user if not exists
     */
    private function createAdminUser(): User
    {
        $admin = User::where('username', 'admin')->first();
        
        if (!$admin) {
            $admin = User::create([
                'username' => 'admin',
                'email' => 'admin@nexopos.com',
                'password' => Hash::make('123456'),
                'active' => true,
            ]);
            $admin->assignRole(Role::ADMIN);
            $this->command->info('Created admin user');
        }
        
        return $admin;
    }

    /**
     * Create unit group with units
     */
    private function createUnitGroup(User $admin): UnitGroup
    {
        $unitGroup = UnitGroup::firstOrCreate(
            ['name' => 'Piece Unit Group'],
            [
                'description' => 'Default unit group for counting items',
                'author' => $admin->id,
            ]
        );

        // Create base unit
        Unit::firstOrCreate(
            ['identifier' => 'piece'],
            [
                'name' => 'Piece',
                'description' => 'Single piece unit',
                'group_id' => $unitGroup->id,
                'value' => 1,
                'base_unit' => true,
                'author' => $admin->id,
            ]
        );

        // Create pack unit (6 pieces)
        Unit::firstOrCreate(
            ['identifier' => 'pack'],
            [
                'name' => 'Pack',
                'description' => 'Pack of 6 pieces',
                'group_id' => $unitGroup->id,
                'value' => 6,
                'base_unit' => false,
                'author' => $admin->id,
            ]
        );

        $this->command->info('Created unit group with units');
        return $unitGroup;
    }

    /**
     * Create product categories
     */
    private function createProductCategories(User $admin): array
    {
        $categoriesData = [
            [
                'name' => 'Beverages',
                'description' => 'Drinks and beverages',
            ],
            [
                'name' => 'Snacks',
                'description' => 'Snacks and light food',
            ],
            [
                'name' => 'Electronics',
                'description' => 'Electronic items and accessories',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $category = ProductCategory::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'author' => $admin->id,
                ]
            );
            $categories[] = $category;
        }

        $this->command->info('Created ' . count($categories) . ' product categories');
        return $categories;
    }

    /**
     * Create sample products
     */
    private function createProducts(User $admin, array $categories, UnitGroup $unitGroup): array
    {
        $productsData = [
            // Beverages
            [
                'name' => 'Cola Drink',
                'category_index' => 0,
                'sale_price' => 2.50,
                'wholesale_price' => 1.80,
                'sku' => 'BEV-001',
                'barcode' => '1234567890001',
            ],
            [
                'name' => 'Orange Juice',
                'category_index' => 0,
                'sale_price' => 3.00,
                'wholesale_price' => 2.20,
                'sku' => 'BEV-002',
                'barcode' => '1234567890002',
            ],
            [
                'name' => 'Mineral Water',
                'category_index' => 0,
                'sale_price' => 1.50,
                'wholesale_price' => 0.80,
                'sku' => 'BEV-003',
                'barcode' => '1234567890003',
            ],
            // Snacks
            [
                'name' => 'Potato Chips',
                'category_index' => 1,
                'sale_price' => 2.00,
                'wholesale_price' => 1.20,
                'sku' => 'SNK-001',
                'barcode' => '1234567890004',
            ],
            [
                'name' => 'Chocolate Bar',
                'category_index' => 1,
                'sale_price' => 1.75,
                'wholesale_price' => 1.00,
                'sku' => 'SNK-002',
                'barcode' => '1234567890005',
            ],
            [
                'name' => 'Mixed Nuts',
                'category_index' => 1,
                'sale_price' => 4.50,
                'wholesale_price' => 3.00,
                'sku' => 'SNK-003',
                'barcode' => '1234567890006',
            ],
            // Electronics
            [
                'name' => 'USB Cable',
                'category_index' => 2,
                'sale_price' => 9.99,
                'wholesale_price' => 5.00,
                'sku' => 'ELC-001',
                'barcode' => '1234567890007',
            ],
            [
                'name' => 'Phone Charger',
                'category_index' => 2,
                'sale_price' => 15.00,
                'wholesale_price' => 8.00,
                'sku' => 'ELC-002',
                'barcode' => '1234567890008',
            ],
            [
                'name' => 'Earphones',
                'category_index' => 2,
                'sale_price' => 25.00,
                'wholesale_price' => 12.00,
                'sku' => 'ELC-003',
                'barcode' => '1234567890009',
            ],
        ];

        $unit = Unit::where('identifier', 'piece')->first();
        $products = [];

        foreach ($productsData as $data) {
            $product = Product::firstOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'barcode' => $data['barcode'],
                    'category_id' => $categories[$data['category_index']]->id,
                    'product_type' => 'product',
                    'type' => 'materialized',
                    'status' => 'available',
                    'stock_management' => 'enabled',
                    'barcode_type' => 'ean13',
                    'tax_type' => 'inclusive',
                    'unit_group' => $unitGroup->id,
                    'author' => $admin->id,
                ]
            );

            // Create product unit quantity (price configuration)
            ProductUnitQuantity::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                ],
                [
                    'quantity' => 100, // Initial stock
                    'sale_price' => $data['sale_price'],
                    'sale_price_edit' => $data['sale_price'],
                    'wholesale_price' => $data['wholesale_price'],
                    'wholesale_price_edit' => $data['wholesale_price'],
                    'custom_price' => $data['sale_price'],
                    'custom_price_edit' => $data['sale_price'],
                ]
            );

            $products[] = $product;
        }

        $this->command->info('Created ' . count($products) . ' products');
        return $products;
    }

    /**
     * Create customer group without rewards
     */
    private function createCustomerGroup(User $admin): CustomerGroup
    {
        $group = CustomerGroup::firstOrCreate(
            ['name' => 'Walk In Customers'],
            [
                'description' => 'Default group for walk-in customers. No rewards earned.',
                'minimal_credit_payment' => 0,
                'reward_system_id' => null, // No rewards
                'author' => $admin->id,
            ]
        );

        $this->command->info('Created Walk In Customers group (no rewards)');
        return $group;
    }

    /**
     * Create Walk In Customer
     */
    private function createWalkInCustomer(User $admin, CustomerGroup $group): Customer
    {
        // Check if Walk In Customer already exists
        $existing = User::where('username', 'walk_in_customer')->first();
        
        if ($existing) {
            $this->command->info('Walk In Customer already exists');
            return Customer::find($existing->id);
        }

        // Create the Walk In Customer user
        $user = User::create([
            'username' => 'walk_in_customer',
            'email' => 'walkin@store.local',
            'password' => Hash::make('walkin123'),
            'first_name' => 'Walk In',
            'last_name' => 'Customer',
            'active' => true,
            'group_id' => $group->id,
            'author' => $admin->id,
        ]);

        // Assign customer role
        $user->assignRole(Role::STORECUSTOMER);

        $this->command->info('Created Walk In Customer');
        return Customer::find($user->id);
    }

    /**
     * Set the default customer for POS
     */
    private function setDefaultCustomer(Customer $customer): void
    {
        ns()->option->set('ns_customers_default', $customer->id);

        $this->command->info('Set Walk In Customer as default POS customer');
    }

    /**
     * Create sample cashier users for commission testing
     */
    private function createCashierUsers(User $admin): array
    {
        $cashiersData = [
            [
                'username' => 'cashier_alice',
                'first_name' => 'Alice',
                'last_name' => 'Smith',
                'email' => 'alice@store.local',
            ],
            [
                'username' => 'cashier_bob',
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'email' => 'bob@store.local',
            ],
            [
                'username' => 'cashier_carol',
                'first_name' => 'Carol',
                'last_name' => 'Williams',
                'email' => 'carol@store.local',
            ],
        ];

        $cashiers = [];
        foreach ($cashiersData as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'email' => $data['email'],
                    'password' => Hash::make('123456'),
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'active' => true,
                    'author' => $admin->id,
                ]
            );

            // Assign cashier role if not already assigned
            if (!$user->hasRoles([Role::STORECASHIER])) {
                $user->assignRole(Role::STORECASHIER);
            }

            $cashiers[] = $user;
        }

        $this->command->info('Created ' . count($cashiers) . ' cashier users');
        return $cashiers;
    }

    /**
     * Create sample commissions of all types
     */
    private function createCommissions(User $admin, array $categories, array $products, array $cashiers): void
    {
        $cashierRole = Role::where('namespace', Role::STORECASHIER)->first();

        // 1. Fixed Commission (DEFAULT) - For Beverages
        $fixedCommission = Commission::firstOrCreate(
            ['name' => 'Beverages Fixed Commission'],
            [
                'type' => Commission::TYPE_FIXED,
                'calculation_base' => Commission::BASE_FIXED,
                'value' => 0.50, // Default $0.50 per item
                'active' => true,
                'role_id' => $cashierRole->id,
                'description' => 'Fixed commission per beverage item sold. Default type.',
                'author' => $admin->id,
            ]
        );

        // Link to Beverages category
        CommissionProductCategory::firstOrCreate([
            'commission_id' => $fixedCommission->id,
            'category_id' => $categories[0]->id, // Beverages
        ]);

        // Add per-product values for Fixed commission
        $beverageProducts = array_filter($products, fn($p) => $p->category_id === $categories[0]->id);
        foreach ($beverageProducts as $index => $product) {
            // Different commission value per product
            $values = [0.25, 0.35, 0.15]; // Cola, Orange Juice, Water
            CommissionProductValue::firstOrCreate(
                [
                    'commission_id' => $fixedCommission->id,
                    'product_id' => $product->id,
                ],
                [
                    'value' => $values[$index] ?? 0.50,
                ]
            );
        }

        $this->command->info('Created Fixed commission with per-product values');

        // 2. Percentage Commission - For Electronics
        $percentageCommission = Commission::firstOrCreate(
            ['name' => 'Electronics Sales Commission'],
            [
                'type' => Commission::TYPE_PERCENTAGE,
                'calculation_base' => Commission::BASE_NET,
                'value' => 5.00, // 5% of net sale
                'active' => true,
                'role_id' => $cashierRole->id,
                'description' => '5% commission on electronics sales (after discounts).',
                'author' => $admin->id,
            ]
        );

        // Link to Electronics category
        CommissionProductCategory::firstOrCreate([
            'commission_id' => $percentageCommission->id,
            'category_id' => $categories[2]->id, // Electronics
        ]);

        $this->command->info('Created Percentage commission (5% net)');

        // 3. On The House Commission - For Snacks
        $onTheHouseCommission = Commission::firstOrCreate(
            ['name' => 'Snacks Bonus'],
            [
                'type' => Commission::TYPE_ON_THE_HOUSE,
                'calculation_base' => Commission::BASE_FIXED,
                'value' => 0.10, // $0.10 per item regardless of price/discounts
                'active' => true,
                'role_id' => $cashierRole->id,
                'description' => 'Small bonus per snack item - not affected by discounts or taxes.',
                'author' => $admin->id,
            ]
        );

        // Link to Snacks category
        CommissionProductCategory::firstOrCreate([
            'commission_id' => $onTheHouseCommission->id,
            'category_id' => $categories[1]->id, // Snacks
        ]);

        $this->command->info('Created On The House commission');

        // 4. Percentage Commission with Gross base - Alternative for high-value items
        $grossCommission = Commission::firstOrCreate(
            ['name' => 'Premium Electronics Bonus'],
            [
                'type' => Commission::TYPE_PERCENTAGE,
                'calculation_base' => Commission::BASE_GROSS,
                'value' => 2.00, // 2% of gross (before discounts)
                'active' => false, // Inactive by default, for testing
                'role_id' => $cashierRole->id,
                'description' => '2% bonus on gross price for premium items.',
                'author' => $admin->id,
            ]
        );

        // Also link to Electronics
        CommissionProductCategory::firstOrCreate([
            'commission_id' => $grossCommission->id,
            'category_id' => $categories[2]->id,
        ]);

        $this->command->info('Created Gross percentage commission (inactive)');

        $this->command->info('All commissions created successfully');
        $this->command->table(
            ['Commission', 'Type', 'Value', 'Category', 'Active'],
            [
                ['Beverages Fixed Commission', 'fixed', '$0.50/item', 'Beverages', 'Yes'],
                ['Electronics Sales Commission', 'percentage', '5% net', 'Electronics', 'Yes'],
                ['Snacks Bonus', 'on_the_house', '$0.10/item', 'Snacks', 'Yes'],
                ['Premium Electronics Bonus', 'percentage', '2% gross', 'Electronics', 'No'],
            ]
        );
    }
}
