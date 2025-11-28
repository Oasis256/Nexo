# NsCommissions Module - Refactoring Blueprint

## Overview

This document outlines the comprehensive refactoring of the NsCommissions module to support:

1. **On The House** - Fixed commission value regardless of discounts/taxes
2. **Fixed** - Individual commission value per item (with foreign key to products)
3. **Percentage** - Flat percentage for all items
4. **POS User Selection** - Ability to select commission earner per item during transaction

---

## Implementation Status

### Phase 1: Database & Models - COMPLETE

#### New Migrations Created

| Migration | Purpose |
|-----------|---------|
| UpdateCommissionsTableNov2025.php | Adds calculation_base column |
| CreateCommissionProductValuesTable.php | New table for per-product commission values |
| UpdateOrdersCommissionsTableNov2025.php | Links earned commissions to specific order products |
| CreateOrderProductCommissionAssignmentsTable.php | Tracks POS user selection per item |

#### Models Updated/Created

| Model | Status | Description |
|-------|--------|-------------|
| Commission.php | Updated | New types: on_the_house, fixed, percentage |
| CommissionProductValue.php | New | Per-product commission values for Fixed type |
| OrderProductCommissionAssignment.php | New | POS user selection per order item |
| EarnedCommission.php | Updated | New fields: order_product_id, quantity, commission_type |

---

### Phase 2: Service Layer - COMPLETE

#### CommissionCalculatorService.php

Core calculation service with methods:

- processOrderCommissions(Order) - Main entry point
- processProductCommission(Order, OrderProduct) - Per-item calculation
- findApplicableCommission(User, OrderProduct) - Find matching commission
- calculateCommissionValue(Commission, OrderProduct) - Type-based calculation
- assignCommissionUser() - POS user assignment per item
- previewCommission() - Preview expected commission in POS

---

### Phase 3: Event Handlers - COMPLETE

- Refactored trackCommissions() to use CommissionCalculatorService
- Refactored deleteCommissions() to use service
- Removed legacy calculation methods

---

### Phase 4: CRUD Updates - COMPLETE

- Added On The House option to type dropdown
- Added calculation_base field (shows for percentage type)
- Added product_values tab placeholder for Fixed type

---

### Phase 5: API Routes - COMPLETE

| Endpoint | Method | Purpose |
|----------|--------|---------|
| /commissions/eligible-users | GET | Get users eligible for commission |
| /commissions/preview | POST | Preview commission calculation |
| /orders/{order}/products/{orderProduct}/commission-assignment | POST | Assign user to earn commission |
| /commissions/{commission}/product-values | GET/POST | Manage per-product values |
| /commissions/products/search | GET | Search products for value assignment |

---

## Completed Tasks

### Phase 6: Vue Components - COMPLETE

1. nsCommissionProductValues.vue - Per-product value assignment in commission form
2. nsProductCommissionUserSelect.vue - POS component for selecting commission earner

### Phase 7: Run Migrations - COMPLETE

php artisan modules:migrate NsCommissions

Tables created:
- nexopos_commission_product_values
- nexopos_order_product_commission_assignments

Columns added:
- nexopos_commissions.calculation_base
- nexopos_orders_commissions.order_product_id
- nexopos_orders_commissions.product_id
- nexopos_orders_commissions.quantity
- nexopos_orders_commissions.commission_type
- nexopos_orders_commissions.base_amount

---

## Commission Type Behavior

### On The House (on_the_house)

Commission = Value x Quantity

- Fixed value regardless of price, discounts, or taxes

### Fixed (fixed)

Commission = (Product Value OR Default Value) x Quantity

- Per-product values via CommissionProductValue table

### Percentage (percentage)

Commission = (Base Amount x Percentage / 100) x Quantity

- Calculation bases: Gross, Net, or Fixed

---

## File Changes Summary

### New Files

- Migrations/UpdateCommissionsTableNov2025.php
- Migrations/CreateCommissionProductValuesTable.php
- Migrations/UpdateOrdersCommissionsTableNov2025.php
- Migrations/CreateOrderProductCommissionAssignmentsTable.php
- Models/CommissionProductValue.php
- Models/OrderProductCommissionAssignment.php
- Services/CommissionCalculatorService.php

### Modified Files

- Models/Commission.php
- Models/EarnedCommission.php
- Crud/CommissionsCrud.php
- Events/NsCommissionsEvent.php
- Routes/api.php
- Http/Controllers/NsCommissionsController.php
