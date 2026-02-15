<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookingvisitors_bookings')) {
            Schema::create('bookingvisitors_bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('uuid', 32)->unique();
                $table->enum('channel', ['phone', 'website', 'whatsapp_business_api'])->default('phone');
                $table->enum('status', ['draft', 'confirmed', 'checked_in', 'completed', 'cancelled'])->default('confirmed');
                $table->string('customer_name', 190);
                $table->string('customer_phone', 50)->nullable();
                $table->string('customer_email', 190)->nullable();
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->dateTime('confirmed_at')->nullable();
                $table->dateTime('checked_in_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('cancelled_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('author')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'status', 'start_at']);
                $table->index(['store_id', 'channel', 'created_at']);
            });
        }

        if (! Schema::hasTable('bookingvisitors_booking_guests')) {
            Schema::create('bookingvisitors_booking_guests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('booking_id');
                $table->string('guest_name', 190);
                $table->string('guest_phone', 50)->nullable();
                $table->enum('status', ['pending', 'granted', 'denied'])->default('pending');
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('author')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'booking_id', 'status']);
            });
        }

        if (! Schema::hasTable('bookingvisitors_qr_tokens')) {
            Schema::create('bookingvisitors_qr_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('booking_id');
                $table->enum('scope', ['booking', 'guest'])->default('booking');
                $table->string('token_hash', 64)->unique();
                $table->dateTime('expires_at')->nullable();
                $table->dateTime('used_at')->nullable();
                $table->unsignedBigInteger('used_by')->nullable();
                $table->dateTime('revoked_at')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('author')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'booking_id', 'scope']);
            });
        }

        if (! Schema::hasTable('bookingvisitors_visit_events')) {
            Schema::create('bookingvisitors_visit_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('booking_id');
                $table->enum('event_type', ['check_in', 'check_out', 'guest_access_granted', 'guest_access_denied'])->default('check_in');
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('author')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'booking_id', 'event_type']);
            });
        }

        if (! Schema::hasTable('bookingvisitors_channel_messages')) {
            Schema::create('bookingvisitors_channel_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->enum('channel', ['local', 'website', 'whatsapp_business_api'])->default('local');
                $table->enum('message_type', ['booking_confirmation', 'reminder', 'checkin_alert'])->default('booking_confirmation');
                $table->string('recipient', 190)->nullable();
                $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'skipped'])->default('queued');
                $table->string('provider_ref', 190)->nullable();
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('author')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'channel', 'status']);
            });
        }

        if (! Schema::hasTable('bookingvisitors_audit_logs')) {
            Schema::create('bookingvisitors_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('entity_type', 60);
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('action', 120);
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('author')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'entity_type', 'entity_id']);
                $table->index(['store_id', 'action', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookingvisitors_audit_logs');
        Schema::dropIfExists('bookingvisitors_channel_messages');
        Schema::dropIfExists('bookingvisitors_visit_events');
        Schema::dropIfExists('bookingvisitors_qr_tokens');
        Schema::dropIfExists('bookingvisitors_booking_guests');
        Schema::dropIfExists('bookingvisitors_bookings');
    }
};
