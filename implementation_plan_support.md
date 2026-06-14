# SaaS Support & Admin Infrastructure Implementation Plan

## 1. Overview
The goal is to evolve the current multi-tenant application into a managed SaaS platform. This involves introducing a central "Command Center" for platform administrators and a "Support Portal" for tenants to request assistance.

---

## 2. Data Architecture

### 2.1 Models & Migrations

#### `SupportTicket`
- `id`: Primary Key
- `tenant_id`: Foreign Key (The tenant raising the ticket)
- `user_id`: Foreign Key (The specific user who raised it)
- `assigned_to`: Foreign Key (Null until claimed by a Support Agent)
- `subject`: String
- `category`: Enum (Billing, Technical, Bug, Feature Request)
- `priority`: Enum (Low, Medium, High, Urgent)
- `status`: Enum (Open, In Progress, Pending Response, Resolved, Closed)
- `is_escalated`: Boolean
- `timestamps`: created_at, updated_at

#### `TicketMessage`
- `id`: Primary Key
- `ticket_id`: Foreign Key
- `user_id`: Foreign Key (The staff or tenant user sending the reply)
- `message`: Text
- `attachment_path`: String (Optional)
- `is_internal`: Boolean (Private notes for support staff)

#### `Plan` (Pricing Module)
- `id`: Primary Key
- `name`: String (Starter, Pro, Enterprise)
- `slug`: String
- `description`: Text
- `price_monthly`: Decimal
- `price_yearly`: Decimal
- `features`: JSON (List of enabled features, e.g., max bots, max users)
- `stripe_price_id`: String (Reference for the gateway)

#### `Subscription` (Handled by Laravel Cashier)
- `id`: Primary Key
- `tenant_id`: Foreign Key
- `plan_id`: Foreign Key
- `status`: active, trialing, past_due, canceled
- `trial_ends_at`: Timestamp
- `ends_at`: Timestamp

#### `Post` (Blog & Resources CMS)
- `id`: Primary Key
- `title`: String
- `slug`: String (Unique)
- `content`: LongText (HTML/Markdown)
- `excerpt`: Text
- `featured_image`: String (URL)
- `type`: Enum (blog, resource, article)
- `category`: String
- `status`: Enum (draft, published, scheduled)
- `published_at`: Timestamp
- `author_id`: Foreign Key (System User)

---

## 3. Authentication & Global Access

### 3.1 Global Roles (`Spatie/Permission`)
- **Super Admin**: Full platform access. Bypasses all multi-tenancy constraints via `Gate::before`.
- **Support Agent**: Access to `/admin/support` and read-only access to tenant workspaces for debugging.

### 3.2 User Model Updates
- Add `is_system_user`: Boolean. Used to distinguish SaaS staff from Tenant users.
- Update `DashboardLayout` logic to route `System Users` to the Admin Console by default.

---

## 4. Admin & Support Dashboards (The Command Center)

### 4.1 Super Admin Dashboard (KPIs & Graphs)
- **Revenue Analytics**:
    - Monthly Recurring Revenue (MRR) - Trend graph.
    - Churn Rate vs Activation Rate.
- **Growth metrics**:
    - New Tenant Signups (Bar chart per month).
    - Total Active Users across all tenants.
- **System Health**:
    - API usage stats (if applicable).
    - Database/Storage consumption per tenant.

### 4.2 Support Agent Console
- **Ticket Queue**: A unified list of all open tickets platform-wide.
- **First Response Time (FRT)**: Metric showing how fast support reacts.
- **Resolution Rate**: Graph showing tickets opened vs tickets closed.

## 5. Billing & Pricing System

### 5.1 Gateway Integration (Stripe)
- **Laravel Cashier**: Implementation of Cashier for subscription management.
- **Webhooks**: Handling `invoice.paid`, `customer.subscription.deleted`, and `payment_intent.succeeded` to keep tenant status in sync.
- **Customer Portal**: Directing tenants to Stripe's hosted billing portal for card management and invoices.

### 5.2 Pricing Module
- **Tiered Access**: Middleware to check if a tenant's plan allows access to certain modules (e.g., Social Planner restricted to 'Pro' plan).
- **Usage Limits**: Tracking resource consumption (e.g., number of AI Bot interactions) against plan limits.
- **Pricing UI**: A premium pricing table on the landing page and a "Billing" tab in Tenant Settings.

## 6. Content Management System (CMS)

### 6.1 Blog & Resource Management
- **Editor**: Implementation of a Rick Text Editor (e.g., TipTap or Quill) for system admins to write content.
- **SEO Optimization**: Fields for meta titles, descriptions, and OpenGraph images per post.
- **Media Library**: Simple image upload and management for blog posts.
- **Public API/Routing**: Controllers to serve this content on the main landing page/marketing site.

---

## 5. Tenant-Facing Support Portal

### 5.1 Features
- **Ticket History**: List of past and active requests.
- **Knowledge Base Integration**: (Optional) Show articles before allowing ticket submission.
- **Support Chat**: Threaded conversation with the central team.

---

## 6. Implementation Roadmap

### Phase 1: Foundation (Current Week)
- Run migrations for Tickets.
- Seed the `super-admin` role and the first system user.
- Configure `Gate::before` in `AppServiceProvider`.

### Phase 2: Internal Support UI
- Build the `Admin/Support` view (Ticket list & detail).
- Implement global analytics (Aggregated KPIs from `tenants` and `users` tables).

### Phase 3: Tenant Integration
- Add the "Support" sidebar item in `DashboardLayout`.
- Build the "Raise Ticket" modal/page for tenant users.

### Phase 4: Polish & Notifications
- Implement email notifications for ticket updates.
- Add real-time "Support Alert" for Agents when urgent tickets arrive.

### Phase 5: Billing & Payments
- Install and configure Laravel Cashier.
- Design and implement the Pricing Table UI.
- Implement Plan-based middleware for feature gating.
- Set up Stripe Webhooks and test the end-to-end subscription flow.

### Phase 6: Content & Marketing
- Build the CMS backend (Post migrations, controller, and repository).
- Create the Admin CMS interface (Listing, Creating, Editing posts).
- Integrate the Blog and Resources section into the public website.
