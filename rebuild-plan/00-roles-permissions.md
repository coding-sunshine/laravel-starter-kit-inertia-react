# Roles & Permissions — Fusion CRM v4

Chief reads this before Task 1 (Step 0) and seeds exactly these roles, permissions, and assignments. The kit ships with Spatie `laravel-permission` + Filament's role/permission UI — the defaults defined here are the **seed state**; superadmin can edit them at runtime via Filament → Settings → Roles & Permissions.

---

## 1. Active User Roles (users who log in to the CRM)

| Role | Slug | Description | Scope |
|---|---|---|---|
| Super Admin | `superadmin` | PIAB staff — full system access including system settings, same-device detection, all orgs | Global (all orgs) |
| PIAB Admin | `piab_admin` | PIAB staff — full CRM access across all orgs, minus system config and same-device detection | Global (all orgs) |
| Subscriber | `subscriber` | Paying CRM customer (org owner). Scoped to own org. | Org-scoped |
| Agent | `agent` | Sales agent under subscriber's org. Scoped to own org contacts/tasks. | Org-scoped |
| Sales Agent | `sales_agent` | Legacy alias for agent — identical permissions to agent. Keep as alias. | Org-scoped |
| BDM | `bdm` | Business development manager — wider contact/subscriber access within org. | Org-scoped |
| Referral Partner | `referral_partner` | External partner who refers leads. Limited to contacts/enquiries they own. | Org-scoped |
| Affiliate | `affiliate` | Affiliate — can see subscribers and property portal. | Org-scoped |
| Property Manager | `property_manager` | Full property CRUD (projects/lots) + enquiry forms. | Org-scoped |

## 2. Contact-type Roles (NOT login roles — used as contact.type in CRM)

These are NOT assignable to users. They exist as role records for Spatie compatibility but have `is_user_role = false`. They represent the KIND of person a contact is.

| Contact Type | Slug | Mapped from v3 |
|---|---|---|
| Lead | `lead` | v3 lead (default, is_partner=false) |
| Client | `client` | v3 client (converted buyer) |
| Partner | `partner` | v3 referral_partner contact type |
| Affiliate | `affiliate_contact` | v3 affiliate contact |
| Finance Broker | `finance_broker` | v3 finance_broker |
| Conveyancer | `conveyancer` | v3 conveyancer |
| Accountant | `accountant` | v3 accountant |
| Developer | `developer_contact` | v3 developer |
| Insurance Agency | `insurance_agency` | v3 insurance_agency |
| Event Coordinator | `event_coordinator` | v3 event_coordinator |
| SaaS Lead | `saas_lead` | New in v4 — contact_origin=saas_product |
| Other | `other` | Catch-all |

---

## 3. Contact Type Field (contacts table)

```php
// contacts.type (enum, nullable — defaults to 'lead' on import)
enum ContactType: string {
    case Lead            = 'lead';           // property buyer/enquirer (default)
    case Client          = 'client';         // converted — has active reservation/sale
    case Partner         = 'partner';        // referral partner (v3 is_partner=true)
    case Affiliate       = 'affiliate';
    case FinanceBroker   = 'finance_broker';
    case Conveyancer     = 'conveyancer';
    case Accountant      = 'accountant';
    case Developer       = 'developer';
    case InsuranceAgency = 'insurance_agency';
    case EventCoordinator= 'event_coordinator';
    case SaasLead        = 'saas_lead';      // contact_origin=saas_product
    case Other           = 'other';
}
```

**Import mapping from v3:**
- `is_partner = true` → type = `partner`
- All other v3 leads → type = `lead`
- Post-import: promote to `client` automatically when a Reservation/Sale exists for this contact

---

## 4. Permission List (seed all, assign per role below)

### Dashboard
| Permission | Description |
|---|---|
| `contacts.view.dashboard` | View dashboard |

### Contacts
| Permission | Description |
|---|---|
| `contacts.view` | List contacts (org-scoped for non-superadmin) |
| `contacts.create` | Create contacts |
| `contacts.edit` | Edit any contact in org |
| `contacts.delete` | Delete/archive contact |
| `contacts.view.own` | View only own assigned contacts |
| `contacts.edit.own` | Edit only own assigned contacts |
| `contacts.export` | Export contact list to CSV |
| `contacts.import` | Import contacts via CSV |
| `contacts.merge` | Merge duplicate contacts |

### Property Portal
| Permission | Description |
|---|---|
| `projects.view` | List projects |
| `projects.view.details` | View full project details |
| `projects.create` | Create projects |
| `projects.edit` | Edit projects |
| `projects.delete` | Delete projects |
| `lots.view` | List lots |
| `lots.view.details` | View full lot details |
| `lots.create` | Create lots |
| `lots.edit` | Edit lots |
| `lots.delete` | Delete lots |
| `potential_properties.view` | View potential properties |
| `potential_properties.manage` | Create/edit/delete potential properties |
| `potential_properties.map` | View map of potential properties |

### Sales & Reservations
| Permission | Description |
|---|---|
| `reservations.view` | View reservations |
| `reservations.create` | Create reservations |
| `reservations.edit` | Edit reservations |
| `sales.view` | View sales |
| `sales.create` | Create sales |
| `commissions.view` | View commissions |
| `spr.view` | View SPR history |
| `spr.manage` | Edit/delete SPR records |

### Tasks
| Permission | Description |
|---|---|
| `tasks.view` | View all tasks in org |
| `tasks.view.own` | View only own tasks |
| `tasks.create` | Create tasks |
| `tasks.edit` | Edit tasks |
| `tasks.delete` | Delete tasks |

### Online Forms / Enquiries
| Permission | Description |
|---|---|
| `enquiries.view` | View property enquiries |
| `enquiries.property_search.view` | View property search requests |
| `enquiries.reservation.view` | View reservation forms |
| `enquiries.finance.view` | View finance assessment forms |

### Marketing
| Permission | Description |
|---|---|
| `marketing.view` | View marketing tools section |
| `flyers.view` | View brochures/flyers |
| `flyers.create` | Create flyers |
| `flyers.edit` | Edit flyers |
| `flyers.delete` | Delete flyers |
| `campaign_websites.view` | View campaign websites |
| `campaign_websites.create` | Create campaign websites |
| `campaign_websites.edit` | Edit campaign websites |
| `campaign_websites.delete` | Delete campaign websites |
| `mail_lists.view` | View mail lists |
| `mail_jobs.view` | View mail job status |
| `websites.view` | View PHP/WP websites |

### Reports
| Permission | Description |
|---|---|
| `reports.view` | View reports section |
| `reports.network_activity` | View network activity report |
| `reports.notes_history` | View notes history report |
| `reports.login_history` | View login history report |
| `reports.same_device` | View same-device detection (superadmin only) |
| `reports.website` | View website report |
| `reports.wp_website` | View WP website report |
| `reports.campaign` | View campaign website report |

### Resources
| Permission | Description |
|---|---|
| `resources.view` | View resources |
| `resources.create` | Create resources |
| `resources.edit` | Edit resources |
| `resources.delete` | Delete resources |

### Admin / System
| Permission | Description |
|---|---|
| `users.view` | List users in org |
| `users.create` | Invite/create users |
| `users.edit` | Edit users |
| `users.delete` | Remove users |
| `roles.manage` | Edit roles and permissions (superadmin only) |
| `api_keys.view` | View API keys |
| `api_keys.manage` | Create/revoke API keys |
| `ai_credits.view` | View AI credit usage |
| `ai_credits.manage` | Adjust AI credit limits (superadmin/subscriber) |
| `settings.system` | System-level settings (superadmin only) |
| `orgs.view_all` | View all organisations (superadmin only) |
| `orgs.manage` | Create/delete organisations (superadmin only) |
| `agents.view` | View agent list |
| `co_living_projects.view` | View Co-Living/NEXTGEN projects |

---

## 5. Role → Permission Matrix

### superadmin — ALL permissions

### piab_admin
All permissions **except**:
- `reports.same_device`
- `roles.manage`
- `settings.system`
- `orgs.manage`

### subscriber
```
contacts.view.dashboard
contacts.view, contacts.create, contacts.edit, contacts.delete, contacts.export, contacts.import
projects.view, projects.view.details
lots.view, lots.view.details
potential_properties.map
reservations.view, reservations.create
sales.view
commissions.view
tasks.view.own, tasks.create, tasks.edit, tasks.delete
enquiries.view, enquiries.property_search.view, enquiries.reservation.view, enquiries.finance.view
marketing.view, flyers.view, flyers.create, flyers.edit, flyers.delete
campaign_websites.view, campaign_websites.create, campaign_websites.edit, campaign_websites.delete
mail_lists.view, mail_jobs.view, websites.view
reports.view, reports.website, reports.wp_website, reports.campaign
resources.view
users.view, users.create
api_keys.view, api_keys.manage
ai_credits.view
spr.view
agents.view
```

### agent / sales_agent
```
contacts.view.dashboard
contacts.view, contacts.create, contacts.edit, contacts.delete
contacts.view.own, contacts.edit.own
projects.view, projects.view.details
lots.view, lots.view.details
reservations.view
sales.view
commissions.view
tasks.view.own, tasks.create, tasks.edit, tasks.delete
enquiries.view, enquiries.reservation.view, enquiries.property_search.view, enquiries.finance.view
resources.view
mail_lists.view
co_living_projects.view
```

### bdm
```
contacts.view.dashboard
contacts.view, contacts.create, contacts.edit, contacts.delete
projects.view, projects.view.details
lots.view, lots.view.details
reservations.view, reservations.create
sales.view
commissions.view
tasks.view.own, tasks.create, tasks.edit, tasks.delete
enquiries.view, enquiries.property_search.view, enquiries.reservation.view, enquiries.finance.view
resources.view
mail_lists.view
users.view (subscribers only — for BDM to onboard new subscribers)
users.create (subscribers only)
```

### referral_partner
```
contacts.view.dashboard
contacts.view.own, contacts.create, contacts.edit.own
tasks.view.own, tasks.create, tasks.edit, tasks.delete
enquiries.view, enquiries.property_search.view
resources.view
commissions.view
mail_lists.view
```

### affiliate
```
contacts.view.dashboard
contacts.view, contacts.create, contacts.edit, contacts.delete
projects.view, projects.view.details
lots.view, lots.view.details
resources.view
commissions.view
tasks.view.own, tasks.create, tasks.edit, tasks.delete
mail_lists.view
users.view (subscribers), users.create (subscribers)
```

### property_manager
```
contacts.view.dashboard
users.view (developers only), users.create, users.edit, users.delete (developers only)
projects.view, projects.create, projects.edit, projects.delete, projects.view.details
lots.view, lots.create, lots.edit, lots.delete, lots.view.details
potential_properties.view, potential_properties.manage, potential_properties.map
tasks.view.own, tasks.create, tasks.edit, tasks.delete
enquiries.view, enquiries.property_search.view, enquiries.reservation.view, enquiries.finance.view
resources.view
mail_lists.view
```

---

## 6. Data Scoping Rules

| Role | Contact scope | Project scope | Task scope |
|---|---|---|---|
| superadmin | All orgs | All orgs | All orgs |
| piab_admin | All orgs | All orgs | All orgs |
| subscriber | Own org (org_id) | All projects (read), own org (write) | Own org |
| agent / sales_agent | Own org (org_id), own assigned (contacts.view.own) | All projects (read-only) | Own (tasks.view.own) |
| bdm | Own org (org_id) | All projects (read-only) | Own (tasks.view.own) |
| referral_partner | Own created (created_by) | — | Own |
| affiliate | Own org | Read-only | Own |
| property_manager | — | Own org (full write) | Own |

**Contact visibility rule**: All org-scoped roles see contacts where `contacts.organization_id = user.organization_id`. Superadmin and piab_admin see all. The `contacts.view.own` permission further restricts to `contacts.assigned_agent_id = auth()->id()` in the DataTable scope.

---

## 7. Implementation Notes for Chief

- **Seeder**: Create `database/seeders/CrmRolesPermissionsSeeder.php` in Step 0 using the above. Call it from `RolesAndPermissionsSeeder` or `EssentialSeeder`.
- **Filament**: Kit ships with `stephenjude/filament-feature-flags` + Spatie; roles and permissions are editable in Filament → Settings → Roles & Permissions at runtime. The seeder is default state only.
- **Policy classes**: Create a Policy per main CRM model (Contact, Project, Lot, Reservation, Sale, Task, Flyer, CampaignWebsite). Each policy checks the relevant permission using `$user->can('contacts.edit')` etc. Use `Gate::policy()` registration in `AuthServiceProvider`.
- **Org scoping**: Apply `OrganizationScope` global scope to all CRM models (Contact, Reservation, Sale, Task, etc.) that adds `WHERE organization_id = current_org_id` automatically. Superadmin and piab_admin bypass via `withoutGlobalScope`.
- **v3 import**: `fusion:import-contacts` maps v3 `is_partner=true` → `contact.type = partner`; all others → `contact.type = lead`. Users import retains their v3 role slug directly.
