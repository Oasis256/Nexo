<?php

/**
 * WhatsApp Module - Database Migration
 * Consolidated migration for all WhatsApp module tables
 * @package 6.0.5
 */

namespace Modules\WhatsApp\Migrations;

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Create message templates table
        if (!Schema::hasTable('nexopos_whatsapp_templates')) {
            Schema::create('nexopos_whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique()->comment('Template identifier');
                $table->string('label', 255)->comment('Display name');
                $table->string('event', 100)->nullable()->comment('Trigger event name');
                $table->text('content')->comment('Message content with placeholders');
                $table->boolean('is_active')->default(true);
                $table->string('target', 20)->default('customer')->comment('customer, staff, both');
                $table->json('meta')->nullable()->comment('Additional settings');
                $table->integer('author')->unsigned()->nullable();
                $table->timestamps();

                $table->index('event');
                $table->index('is_active');
                $table->index('target');
            });
        }

        // 2. Create message logs table
        if (!Schema::hasTable('nexopos_whatsapp_logs')) {
            Schema::create('nexopos_whatsapp_logs', function (Blueprint $table) {
                $table->id();
                $table->string('whatsapp_message_id')->nullable()->comment('WhatsApp API message ID');
                $table->foreignId('template_id')
                    ->nullable()
                    ->constrained('nexopos_whatsapp_templates')
                    ->nullOnDelete();
                $table->string('recipient_phone', 20)->comment('Phone number with country code');
                $table->string('recipient_name')->nullable();
                $table->string('recipient_type')->default('customer')->comment('customer or user');
                $table->integer('recipient_id')->unsigned()->nullable();
                $table->string('message_type')->default('text')->comment('text, template, image, document');
                $table->text('content')->comment('Actual sent content');
                $table->string('status')->default('pending')->comment('pending, sent, delivered, read, failed');
                $table->text('error_message')->nullable();
                $table->string('error_code')->nullable();
                $table->string('related_type')->nullable()->comment('order, customer, etc.');
                $table->integer('related_id')->unsigned()->nullable();
                $table->json('meta')->nullable()->comment('Additional data');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->integer('author')->unsigned()->nullable()->comment('User who initiated the message');
                $table->timestamps();

                $table->index('whatsapp_message_id');
                $table->index('recipient_phone');
                $table->index('recipient_type');
                $table->index('status');
                $table->index('message_type');
                $table->index(['related_type', 'related_id']);
                $table->index('created_at');
            });
        }

        // 3. Create permissions
        $this->createPermissions();

        // 4. Seed default templates
        $this->seedDefaultTemplates();
    }

    /**
     * Create WhatsApp permissions
     */
    protected function createPermissions()
    {
        if (!defined('NEXO_CREATE_PERMISSIONS')) {
            define('NEXO_CREATE_PERMISSIONS', true);
        }

        $permissions = [
            [
                'namespace' => 'whatsapp.dashboard',
                'name' => __('WhatsApp Dashboard'),
                'description' => __('Access WhatsApp module dashboard'),
            ],
            [
                'namespace' => 'whatsapp.send',
                'name' => __('Send WhatsApp Messages'),
                'description' => __('Send WhatsApp messages to customers'),
            ],
            [
                'namespace' => 'whatsapp.templates.create',
                'name' => __('Create WhatsApp Templates'),
                'description' => __('Create message templates'),
            ],
            [
                'namespace' => 'whatsapp.templates.read',
                'name' => __('View WhatsApp Templates'),
                'description' => __('View message templates'),
            ],
            [
                'namespace' => 'whatsapp.templates.update',
                'name' => __('Update WhatsApp Templates'),
                'description' => __('Update message templates'),
            ],
            [
                'namespace' => 'whatsapp.templates.delete',
                'name' => __('Delete WhatsApp Templates'),
                'description' => __('Delete message templates'),
            ],
            [
                'namespace' => 'whatsapp.logs.read',
                'name' => __('View WhatsApp Logs'),
                'description' => __('View message logs'),
            ],
            [
                'namespace' => 'whatsapp.logs.delete',
                'name' => __('Delete WhatsApp Logs'),
                'description' => __('Delete message logs'),
            ],
            [
                'namespace' => 'whatsapp.settings',
                'name' => __('WhatsApp Settings'),
                'description' => __('Manage WhatsApp module settings'),
            ],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrNew(['namespace' => $perm['namespace']]);
            $permission->name = $perm['name'];
            $permission->namespace = $perm['namespace'];
            $permission->description = $perm['description'];
            $permission->save();
        }

        // Assign all permissions to admin
        $admin = Role::namespace('admin');
        if ($admin) {
            $admin->addPermissions(
                Permission::where('namespace', 'like', 'whatsapp.%')
                    ->get()
                    ->pluck('namespace')
                    ->toArray()
            );
        }

        // Assign read permissions to store admin
        $storeAdmin = Role::namespace('nexopos.store.administrator');
        if ($storeAdmin) {
            $storeAdmin->addPermissions([
                'whatsapp.dashboard',
                'whatsapp.send',
                'whatsapp.templates.read',
                'whatsapp.logs.read',
            ]);
        }
    }

    /**
     * Seed default message templates
     */
    protected function seedDefaultTemplates()
    {
        $templates = [
            [
                'name' => 'order_confirmation',
                'label' => __('Order Confirmation'),
                'event' => 'order_created',
                'target' => 'customer',
                'content' => "Hello {customer_name}!\n\nThank you for your order #{order_id}.\n\nOrder Total: {order_total}\nPayment Method: {payment_method}\n\nWe will notify you when your order is ready.\n\nThank you for shopping with {store_name}!",
            ],
            [
                'name' => 'payment_received',
                'label' => __('Payment Received'),
                'event' => 'payment_created',
                'target' => 'customer',
                'content' => "Hello {customer_name}!\n\nWe have received your payment of {payment_amount} for order #{order_id}.\n\nPayment Method: {payment_method}\nDate: {date}\n\nThank you for your payment!",
            ],
            [
                'name' => 'order_refunded',
                'label' => __('Order Refunded'),
                'event' => 'order_refunded',
                'target' => 'customer',
                'content' => "Hello {customer_name},\n\nYour refund of {refund_amount} for order #{order_id} has been processed.\n\nReason: {refund_reason}\n\nThe amount will be credited within 5-7 business days.\n\nIf you have any questions, please contact us.",
            ],
            [
                'name' => 'delivery_update',
                'label' => __('Delivery Update'),
                'event' => 'delivery_status_changed',
                'target' => 'customer',
                'content' => "Hello {customer_name}!\n\nYour order #{order_id} status has been updated.\n\nStatus: {delivery_status}\n\nThank you for your patience!",
            ],
            [
                'name' => 'welcome_customer',
                'label' => __('Welcome Customer'),
                'event' => 'customer_created',
                'target' => 'customer',
                'content' => "Welcome to {store_name}, {customer_name}!\n\nThank you for registering with us. We're excited to have you as a customer.\n\nVisit us anytime for great products and services!",
            ],
            [
                'name' => 'low_stock_alert',
                'label' => __('Low Stock Alert'),
                'event' => 'low_stock',
                'target' => 'staff',
                'content' => "⚠️ Low Stock Alert\n\nThe following products are running low:\n\n{product_list}\n\nPlease restock these items soon.",
            ],
            [
                'name' => 'new_order_staff',
                'label' => __('New Order (Staff)'),
                'event' => 'order_created',
                'target' => 'staff',
                'content' => "📦 New Order Received!\n\nOrder: #{order_id}\nCustomer: {customer_name}\nTotal: {order_total}\nPayment: {payment_method}\n\nItems:\n{product_list}",
            ],
            [
                'name' => 'order_voided',
                'label' => __('Order Voided'),
                'event' => 'order_voided',
                'target' => 'customer',
                'content' => "Hello {customer_name},\n\nYour order #{order_id} has been cancelled.\n\nIf you have any questions, please contact us.\n\nThank you for your understanding.",
            ],
        ];

        $templatesTable = app('db')->table('nexopos_whatsapp_templates');

        foreach ($templates as $template) {
            if (!$templatesTable->where('name', $template['name'])->exists()) {
                $templatesTable->insert([
                    'name' => $template['name'],
                    'label' => $template['label'],
                    'event' => $template['event'],
                    'target' => $template['target'],
                    'content' => $template['content'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Drop tables
        Schema::dropIfExists('nexopos_whatsapp_logs');
        Schema::dropIfExists('nexopos_whatsapp_templates');

        // Remove permissions
        Permission::where('namespace', 'like', 'whatsapp.%')->delete();
    }
};
