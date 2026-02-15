# RenCommissions Audit Findings (NexoPOS Instruction Compliance)

Date: 2026-02-13  
Scope: `modules/RenCommissions`  
Baseline: `.github/instructions/*` (modules, quick-reference, view-injection, blade-layouts, permissions, httpclient, migrations)

## Summary
- Critical issues: 2
- High issues: 2
- Medium issues: 3
- Positive compliance points: 3

## Findings

### 1. Critical - Dashboard frontend uses blocking sync XHR instead of NexoPOS HTTP client
- File: `modules/RenCommissions/Resources/ts/dashboard.ts:199`
- Evidence: `XMLHttpRequest` with `xhr.open(..., false)` and synchronous `getSync(...)` usage.
- Impact:
  - Blocks main thread.
  - Can freeze UI and cause timeout/race symptoms.
  - Diverges from `nexopos-httpclient.instructions.md` recommended `nsHttpClient` observable pattern.

### 2. Critical - Conflicting POS session schema migration causes model/table mismatch
- Files:
  - `modules/RenCommissions/Migrations/2026_02_06_000002_create_multistore_pos_commission_sessions.php:90`
  - `modules/RenCommissions/Models/PosCommissionSession.php:38`
- Evidence:
  - Migration fallback table: `rencommissions_pos_commission_sessions`.
  - Model table: `rencommissions_pos_sessions`.
  - Migration enum includes `flat`, module logic expects `on_the_house`.
- Impact:
  - Data integrity and runtime query mismatch risk.
  - Potential silent failures depending on store context.

### 3. High - Permission namespace convention differs from instruction guidance
- File: `modules/RenCommissions/Migrations/2026_02_10_000005_create_rencommissions_permissions.php:21`
- Evidence: Uses `rencommissions.*` namespaces.
- Instruction reference: `nexopos-permissions.instructions.md` recommends `nexopos.{module}.{action}`.
- Impact:
  - Convention drift and potential interoperability inconsistencies.

### 4. High - Permission creation duplicated in migration and provider
- Files:
  - `modules/RenCommissions/Migrations/2026_02_10_000005_create_rencommissions_permissions.php:34`
  - `modules/RenCommissions/Providers/RenCommissionsServiceProvider.php:58`
- Evidence: Same permission set is created/managed in two places.
- Impact:
  - Drift risk, maintenance overhead, and migration/runtime coupling.

### 5. Medium - Dashboard header include not hook-filtered
- File: `modules/RenCommissions/Resources/Views/dashboard/index.blade.php:5`
- Evidence: Direct include `@include('common.dashboard-header')`.
- Instruction reference: `nexopos-blade-layouts.instructions.md` recommends hook-filtered header pattern for extensibility.
- Impact:
  - Reduced compatibility with header customizations injected by other modules.

### 6. Medium - Module package manifest missing
- File: `modules/RenCommissions/manifest.json` (missing)
- Evidence: Not present in module root.
- Instruction reference: `nexopos-modules.instructions.md` recommends manifest include/exclude controls for clean exports.
- Impact:
  - Packaging/distribution hygiene gap (especially for export workflows).

### 7. Medium - No automated tests found
- Scope scan: no module tests under `modules/RenCommissions`
- Impact:
  - Higher regression risk for payout logic, permission behavior, and dashboard APIs.

## Positive Compliance
- Uses `@moduleViteAssets` in module Blade views:
  - `modules/RenCommissions/Resources/Views/dashboard/index.blade.php:17`
  - `modules/RenCommissions/Resources/Views/pos/inject-assets.blade.php:2`
- Web/API routes are permission-gated with `NsRestrictMiddleware`.
- Migration files are timestamped and mostly anonymous-class style.

## Priority Remediation Plan

### P0 (Immediate)
1. Replace sync XHR/getSync dashboard transport with `nsHttpClient` (or a single non-blocking transport strategy).
2. Consolidate/fix POS session schema to a single canonical table and enum set matching model/service logic.

### P1 (Near-term)
1. Normalize permission strategy:
   - Keep one source of truth (migration or provider, not both).
   - Decide namespace convention (`rencommissions.*` compatibility vs `nexopos.*` standard).
2. Apply hook-filtered dashboard header include pattern.

### P2 (Hardening)
1. Add `manifest.json` for module packaging.
2. Add feature/unit tests for:
   - Dashboard APIs.
   - Commission state transitions (pending -> paid/voided).
   - Permission and route access controls.

