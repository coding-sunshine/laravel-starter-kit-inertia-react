/**
 * Thesys C1 generative UI — TypeScript interfaces for custom CRM components.
 * Based on spec in rebuild-plan/00-thesys-conversational-ai.md
 *
 * NOTE: @thesys/client SDK is not available on npm. These types define the
 * component interfaces for local C1-compatible rendering.
 */

export interface C1Action {
    label: string;
    type: 'link' | 'action' | 'submit' | 'edit' | 'dismiss' | 'complete' | 'assign' | 'snooze';
    href?: string;
    action?: string;
    payload?: Record<string, unknown>;
}

// ─── ContactCard ──────────────────────────────────────────────────────────────

export interface ContactCardProps {
    id: number;
    full_name: string;
    email?: string;
    phone?: string;
    suburb?: string;
    state?: string;
    stage: string;
    lead_score: number;
    last_contacted_at?: string;
    assigned_agent?: { id: number; name: string; avatar_url?: string };
    tags?: string[];
    actions?: C1Action[];
}

// ─── PropertyCard ─────────────────────────────────────────────────────────────

export interface PropertyCardProps {
    id: number;
    type: 'project' | 'lot';
    title: string;
    suburb?: string;
    state?: string;
    stage?: string;
    title_status?: 'available' | 'reserved' | 'sold';
    photo_url?: string;
    min_price?: number;
    price?: number;
    bedrooms?: number;
    bathrooms?: number;
    car?: number;
    internal_m2?: number;
    total_m2?: number;
    project_title?: string;
    is_hot_property?: boolean;
    available_lots_count?: number;
    actions?: C1Action[];
}

// ─── PipelineFunnel ───────────────────────────────────────────────────────────

export interface PipelineFunnelStage {
    name: string;
    count: number;
    value?: number;
    color?: string;
}

export interface PipelineFunnelProps {
    title: string;
    stages: PipelineFunnelStage[];
    total_count: number;
    total_value?: number;
    currency?: string;
}

// ─── EmailCompose ─────────────────────────────────────────────────────────────

export interface EmailComposeProps {
    to: { name: string; email: string };
    from?: { name: string; email: string };
    subject: string;
    body: string;
    contact_id?: number;
    thread_id?: string;
    actions: C1Action[];
}

// ─── CommissionTable ──────────────────────────────────────────────────────────

export type CommissionType =
    | 'piab'
    | 'subscriber'
    | 'affiliate'
    | 'sales_agent'
    | 'referral_partner'
    | 'bdm'
    | 'sub_agent';

export interface CommissionTableRow {
    commission_type: CommissionType;
    agent_name?: string;
    rate_percentage?: number;
    amount: number;
    override_amount: boolean;
}

export interface CommissionTableProps {
    sale_id: number;
    lot_title: string;
    project_title: string;
    sale_price: number;
    rows: CommissionTableRow[];
    total_commission: number;
    currency?: string;
}

// ─── TaskChecklist ────────────────────────────────────────────────────────────

export interface TaskChecklistItem {
    id: number;
    title: string;
    due_at?: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    type: 'call' | 'email' | 'meeting' | 'follow_up' | 'other';
    is_completed: boolean;
    assigned_to?: { id: number; name: string; avatar_url?: string };
    contact?: { id: number; name: string; href: string };
    actions: C1Action[];
}

export interface TaskChecklistProps {
    title: string;
    tasks: TaskChecklistItem[];
    show_contact?: boolean;
    actions?: C1Action[];
}

// ─── C1 Component Registry ────────────────────────────────────────────────────

export type C1ComponentName =
    | 'ContactCard'
    | 'PropertyCard'
    | 'PipelineFunnel'
    | 'EmailCompose'
    | 'CommissionTable'
    | 'TaskChecklist';

export interface C1Response {
    component: C1ComponentName;
    props?: Record<string, unknown>;
    multiple?: boolean;
    count?: number;
    items?: Record<string, unknown>[];
}
