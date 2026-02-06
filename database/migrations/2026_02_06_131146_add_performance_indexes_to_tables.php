<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds performance indexes to improve query speed:
     * - Foreign key indexes for relationship queries
     * - Status column indexes for filtering
     * - order_number index for searches
     * - created_at indexes for date-based queries
     */
    public function up(): void
    {
        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->index('table_id', 'idx_orders_table_id');
            $table->index('guest_id', 'idx_orders_guest_id');
            $table->index('waiter_id', 'idx_orders_waiter_id');
            $table->index('status', 'idx_orders_status');
            $table->index('order_number', 'idx_orders_order_number');
            $table->index('created_at', 'idx_orders_created_at');
            $table->index('order_source', 'idx_orders_order_source');
            // Composite index for common query patterns
            $table->index(['status', 'created_at'], 'idx_orders_status_created_at');
        });

        // Order items table indexes
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_items_order_id');
            $table->index('menu_item_id', 'idx_order_items_menu_item_id');
            $table->index('prep_status', 'idx_order_items_prep_status');
            // Composite index for kitchen/bar queries
            $table->index(['prep_status', 'order_id'], 'idx_order_items_prep_status_order_id');
        });

        // Menu items table indexes
        Schema::table('menu_items', function (Blueprint $table) {
            $table->index('category_id', 'idx_menu_items_category_id');
            $table->index('status', 'idx_menu_items_status');
            $table->index('prep_area', 'idx_menu_items_prep_area');
            // Composite index for low stock queries
            $table->index(['stock_quantity', 'low_stock_threshold'], 'idx_menu_items_stock');
        });

        // Menu categories table indexes
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->index('status', 'idx_menu_categories_status');
            $table->index('display_order', 'idx_menu_categories_display_order');
        });

        // Tables table indexes
        Schema::table('tables', function (Blueprint $table) {
            $table->index('status', 'idx_tables_status');
        });

        // Staff table indexes
        Schema::table('staff', function (Blueprint $table) {
            $table->index('role', 'idx_staff_role');
            $table->index('status', 'idx_staff_status');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('order_id', 'idx_payments_order_id');
            $table->index('payment_method', 'idx_payments_payment_method');
            $table->index('status', 'idx_payments_status');
            $table->index('created_at', 'idx_payments_created_at');
        });

        // Tips table indexes
        Schema::table('tips', function (Blueprint $table) {
            $table->index('order_id', 'idx_tips_order_id');
            $table->index('staff_id', 'idx_tips_staff_id');
            $table->index('created_at', 'idx_tips_created_at');
        });

        // Guests table indexes
        Schema::table('guests', function (Blueprint $table) {
            $table->index('phone_number', 'idx_guests_phone_number');
            $table->index('created_at', 'idx_guests_created_at');
        });

        // Guest sessions table indexes
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->index('guest_id', 'idx_guest_sessions_guest_id');
            $table->index('table_id', 'idx_guest_sessions_table_id');
            $table->index('session_token', 'idx_guest_sessions_session_token');
            $table->index('created_at', 'idx_guest_sessions_created_at');
        });

        // Inventory transactions table indexes
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index('menu_item_id', 'idx_inventory_transactions_menu_item_id');
            $table->index('transaction_type', 'idx_inventory_transactions_transaction_type');
            $table->index('created_at', 'idx_inventory_transactions_created_at');
        });

        // Audit logs table indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'idx_audit_logs_user_id');
            $table->index('action', 'idx_audit_logs_action');
            $table->index('model_type', 'idx_audit_logs_model_type');
            $table->index('created_at', 'idx_audit_logs_created_at');
        });

        // Order status logs table indexes
        Schema::table('order_status_logs', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_status_logs_order_id');
            $table->index('created_at', 'idx_order_status_logs_created_at');
        });

        // Settings table indexes
        Schema::table('settings', function (Blueprint $table) {
            $table->index('key', 'idx_settings_key');
        });

        // Error logs table indexes
        Schema::table('error_logs', function (Blueprint $table) {
            $table->index('severity', 'idx_error_logs_severity');
            $table->index('created_at', 'idx_error_logs_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_table_id');
            $table->dropIndex('idx_orders_guest_id');
            $table->dropIndex('idx_orders_waiter_id');
            $table->dropIndex('idx_orders_status');
            $table->dropIndex('idx_orders_order_number');
            $table->dropIndex('idx_orders_created_at');
            $table->dropIndex('idx_orders_order_source');
            $table->dropIndex('idx_orders_status_created_at');
        });

        // Order items table indexes
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_order_id');
            $table->dropIndex('idx_order_items_menu_item_id');
            $table->dropIndex('idx_order_items_prep_status');
            $table->dropIndex('idx_order_items_prep_status_order_id');
        });

        // Menu items table indexes
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex('idx_menu_items_category_id');
            $table->dropIndex('idx_menu_items_status');
            $table->dropIndex('idx_menu_items_prep_area');
            $table->dropIndex('idx_menu_items_stock');
        });

        // Menu categories table indexes
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropIndex('idx_menu_categories_status');
            $table->dropIndex('idx_menu_categories_display_order');
        });

        // Tables table indexes
        Schema::table('tables', function (Blueprint $table) {
            $table->dropIndex('idx_tables_status');
        });

        // Staff table indexes
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('idx_staff_role');
            $table->dropIndex('idx_staff_status');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_order_id');
            $table->dropIndex('idx_payments_payment_method');
            $table->dropIndex('idx_payments_status');
            $table->dropIndex('idx_payments_created_at');
        });

        // Tips table indexes
        Schema::table('tips', function (Blueprint $table) {
            $table->dropIndex('idx_tips_order_id');
            $table->dropIndex('idx_tips_staff_id');
            $table->dropIndex('idx_tips_created_at');
        });

        // Guests table indexes
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex('idx_guests_phone_number');
            $table->dropIndex('idx_guests_created_at');
        });

        // Guest sessions table indexes
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_guest_sessions_guest_id');
            $table->dropIndex('idx_guest_sessions_table_id');
            $table->dropIndex('idx_guest_sessions_session_token');
            $table->dropIndex('idx_guest_sessions_created_at');
        });

        // Inventory transactions table indexes
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_transactions_menu_item_id');
            $table->dropIndex('idx_inventory_transactions_transaction_type');
            $table->dropIndex('idx_inventory_transactions_created_at');
        });

        // Audit logs table indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user_id');
            $table->dropIndex('idx_audit_logs_action');
            $table->dropIndex('idx_audit_logs_model_type');
            $table->dropIndex('idx_audit_logs_created_at');
        });

        // Order status logs table indexes
        Schema::table('order_status_logs', function (Blueprint $table) {
            $table->dropIndex('idx_order_status_logs_order_id');
            $table->dropIndex('idx_order_status_logs_created_at');
        });

        // Settings table indexes
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('idx_settings_key');
        });

        // Error logs table indexes
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropIndex('idx_error_logs_severity');
            $table->dropIndex('idx_error_logs_created_at');
        });
    }
};
