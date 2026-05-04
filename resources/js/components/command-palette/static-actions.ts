import type { StaticAction } from './types';

export const STATIC_ACTIONS: StaticAction[] = [
    {
        id: 'go-dashboard',
        label: 'Go to Dashboard',
        keywords: ['home', 'overview'],
        href: '/dashboard',
    },
    {
        id: 'go-rakes',
        label: 'Go to Rakes',
        keywords: ['list', 'wagons'],
        href: '/rakes',
    },
    {
        id: 'go-indents',
        label: 'Go to Indents',
        keywords: ['orders', 'demand'],
        href: '/indents',
    },
    {
        id: 'go-alerts',
        label: 'Go to Alerts',
        keywords: ['notifications', 'overload'],
        href: '/alerts',
    },
];
