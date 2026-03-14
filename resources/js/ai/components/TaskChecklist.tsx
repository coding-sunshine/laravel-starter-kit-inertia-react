/**
 * C1 TaskChecklist component — renders an interactive task checklist with priority badges,
 * due date indicators, and action buttons.
 */

import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { TaskChecklistProps, TaskChecklistItem, C1Action } from '../c1-types';

function priorityVariant(priority: string): string {
    const map: Record<string, string> = {
        urgent: 'bg-red-100 text-red-700',
        high: 'bg-orange-100 text-orange-700',
        medium: 'bg-amber-100 text-amber-700',
        low: 'bg-gray-100 text-gray-600',
    };
    return map[priority] ?? 'bg-muted text-muted-foreground';
}

function typeIcon(type: string): string {
    const map: Record<string, string> = {
        call: '📞',
        email: '✉️',
        meeting: '📅',
        follow_up: '🔄',
        other: '📝',
    };
    return map[type] ?? '📝';
}

function dueDateVariant(iso?: string, isCompleted = false): string {
    if (isCompleted || !iso) return 'text-muted-foreground';
    const msUntil = new Date(iso).getTime() - Date.now();
    if (msUntil < 0) return 'text-red-600 font-medium'; // overdue
    if (msUntil < 86_400_000) return 'text-amber-600'; // due today
    return 'text-muted-foreground';
}

function formatDue(iso?: string): string {
    if (!iso) return '';
    const d = new Date(iso);
    const today = new Date();
    const diff = Math.floor((d.getTime() - today.setHours(0, 0, 0, 0)) / 86_400_000);
    if (diff < -1) return `${Math.abs(diff)}d overdue`;
    if (diff === -1) return 'Yesterday';
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Tomorrow';
    return d.toLocaleDateString('en-AU', { day: 'numeric', month: 'short' });
}

function TaskItem({ task, showContact }: { task: TaskChecklistItem; showContact: boolean }) {
    return (
        <div className={cn('flex items-start gap-3 rounded-md border border-border p-3', task.is_completed && 'opacity-60')}>
            {/* Checkbox indicator */}
            <div className={cn('mt-0.5 h-4 w-4 flex-shrink-0 rounded border border-border', task.is_completed && 'bg-primary')}>
                {task.is_completed && <span className="block text-[10px] text-primary-foreground">✓</span>}
            </div>

            <div className="min-w-0 flex-1 space-y-1">
                {/* Title row */}
                <div className="flex flex-wrap items-center gap-2">
                    <span className={cn('text-sm', task.is_completed && 'line-through')}>{task.title}</span>
                    <span className="text-xs">{typeIcon(task.type)}</span>
                    <span className={cn('rounded-full px-1.5 py-0.5 text-xs', priorityVariant(task.priority))}>
                        {task.priority}
                    </span>
                </div>

                {/* Meta row */}
                <div className="flex flex-wrap gap-x-3 text-xs">
                    {task.due_at && (
                        <span className={dueDateVariant(task.due_at, task.is_completed)}>
                            {formatDue(task.due_at)}
                        </span>
                    )}
                    {task.assigned_to && (
                        <span className="text-muted-foreground">{task.assigned_to.name}</span>
                    )}
                    {showContact && task.contact && (
                        <Link href={task.contact.href} className="text-primary hover:underline">
                            {task.contact.name}
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
}

function ListAction({ action }: { action: C1Action }) {
    if (action.type === 'link' && action.href) {
        return (
            <Link
                href={action.href}
                className="inline-flex items-center rounded border border-border bg-background px-2 py-1 text-xs font-medium hover:bg-muted"
            >
                {action.label}
            </Link>
        );
    }
    return (
        <button
            type="button"
            className="inline-flex items-center rounded border border-border bg-background px-2 py-1 text-xs font-medium hover:bg-muted"
        >
            {action.label}
        </button>
    );
}

export function TaskChecklist({ title, tasks, show_contact = false, actions = [] }: TaskChecklistProps) {
    return (
        <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-sm">{title}</h3>
                <span className="text-xs text-muted-foreground">{tasks.length} tasks</span>
            </div>

            {tasks.length === 0 ? (
                <p className="text-sm text-muted-foreground">No tasks found.</p>
            ) : (
                <div className="space-y-2">
                    {tasks.map((task) => (
                        <TaskItem key={task.id} task={task} showContact={show_contact} />
                    ))}
                </div>
            )}

            {actions.length > 0 && (
                <div className="mt-3 flex gap-2 border-t border-border pt-3">
                    {actions.map((action, i) => (
                        <ListAction key={i} action={action} />
                    ))}
                </div>
            )}
        </div>
    );
}
