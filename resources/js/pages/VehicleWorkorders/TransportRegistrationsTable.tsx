import { Link } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Columns2, Pencil, Trash2 } from 'lucide-react';
import { type ReactNode, useCallback, useMemo, useState } from 'react';
import { cn } from '@/lib/utils';

export interface TransportRegistrationRow {
    id: number;
    siding_id: number | null;
    work_order_no_1: string | null;
    work_order_no_2: string | null;
    reference_no: string | null;
    work_order_date: string | null;
    transporter_name: string | null;
    trade_name: string | null;
    legal_name_of_business: string | null;
    pan_card: string | null;
    gst_no: string | null;
    status: string | null;
    email: string | null;
    vendor_code: string | null;
    mobile_1: string | null;
    mobile_2: string | null;
    address: string | null;
    gramin_or_non_gramin: string | null;
    /** When omitted or null, treated as active. */
    is_active?: boolean | null;
    created_at: string | null;
    updated_at: string | null;
    siding?: { id: number; name: string; code: string } | null;
    /** Matches {@link VehicleWorkorder}: trimmed transporter + wo_no equals reg WO1 or WO2. */
    assigned_vehicle_workorders_count?: number;
}

export interface PaginatedTransportRegistrations {
    data: TransportRegistrationRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

/** Bumped when default visibility or columns change so old localStorage picks up fresh defaults. */
const COLUMN_STORAGE_KEY =
    'vehicle-workorders-transporters-column-visibility-v3';

const COLUMN_DEFS: readonly {
    id: string;
    label: string;
    headerClass?: string;
    cellClass?: string;
    /** Keep row height stable; full value on hover via `title`. */
    cellClamp?: 'truncate' | 'line-clamp-2' | 'line-clamp-3';
}[] = [
    {
        id: 'siding',
        label: 'Siding',
        headerClass:
            'w-[1%] max-w-[min(28vw,16rem)] min-w-0 whitespace-nowrap',
        cellClass:
            'w-[1%] max-w-[min(28vw,16rem)] min-w-0 whitespace-nowrap',
        cellClamp: 'truncate',
    },
    {
        id: 'assigned_vehicle_workorders_count',
        label: 'Assigned vehicles',
        headerClass:
            'w-[min(10rem,26vw)] min-w-[5.5rem] whitespace-nowrap text-center',
        cellClass:
            'w-[min(10rem,26vw)] min-w-[5.5rem] whitespace-nowrap text-center',
    },
    { id: 'work_order_no_1', label: 'WO no 1', cellClass: 'whitespace-nowrap' },
    { id: 'work_order_no_2', label: 'WO no 2', cellClass: 'whitespace-nowrap' },
    { id: 'reference_no', label: 'Reference no', cellClass: 'whitespace-nowrap' },
    {
        id: 'work_order_date',
        label: 'Work order date',
        cellClass: 'whitespace-nowrap',
    },
    {
        id: 'transporter_name',
        label: 'Transporter',
        headerClass: 'max-w-[200px] whitespace-normal',
        cellClass: 'max-w-[200px] min-w-0 align-middle text-sm',
    },
    {
        id: 'trade_name',
        label: 'Trade name',
        headerClass: 'max-w-[200px] whitespace-normal',
        cellClass: 'max-w-[200px] min-w-0 align-middle text-sm',
        cellClamp: 'line-clamp-2',
    },
    {
        id: 'legal_name_of_business',
        label: 'Legal name',
        headerClass: 'max-w-[220px] whitespace-normal',
        cellClass: 'max-w-[220px] min-w-0 align-middle text-sm',
        cellClamp: 'line-clamp-2',
    },
    { id: 'pan_card', label: 'PAN', cellClass: 'whitespace-nowrap' },
    { id: 'gst_no', label: 'GST no', cellClass: 'whitespace-nowrap' },
    { id: 'status', label: 'Status', cellClass: 'whitespace-nowrap' },
    {
        id: 'is_active',
        label: 'Active',
        headerClass: 'whitespace-nowrap',
        cellClass: 'whitespace-nowrap',
    },
    {
        id: 'email',
        label: 'Email',
        headerClass: 'max-w-[200px] whitespace-normal',
        cellClass: 'max-w-[220px] min-w-0 align-middle text-sm',
        cellClamp: 'truncate',
    },
    {
        id: 'vendor_code',
        label: 'Vendor code',
        cellClass: 'whitespace-nowrap',
    },
    { id: 'mobile_1', label: 'Mobile 1', cellClass: 'whitespace-nowrap' },
    { id: 'mobile_2', label: 'Mobile 2', cellClass: 'whitespace-nowrap' },
    {
        id: 'address',
        label: 'Address',
        headerClass: 'max-w-[240px] whitespace-normal',
        cellClass: 'max-w-[240px] min-w-[10rem] align-middle text-sm',
        cellClamp: 'line-clamp-3',
    },
    {
        id: 'gramin_or_non_gramin',
        label: 'Gramin / non-gramin',
        cellClass: 'whitespace-nowrap',
    },
    {
        id: 'created_at',
        label: 'Created',
        headerClass: 'whitespace-nowrap',
        cellClass: 'whitespace-nowrap text-muted-foreground',
    },
    {
        id: 'updated_at',
        label: 'Updated',
        headerClass: 'whitespace-nowrap',
        cellClass: 'whitespace-nowrap text-muted-foreground',
    },
];

function columnIds(): string[] {
    return COLUMN_DEFS.map((c) => c.id);
}

/** Shown until the user changes the column picker (or restores “Show all”). */
const DEFAULT_VISIBLE_COLUMN_IDS: readonly string[] = [
    'siding',
    'assigned_vehicle_workorders_count',
    'work_order_no_2',
    'transporter_name',
    'pan_card',
    'gst_no',
    'is_active',
];

function defaultVisibility(): Record<string, boolean> {
    const visible = new Set(DEFAULT_VISIBLE_COLUMN_IDS);

    return Object.fromEntries(
        columnIds().map((id) => [id, visible.has(id)]),
    );
}

function allColumnsVisibility(): Record<string, boolean> {
    return Object.fromEntries(columnIds().map((id) => [id, true]));
}

function loadStoredVisibility(): Record<string, boolean> {
    const defaults = defaultVisibility();
    if (typeof window === 'undefined') {
        return defaults;
    }
    try {
        const raw = window.localStorage.getItem(COLUMN_STORAGE_KEY);
        if (!raw) {
            return defaults;
        }
        const parsed = JSON.parse(raw) as Record<string, unknown>;
        const merged = { ...defaults };
        for (const id of columnIds()) {
            if (typeof parsed[id] === 'boolean') {
                merged[id] = parsed[id];
            }
        }

        return merged;
    } catch {
        return defaults;
    }
}

function formatDate(dateStr: string | null): string {
    if (! dateStr) {
        return '-';
    }
    try {
        return new Date(dateStr).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
}

function formatDateTime(dateStr: string | null): string {
    if (! dateStr) {
        return '-';
    }
    try {
        return new Date(dateStr).toLocaleString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
}

function formatGraminOrNonGramin(value: string | null | undefined): string {
    if (value === 'gramin') {
        return 'Gramin';
    }
    if (value === 'non_gramin') {
        return 'Non-Gramin';
    }

    return value?.trim() ? value : '-';
}

function dash(value: string | null | undefined): string {
    if (value === null || value === undefined) {
        return '-';
    }
    const s = String(value).trim();

    return s === '' ? '-' : s;
}

/** Raw trimmed text for native tooltip on clamped cells (no placeholder dash). */
function plainTextTooltip(
    row: TransportRegistrationRow,
    columnId: string,
): string | undefined {
    let value: string | null | undefined;
    switch (columnId) {
        case 'siding':
            return row.siding
                ? `${row.siding.name} (${row.siding.code})`
                : undefined;
        case 'transporter_name':
            value = row.transporter_name;
            break;
        case 'trade_name':
            value = row.trade_name;
            break;
        case 'legal_name_of_business':
            value = row.legal_name_of_business;
            break;
        case 'email':
            value = row.email;
            break;
        case 'address':
            value = row.address;
            break;
        default:
            return undefined;
    }
    const trimmed = typeof value === 'string' ? value.trim() : '';

    return trimmed === '' ? undefined : trimmed;
}

function clippedCellWrap(
    row: TransportRegistrationRow,
    column: {
        id: string;
        cellClamp?: 'truncate' | 'line-clamp-2' | 'line-clamp-3';
    },
    content: ReactNode,
): ReactNode {
    const clamp = column.cellClamp;
    if (!clamp) {
        return <div className="min-w-0">{content}</div>;
    }

    const tip = plainTextTooltip(row, column.id);
    const clampClass =
        clamp === 'truncate'
            ? 'block truncate'
            : clamp === 'line-clamp-2'
              ? 'line-clamp-2 break-words [overflow-wrap:anywhere]'
              : 'line-clamp-3 break-words [overflow-wrap:anywhere]';

    return (
        <div className={cn('min-w-0', clampClass)} title={tip}>
            {content}
        </div>
    );
}

function renderCell(
    row: TransportRegistrationRow,
    columnId: string,
): ReactNode {
    switch (columnId) {
        case 'siding':
            return row.siding
                ? `${row.siding.name} (${row.siding.code})`
                : '-';
        case 'assigned_vehicle_workorders_count':
            return (
                <span className="flex w-full justify-center tabular-nums font-medium">
                    <span>{row.assigned_vehicle_workorders_count ?? 0}</span>
                </span>
            );
        case 'work_order_no_1':
            return dash(row.work_order_no_1);
        case 'work_order_no_2':
            return dash(row.work_order_no_2);
        case 'reference_no':
            return dash(row.reference_no);
        case 'work_order_date':
            return formatDate(row.work_order_date);
        case 'transporter_name':
            return dash(row.transporter_name);
        case 'trade_name':
            return dash(row.trade_name);
        case 'legal_name_of_business':
            return dash(row.legal_name_of_business);
        case 'pan_card':
            return dash(row.pan_card);
        case 'gst_no':
            return dash(row.gst_no);
        case 'status':
            return dash(row.status);
        case 'is_active':
            return row.is_active === false ? (
                <span className="rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                    Inactive
                </span>
            ) : (
                <span className="rounded-md bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-200">
                    Active
                </span>
            );
        case 'email':
            return dash(row.email);
        case 'vendor_code':
            return dash(row.vendor_code);
        case 'mobile_1':
            return dash(row.mobile_1);
        case 'mobile_2':
            return dash(row.mobile_2);
        case 'address':
            return dash(row.address);
        case 'gramin_or_non_gramin':
            return formatGraminOrNonGramin(row.gramin_or_non_gramin);
        case 'created_at':
            return formatDateTime(row.created_at);
        case 'updated_at':
            return formatDateTime(row.updated_at);
        default:
            return '-';
    }
}

interface TransportRegistrationPermissions {
    canCreate: boolean;
    canUpdate: boolean;
    canDelete: boolean;
}

interface TransportRegistrationsTableProps {
    transportWorkOrderRegistrations: PaginatedTransportRegistrations;
    transportRegistrationPermissions: TransportRegistrationPermissions;
    onDeleteRegistration: (id: number) => void;
}

export default function TransportRegistrationsTable({
    transportWorkOrderRegistrations,
    transportRegistrationPermissions,
    onDeleteRegistration,
}: TransportRegistrationsTableProps): ReactNode {
    const [columnsOpen, setColumnsOpen] = useState(false);

    const [visibility, setVisibility] = useState<Record<string, boolean>>(
        () => loadStoredVisibility(),
    );
    const persistVisibility = useCallback((next: Record<string, boolean>) => {
        setVisibility(next);
        if (typeof window !== 'undefined') {
            try {
                window.localStorage.setItem(
                    COLUMN_STORAGE_KEY,
                    JSON.stringify(next),
                );
            } catch {
                //
            }
        }
    }, []);

    const toggleColumnVisibility = useCallback(
        (id: string, checked: boolean) => {
            persistVisibility({ ...visibility, [id]: checked });
        },
        [visibility, persistVisibility],
    );

    const showAllColumns = useCallback(() => {
        persistVisibility(allColumnsVisibility());
    }, [persistVisibility]);

    const visibleDefs = useMemo(() => {
        return COLUMN_DEFS.filter((c) => visibility[c.id] !== false);
    }, [visibility]);

    const hasRows = transportWorkOrderRegistrations.data.length > 0;

    const totalRecords = transportWorkOrderRegistrations.total;
    const recordsLabel =
        totalRecords !== 1 ? 'records' : 'record';

    return (
        <Card data-pan="vehicle-workorders-transport-registrations-table">
            <CardHeader className="flex flex-col gap-4 space-y-0 sm:flex-row sm:items-start sm:justify-between">
                <div className="space-y-1.5">
                    <CardTitle>Transporters</CardTitle>
                    <CardDescription>
                        {totalRecords} {recordsLabel} from transporter
                        registrations
                    </CardDescription>
                </div>
                <Popover open={columnsOpen} onOpenChange={setColumnsOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            variant="outline"
                            size="sm"
                            className="shrink-0 gap-2"
                            type="button"
                            data-pan="vehicle-workorders-transporters-column-picker"
                        >
                            <Columns2 className="size-4" />
                            Columns
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent align="end" className="w-72 p-3">
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <span className="text-sm font-medium">
                                Visible columns
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-auto py-1 text-xs"
                                onClick={() => showAllColumns()}
                            >
                                Show all
                            </Button>
                        </div>
                        <div className="flex max-h-72 flex-col gap-2 overflow-y-auto pr-1">
                            {COLUMN_DEFS.map((col) => {
                                const cid = `transporter-col-${col.id}`;

                                return (
                                    <label
                                        htmlFor={cid}
                                        key={col.id}
                                        className="flex cursor-pointer items-center gap-2 text-sm leading-none"
                                    >
                                        <Checkbox
                                            id={cid}
                                            checked={
                                                visibility[col.id] !== false
                                            }
                                            onCheckedChange={(v) =>
                                                toggleColumnVisibility(
                                                    col.id,
                                                    v === true,
                                                )
                                            }
                                        />
                                        <span>{col.label}</span>
                                    </label>
                                );
                            })}
                        </div>
                        <p className="text-muted-foreground mt-3 text-xs">
                            Actions stay visible. Column choices are saved in this
                            browser.
                        </p>
                    </PopoverContent>
                </Popover>
            </CardHeader>
            <CardContent>
                {hasRows ? (
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {visibleDefs.map((col) => (
                                        <TableHead
                                            key={col.id}
                                            className={col.headerClass}
                                        >
                                            {col.label}
                                        </TableHead>
                                    ))}
                                    <TableHead className="w-[88px] whitespace-nowrap text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {transportWorkOrderRegistrations.data.map(
                                    (row) => (
                                        <TableRow key={row.id}>
                                            {visibleDefs.map((col) => (
                                                <TableCell
                                                    key={col.id}
                                                    className={`align-middle text-sm ${col.cellClass ?? ''}`}
                                                >
                                                    {clippedCellWrap(
                                                        row,
                                                        col,
                                                        renderCell(
                                                            row,
                                                            col.id,
                                                        ),
                                                    )}
                                                </TableCell>
                                            ))}
                                            <TableCell className="text-right whitespace-nowrap align-middle">
                                                <div className="flex flex-wrap justify-end gap-1">
                                                    {transportRegistrationPermissions.canUpdate ? (
                                                        <Button
                                                            variant="outline"
                                                            size="icon-sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/vehicle-workorders/transport-registrations/${row.id}/edit`}
                                                                aria-label="Edit transporter registration"
                                                                title="Edit"
                                                                data-pan="vehicle-workorders-transport-registrations-edit"
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {transportRegistrationPermissions.canDelete ? (
                                                        <Button
                                                            variant="outline"
                                                            size="icon-sm"
                                                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                            data-pan="vehicle-workorders-transport-registrations-delete"
                                                            type="button"
                                                            aria-label="Delete transporter registration"
                                                            title="Delete"
                                                            onClick={() =>
                                                                onDeleteRegistration(
                                                                    row.id,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    ) : null}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ),
                                )}
                            </TableBody>
                        </Table>
                        {transportWorkOrderRegistrations.last_page > 1 ? (
                            <div className="mt-4 flex flex-wrap gap-2">
                                {transportWorkOrderRegistrations.links.map(
                                    (link, index) => (
                                        <Link
                                            key={`${link.url ?? 'null'}-${link.label}-tr-${index}`}
                                            href={link.url ?? '#'}
                                            className={
                                                link.active
                                                    ? 'rounded border bg-muted px-2 py-1 text-sm font-medium'
                                                    : 'rounded border px-2 py-1 text-sm'
                                            }
                                        >
                                            {link.label}
                                        </Link>
                                    ),
                                )}
                            </div>
                        ) : null}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed p-8 text-center">
                        <p className="text-sm text-muted-foreground">
                            No transporter registrations yet.{` `}
                            {transportRegistrationPermissions.canCreate ? (
                                <>
                                    Use <span className="font-medium">New transporter</span> to add
                                    one.
                                </>
                            ) : null}
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
