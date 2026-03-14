/**
 * Thesys C1 CRM component registry.
 *
 * Registers custom React components that Thesys C1 will render based on
 * agent tool responses. Each component name must match the `component` key
 * returned by MCP tools (e.g. ContactSearchTool returns { component: 'ContactCard' }).
 *
 * NOTE: @thesys/client npm package is not yet publicly available (registry 404).
 * These components implement the C1 interface spec and are registered via
 * the local C1Renderer wrapper. When @thesys/client becomes available,
 * replace the local renderer with the official SDK.
 */

import React from 'react';
import { ContactCard } from './components/ContactCard';
import { CommissionTable } from './components/CommissionTable';
import { EmailCompose } from './components/EmailCompose';
import { PipelineFunnel } from './components/PipelineFunnel';
import { PropertyCard } from './components/PropertyCard';
import { TaskChecklist } from './components/TaskChecklist';
import type { C1ComponentName, C1Response } from './c1-types';

/** Registry of C1 CRM components keyed by component name */
const C1_REGISTRY: Record<C1ComponentName, React.ComponentType<Record<string, unknown>>> = {
    ContactCard:    ContactCard as React.ComponentType<Record<string, unknown>>,
    PropertyCard:   PropertyCard as React.ComponentType<Record<string, unknown>>,
    PipelineFunnel: PipelineFunnel as React.ComponentType<Record<string, unknown>>,
    EmailCompose:   EmailCompose as React.ComponentType<Record<string, unknown>>,
    CommissionTable: CommissionTable as React.ComponentType<Record<string, unknown>>,
    TaskChecklist:  TaskChecklist as React.ComponentType<Record<string, unknown>>,
};

/** Render a C1 response — selects the right component from the registry */
export function C1Renderer({ response }: { response: C1Response }) {
    const Component = C1_REGISTRY[response.component];

    if (!Component) {
        return (
            <div className="rounded border border-destructive/20 bg-destructive/5 p-3 text-sm text-destructive">
                Unknown C1 component: {response.component}
            </div>
        );
    }

    // Multiple-item response (e.g. ContactCard list)
    if (response.multiple && Array.isArray(response.items)) {
        return (
            <div className="space-y-3">
                {response.items.map((item, i) => (
                    <Component key={i} {...item} />
                ))}
            </div>
        );
    }

    // Single-item response
    return <Component {...(response.props ?? {})} />;
}

export { C1_REGISTRY };
