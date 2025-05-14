---
description: 
globs: 
alwaysApply: true
---
# MVP requirements

as we are currently in the process of building an MVP we'd like to keep our scope relatively small but make sure our codebase wont have too many problems moving forward as our scope expands.

Currently, here is our MVP checklist. I will check off tasks that have been completed.

## Phase 1: Multi-Tenant System Setup

### 1. Laravel & Filament Base Setup

-   [x] Install Laravel & Filament Admin Panel
-   [x] Install & configure a multi-tenancy package
-   [x] Set up tenant identification (subdomains, custom domains, or organization-based)
-   [x] Implement a global super admin role (to manage multiple clubs)
-   [x] Implement tenant creation flow (new clubs can register & get their own isolated data)

### 2. Tenant-Specific Data Handling

-   [x] Ensure database separation for each club
-   [x] Implement automatic migrations & seeders per tenant
-   [x] Set up tenant-aware authentication (staff & members only see their club’s data)
-   [x] Secure queries using scoped models (to prevent cross-club access)

## Phase 2: Club & Member Management

### 1. Tenant (Club) Onboarding

-   [x] Club owner signs up & creates their cannabis club
-   [x] Assign club-specific custom settings (branding, pricing, inventory rules)
-   [x] Enable domain mapping (each club can have a custom subdomain/domain)

### 2. Member & Staff Management

-   [x] Club admins can invite staff
-   [x] Implement role-based permissions (Admin, Staff, Member)
-   [x] Member registration (by staff or self-signup with approval)
-   [x] Assign FOB ID or unique identifier to members
-   [x] Implement member wallet (preload funds, track purchases)

## Phase 3: Inventory & POS

### 1. Stock Control (Per-Tenant)

-   [x] Each club manages its own inventory
-   [x] Implement daily staff stock check-in & check-out
-   [x] Track inventory weight discrepancies per day
-   [x] Display real-time stock levels

### 2. Point of Sale (POS)

-   [x] Staff logs in → Selects member (via FOB scan or search)
-   [x] Choose product → Enter weight from scale → Deduct stock
-   [x] Deduct balance from member’s preloaded funds
-   [x] Log transaction for reporting

### Phase 4: Reporting & Analytics

-   [x] Generate daily sales reports per club
-   [x] Track weight discrepancies per staff member
-   [x] Implement multi-club analytics for super admin
-   [x] Staff transaction logs (who sold what, when)

### Phase 5: Data Migration & External Integrations

#### 1. Migrating Existing Clubs

-   [x] Build an adaptable CSV Import Tool (import members, inventory, transactions)
-   [x] Allow data mapping for smooth migration
-   [x] Enable clubs to migrate without data loss

### 2. API & Webhooks

-   [ ] Add Webhook Support (for external accounting, CRM tools) -- SKIPPED!
-   [ ] Implement a public API (for clubs that want integrations)

## Bonus: Future Enhancements

-   [ ] Custom Branding (each club can have its own theme/logo)
-   [ ] Automated Subscriptions (billing per club via Stripe)
-   [ ] AI-Powered Sales Insights (which products sell best per location)
