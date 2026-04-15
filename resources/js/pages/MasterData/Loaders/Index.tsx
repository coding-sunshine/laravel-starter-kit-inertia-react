import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { UserCog, Warehouse } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Loader {
    id: number;
    loader_name: string;
    code: string;
    loader_type: string;
    make_model: string | null;
    capacity_mt: number | null;
    last_calibration_date: string | null;
    is_active: boolean;
    siding: {
        id: number;
        name: string;
        code: string;
    };
    created_at: string;
    updated_at: string;
}

interface LoaderOperatorRow {
    id: number;
    name: string;
    is_active: boolean;
    siding_id: number | null;
    siding: { id: number; name: string; code: string } | null;
    created_at: string;
    updated_at: string;
}

interface SidingOption {
    id: number;
    name: string;
    code: string;
}

interface LoaderOperatorPayload {
    name: string;
    is_active: boolean;
    siding_id: number | null;
}

type MasterTab = 'loaders' | 'operators';

interface Props {
    loaders: Loader[];
    loaderOperators: LoaderOperatorRow[];
    sidings: SidingOption[];
    tab: MasterTab;
}

export default function Index({ loaders, loaderOperators, sidings, tab: initialTab }: Props) {
    const page = usePage<{ errors: Record<string, string> }>();
    const pageErrors = page.props.errors ?? {};

    const [activeTab, setActiveTab] = useState<MasterTab>(initialTab);
    const [operatorDialogOpen, setOperatorDialogOpen] = useState(false);
    const [editingOperator, setEditingOperator] = useState<LoaderOperatorRow | null>(null);
    const [opName, setOpName] = useState('');
    const [opActive, setOpActive] = useState(true);
    const [opSidingId, setOpSidingId] = useState<string>('');
    const [opSubmitting, setOpSubmitting] = useState(false);
    const [duplicateConfirmOpen, setDuplicateConfirmOpen] = useState(false);
    const pendingOperatorPayloadRef = useRef<LoaderOperatorPayload | null>(null);

    useEffect(() => {
        setActiveTab(initialTab);
    }, [initialTab]);

    function syncTabToUrl(next: MasterTab): void {
        setActiveTab(next);
        const params = next === 'operators' ? { tab: 'operators' as const } : {};
        router.get('/master-data/loaders', params, { preserveState: true, preserveScroll: true, replace: true });
    }

    function openCreateOperator(): void {
        setEditingOperator(null);
        setOpName('');
        setOpActive(true);
        setOpSidingId('');
        setOperatorDialogOpen(true);
    }

    function openEditOperator(op: LoaderOperatorRow): void {
        setEditingOperator(op);
        setOpName(op.name);
        setOpActive(op.is_active);
        setOpSidingId(op.siding_id != null ? String(op.siding_id) : '');
        setOperatorDialogOpen(true);
    }

    function normalizedOperatorName(value: string): string {
        return value.trim().toLowerCase();
    }

    function operatorNameCollides(trimmedName: string, excludeOperatorId: number | null): boolean {
        const key = normalizedOperatorName(trimmedName);
        if (key === '') {
            return false;
        }
        return loaderOperators.some((op) => {
            if (excludeOperatorId !== null && op.id === excludeOperatorId) {
                return false;
            }
            return normalizedOperatorName(op.name) === key;
        });
    }

    function commitOperator(payload: LoaderOperatorPayload): void {
        setOpSubmitting(true);
        const done = (): void => setOpSubmitting(false);

        if (editingOperator) {
            router.put(`/master-data/loader-operators/${editingOperator.id}`, payload, {
                preserveScroll: true,
                onFinish: done,
                onSuccess: () => {
                    setOperatorDialogOpen(false);
                    setEditingOperator(null);
                },
            });
        } else {
            router.post('/master-data/loader-operators', payload, {
                preserveScroll: true,
                onFinish: done,
                onSuccess: () => {
                    setOperatorDialogOpen(false);
                },
            });
        }
    }

    function submitOperator(e: React.FormEvent): void {
        e.preventDefault();
        const sidingId = opSidingId === '' || opSidingId === '__none__' ? null : Number(opSidingId);
        const payload: LoaderOperatorPayload = {
            name: opName.trim(),
            is_active: opActive,
            siding_id: sidingId,
        };

        const excludeId = editingOperator?.id ?? null;
        if (
            editingOperator &&
            normalizedOperatorName(payload.name) === normalizedOperatorName(editingOperator.name)
        ) {
            commitOperator(payload);
            return;
        }

        if (operatorNameCollides(payload.name, excludeId)) {
            pendingOperatorPayloadRef.current = payload;
            setDuplicateConfirmOpen(true);
            return;
        }

        commitOperator(payload);
    }

    function confirmDuplicateOperator(): void {
        const payload = pendingOperatorPayloadRef.current;
        pendingOperatorPayloadRef.current = null;
        setDuplicateConfirmOpen(false);
        if (payload) {
            commitOperator(payload);
        }
    }

    return (
        <AppLayout>
            <Head title="Loaders" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold">Loaders</h1>
                        <p className="text-muted-foreground">Manage loaders and loader operator names</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {activeTab === 'loaders' && (
                            <Link href="/master-data/loaders/create">
                                <Button data-pan="master-data-add-loader">Add Loader</Button>
                            </Link>
                        )}
                        {activeTab === 'operators' && (
                            <Button type="button" onClick={openCreateOperator} data-pan="master-data-add-loader-operator">
                                Add operator
                            </Button>
                        )}
                    </div>
                </div>

                <ToggleGroup
                    type="single"
                    value={activeTab}
                    onValueChange={(value) => {
                        if (value) {
                            syncTabToUrl(value as MasterTab);
                        }
                    }}
                    className={cn('inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800')}
                >
                    <ToggleGroupItem
                        value="loaders"
                        aria-label="Loaders"
                        data-pan="master-data-loaders-tab-loaders"
                        className={cn(
                            'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                            activeTab === 'loaders'
                                ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                        )}
                    >
                        <Warehouse className="-ml-1 h-4 w-4" />
                        <span className="ml-1.5 text-sm">Loaders</span>
                    </ToggleGroupItem>
                    <ToggleGroupItem
                        value="operators"
                        aria-label="Loader operators"
                        data-pan="master-data-loaders-tab-operators"
                        className={cn(
                            'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                            activeTab === 'operators'
                                ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                        )}
                    >
                        <UserCog className="-ml-1 h-4 w-4" />
                        <span className="ml-1.5 text-sm">Loader operators</span>
                    </ToggleGroupItem>
                </ToggleGroup>

                {activeTab === 'loaders' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>All Loaders</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Capacity (MT)</TableHead>
                                        <TableHead>Siding</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {loaders.map((loader) => (
                                        <TableRow key={loader.id}>
                                            <TableCell className="font-medium">{loader.loader_name}</TableCell>
                                            <TableCell>{loader.code}</TableCell>
                                            <TableCell>{loader.loader_type}</TableCell>
                                            <TableCell>{loader.capacity_mt || '-'}</TableCell>
                                            <TableCell>{loader.siding.name}</TableCell>
                                            <TableCell>
                                                <Badge variant={loader.is_active ? 'default' : 'secondary'}>
                                                    {loader.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex space-x-2">
                                                    <Link href={`/master-data/loaders/${loader.id}`}>
                                                        <Button variant="outline" size="sm">
                                                            View
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/master-data/loaders/${loader.id}/edit`}>
                                                        <Button variant="outline" size="sm">
                                                            Edit
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {activeTab === 'operators' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Loader operators</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Siding (optional)</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {loaderOperators.map((op) => (
                                        <TableRow key={op.id}>
                                            <TableCell className="font-medium">{op.name}</TableCell>
                                            <TableCell>{op.siding ? op.siding.name : '—'}</TableCell>
                                            <TableCell>
                                                <Badge variant={op.is_active ? 'default' : 'secondary'}>
                                                    {op.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Button variant="outline" size="sm" type="button" onClick={() => openEditOperator(op)}>
                                                    Edit
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                <Dialog open={operatorDialogOpen} onOpenChange={setOperatorDialogOpen}>
                    <DialogContent className="sm:max-w-md">
                        <form onSubmit={submitOperator}>
                            <DialogHeader>
                                <DialogTitle>{editingOperator ? 'Edit loader operator' : 'Add loader operator'}</DialogTitle>
                                <DialogDescription>
                                    Operator name is stored on wagon loading records as text. Deactivate to hide from pick lists.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="loader-op-name">Name</Label>
                                    <Input
                                        id="loader-op-name"
                                        value={opName}
                                        onChange={(e) => setOpName(e.target.value)}
                                        required
                                        autoComplete="off"
                                    />
                                    {pageErrors.name && <p className="text-sm text-destructive">{pageErrors.name}</p>}
                                </div>
                                <div className="grid gap-2">
                                    <Label>Siding (optional)</Label>
                                    <Select
                                        value={opSidingId === '' ? '__none__' : opSidingId}
                                        onValueChange={(v) => setOpSidingId(v === '__none__' ? '' : v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Any siding" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none__">Any siding</SelectItem>
                                            {sidings.map((s) => (
                                                <SelectItem key={s.id} value={String(s.id)}>
                                                    {s.name} ({s.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {pageErrors.siding_id && <p className="text-sm text-destructive">{pageErrors.siding_id}</p>}
                                </div>
                                <div className="flex items-center justify-between gap-4 rounded-lg border p-3">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="loader-op-active">Active</Label>
                                        <p className="text-muted-foreground text-xs">Inactive names are hidden from rake loading dropdowns.</p>
                                    </div>
                                    <Checkbox
                                        id="loader-op-active"
                                        checked={opActive}
                                        onCheckedChange={(v) => setOpActive(v === true)}
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setOperatorDialogOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={opSubmitting}>
                                    {editingOperator ? 'Save' : 'Create'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={duplicateConfirmOpen}
                    onOpenChange={(open) => {
                        setDuplicateConfirmOpen(open);
                        if (!open) {
                            pendingOperatorPayloadRef.current = null;
                        }
                    }}
                >
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Duplicate operator name</DialogTitle>
                            <DialogDescription>
                                {editingOperator
                                    ? 'Another operator already has this name. Are you sure you want to save?'
                                    : 'An operator with this name already exists. Are you sure you want to add another with the same name?'}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter className="gap-2 sm:gap-0">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDuplicateConfirmOpen(false)}
                                data-pan="master-data-loader-operator-duplicate-cancel"
                            >
                                Cancel
                            </Button>
                            <Button type="button" onClick={confirmDuplicateOperator} data-pan="master-data-loader-operator-duplicate-confirm">
                                Yes, continue
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
