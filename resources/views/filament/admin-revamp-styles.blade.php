{{-- Admin panel UI revamp: align with main app sidebar (light, borders, active accent) --}}
<style>
    /* Sidebar panel: border and structure */
    .fi-sidebar.fi-main-sidebar {
        background: rgb(250 250 250) !important;
        border-right: 1px solid rgb(229 231 235);
    }
    .fi-sidebar.fi-main-sidebar.fi-sidebar-open {
        box-shadow: none;
        ring: none;
    }

    /* Header: bottom border, padding */
    .fi-sidebar-header {
        min-height: 4rem;
        padding-left: 1rem;
        padding-right: 1rem;
        justify-content: flex-start;
        border-bottom: 1px solid rgb(229 231 235);
    }
    .fi-sidebar-header-logo-ctn {
        margin-inline-start: 0.25rem;
    }

    /* Nav area: spacing and scroll */
    .fi-sidebar-nav {
        padding: 1rem 0.75rem 1.5rem;
        gap: 0.5rem;
    }
    .fi-sidebar-nav-groups {
        gap: 0.25rem;
        margin: 0 -0.25rem;
    }

    /* Group label: uppercase, smaller, muted */
    .fi-sidebar-group-btn {
        padding: 0.5rem 0.625rem;
        gap: 0.5rem;
    }
    .fi-sidebar-group-label {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: rgb(107 114 128);
    }
    .fi-sidebar-group-btn .fi-icon {
        color: rgb(107 114 128);
    }

    /* Nav items: rounded, padding, hover */
    .fi-sidebar-item-btn {
        padding: 0.5rem 0.75rem;
        gap: 0.75rem;
        border-radius: 0.5rem;
        justify-content: flex-start;
        min-height: 2.25rem;
    }
    .fi-sidebar-item:hover .fi-sidebar-item-btn {
        background: rgb(243 244 246);
    }
    .fi-sidebar-item-btn .fi-icon {
        color: rgb(107 114 128);
    }
    .fi-sidebar-item-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(55 65 81);
    }

    /* Active item: background + left accent bar (revamp style) */
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: rgb(243 244 246);
        box-shadow: inset 3px 0 0 0 rgb(71 85 105);
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon,
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label {
        color: rgb(51 65 85);
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-grouped-border > .fi-sidebar-item-grouped-border-part {
        background: rgb(71 85 105);
    }

    /* Sub-items (grouped) spacing */
    .fi-sidebar-group-items {
        gap: 0.125rem;
    }
    .fi-sidebar-sub-group-items {
        gap: 0.125rem;
    }

    /* Footer: top border */
    .fi-sidebar-footer {
        margin: 0.75rem 1rem 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgb(229 231 235);
    }

    /* Top bar: match app header (border, height) */
    .fi-topbar {
        min-height: 3.5rem;
        border-bottom: 1px solid rgb(229 231 235);
        background: rgb(255 255 255);
    }

    /* Main content: light background */
    .fi-main-ctn {
        background: rgb(248 250 252);
    }
    .fi-main {
        background: transparent;
    }

    /* Tables: same card style as main app (rounded, border, header) */
    .fi-ta-ctn {
        border-radius: 0.5rem;
        border: 1px solid rgb(229 231 235);
        background: rgb(255 255 255);
        box-shadow: none;
        ring: none;
    }
    .fi-ta-table > thead > tr {
        background: rgb(249 250 251);
    }
    .fi-ta-table > thead > tr > .fi-ta-header-cell {
        color: rgb(55 65 81);
    }
    .fi-ta-table {
        border-color: rgb(229 231 235);
    }
    .fi-ta-table > tbody > tr:hover {
        background: rgb(249 250 251);
    }
    .fi-ta-header,
    .fi-ta-header-toolbar,
    .fi-ta-filters-above-content-ctn,
    .fi-ta-filter-indicators,
    .fi-ta-selection-indicator,
    .fi-ta-reorder-indicator,
    .fi-pagination {
        border-color: rgb(229 231 235);
    }

    /* Widget/section cards on dashboard and list pages */
    .fi-section-content-ctn,
    .fi-wi-stats-overview-stat,
    .fi-wi-chart {
        border-radius: 0.5rem;
        border: 1px solid rgb(229 231 235);
        background: rgb(255 255 255);
    }
    .fi-section-header {
        border-color: rgb(229 231 235);
    }

    /* Dashboard: widget cards same as fusion-card, with subtle elevation */
    .fi-wi-chart,
    .fi-wi-stats-overview-stat,
    .fi-wi-stats-overview {
        border-radius: 0.5rem;
        border: 1px solid rgb(229 231 235);
        background: rgb(255 255 255);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.03);
    }
    .fi-wi-stats-overview-stat {
        padding: 1.1rem 1.4rem;
        gap: 0.5rem;
    }

    /* Dashboard heading + grid spacing */
    .fi-main-header-heading {
        font-size: 1.35rem;
        font-weight: 600;
        letter-spacing: -0.02em;
        color: rgb(15 23 42);
    }
    .fi-main-ctn .fi-wi-stats-overview,
    .fi-main-ctn .fi-wi-chart {
        margin-top: 0.5rem;
    }
</style>
