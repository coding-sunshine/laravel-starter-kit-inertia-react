import React, { useEffect, useMemo } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';

const ALL_SIDINGS = '__all__';
const ALL_TYPES = '__all__';

interface SidingOption {
  id: number;
  name: string;
}

interface Creator {
  id: number;
  name: string;
  email: string;
}

interface LedgerRow {
  id: number;
  created_at: string | null;
  transaction_type: string;
  quantity_mt: number;
  opening_balance_mt: number;
  closing_balance_mt: number;
  reference_number: string | null;
  remarks: string | null;
  siding: { id: number; name: string } | null;
  rake: { id: number; rake_number: string | null } | null;
  creator: Creator | null;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  flash: { success?: string | null };
  ledgers: {
    data: LedgerRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
  };
  sidings: SidingOption[];
  filters: {
    siding_id?: number | null;
    rake_number?: string | null;
    from?: string | null;
    to?: string | null;
    transaction_type?: string | null;
  };
}

function fmtMt(n: number): string {
  return Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export default function Index({ flash, ledgers, sidings, filters }: Props) {
  const filterForm = useForm({
    siding_id: filters.siding_id ? String(filters.siding_id) : ALL_SIDINGS,
    rake_number: filters.rake_number ?? '',
    from: filters.from ?? '',
    to: filters.to ?? '',
    transaction_type: filters.transaction_type ?? ALL_TYPES,
  });

  useEffect(() => {
    filterForm.reset({
      siding_id: filters.siding_id ? String(filters.siding_id) : ALL_SIDINGS,
      rake_number: filters.rake_number ?? '',
      from: filters.from ?? '',
      to: filters.to ?? '',
      transaction_type: filters.transaction_type ?? ALL_TYPES,
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- sync when Inertia passes new filters from URL
  }, [filters.siding_id, filters.rake_number, filters.from, filters.to, filters.transaction_type]);

  const adjustForm = useForm({
    siding_id: sidings[0]?.id ? String(sidings[0].id) : '',
    direction: 'add' as 'add' | 'deduct',
    quantity_mt: '',
    remarks: '',
  });

  function filterQuery(): Record<string, string> {
    const q: Record<string, string> = {};
    if (filterForm.data.from) {
      q.from = filterForm.data.from;
    }
    if (filterForm.data.to) {
      q.to = filterForm.data.to;
    }
    if (filterForm.data.siding_id && filterForm.data.siding_id !== ALL_SIDINGS) {
      q.siding_id = filterForm.data.siding_id;
    }
    if (filterForm.data.rake_number.trim() !== '') {
      q.rake_number = filterForm.data.rake_number.trim();
    }
    if (filterForm.data.transaction_type && filterForm.data.transaction_type !== ALL_TYPES) {
      q.transaction_type = filterForm.data.transaction_type;
    }
    return q;
  }

  function submitFilters(e: React.FormEvent) {
    e.preventDefault();
    router.get('/master-data/stock-ledger', filterQuery(), { preserveState: true, replace: true });
  }

  function clearFilters() {
    filterForm.setData({
      siding_id: ALL_SIDINGS,
      rake_number: '',
      from: '',
      to: '',
      transaction_type: ALL_TYPES,
    });
    router.get('/master-data/stock-ledger', {}, { preserveState: true, replace: true });
  }

  function submitAdjust(e: React.FormEvent) {
    e.preventDefault();
    adjustForm.post('/master-data/stock-ledger/adjust', { preserveScroll: true });
  }

  const successBanner = useMemo(() => flash?.success, [flash?.success]);

  return (
    <AppLayout>
      <Head title="Stock ledger" />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Stock ledger</h1>
          <p className="text-muted-foreground">
            Filter by siding, rake number (matches any month), dates, and type. Manual add/deduct posts UI-only ledger
            rows (separate from automated flows).
          </p>
        </div>

        {successBanner ? (
          <div className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
            {successBanner}
          </div>
        ) : null}

        <Card>
          <CardHeader>
            <CardTitle>Filters</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={submitFilters} className="flex flex-wrap items-end gap-3">
              <div className="flex min-w-[10rem] flex-col gap-1">
                <Label htmlFor="filter_siding">Siding</Label>
                <Select
                  value={filterForm.data.siding_id}
                  onValueChange={(v) => filterForm.setData('siding_id', v)}
                >
                  <SelectTrigger id="filter_siding">
                    <SelectValue placeholder="All sidings" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={ALL_SIDINGS}>All sidings</SelectItem>
                    {sidings.map((s) => (
                      <SelectItem key={s.id} value={String(s.id)}>
                        {s.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="flex min-w-[11rem] flex-col gap-1">
                <Label htmlFor="filter_rake_number">Rake number</Label>
                <Input
                  id="filter_rake_number"
                  type="text"
                  autoComplete="off"
                  placeholder="e.g. 17"
                  value={filterForm.data.rake_number}
                  onChange={(e) => filterForm.setData('rake_number', e.target.value)}
                />
                <p className="text-xs text-muted-foreground">Same number can repeat next month; we match by number, not id.</p>
              </div>

              <div className="flex w-[10rem] flex-col gap-1">
                <Label htmlFor="filter_from">From</Label>
                <Input
                  id="filter_from"
                  type="date"
                  value={filterForm.data.from}
                  onChange={(e) => filterForm.setData('from', e.target.value)}
                />
              </div>

              <div className="flex w-[10rem] flex-col gap-1">
                <Label htmlFor="filter_to">To</Label>
                <Input
                  id="filter_to"
                  type="date"
                  value={filterForm.data.to}
                  onChange={(e) => filterForm.setData('to', e.target.value)}
                />
              </div>

              <div className="flex min-w-[10rem] flex-col gap-1">
                <Label htmlFor="filter_type">Type</Label>
                <Select
                  value={filterForm.data.transaction_type}
                  onValueChange={(v) => filterForm.setData('transaction_type', v)}
                >
                  <SelectTrigger id="filter_type">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={ALL_TYPES}>All types</SelectItem>
                    <SelectItem value="receipt">Receipt</SelectItem>
                    <SelectItem value="dispatch">Dispatch</SelectItem>
                    <SelectItem value="correction">Correction</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="flex gap-2">
                <Button type="submit" size="sm" disabled={filterForm.processing}>
                  <Search className="mr-1 h-4 w-4" />
                  Apply
                </Button>
                <Button type="button" size="sm" variant="outline" onClick={clearFilters}>
                  Clear
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Adjust stock (manual)</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={submitAdjust} className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div className="flex flex-col gap-1">
                <Label htmlFor="adj_siding">Siding</Label>
                <Select
                  value={adjustForm.data.siding_id}
                  onValueChange={(v) => adjustForm.setData('siding_id', v)}
                >
                  <SelectTrigger id="adj_siding">
                    <SelectValue placeholder="Select siding" />
                  </SelectTrigger>
                  <SelectContent>
                    {sidings.map((s) => (
                      <SelectItem key={s.id} value={String(s.id)}>
                        {s.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {adjustForm.errors.siding_id ? (
                  <p className="text-sm text-destructive">{adjustForm.errors.siding_id}</p>
                ) : null}
              </div>

              <div className="flex flex-col gap-1">
                <Label>Direction</Label>
                <Select
                  value={adjustForm.data.direction}
                  onValueChange={(v) => adjustForm.setData('direction', v as 'add' | 'deduct')}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="add">Add</SelectItem>
                    <SelectItem value="deduct">Deduct</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="flex flex-col gap-1">
                <Label htmlFor="adj_qty">Quantity (MT)</Label>
                <Input
                  id="adj_qty"
                  type="number"
                  step="0.01"
                  min="0.01"
                  value={adjustForm.data.quantity_mt}
                  onChange={(e) => adjustForm.setData('quantity_mt', e.target.value)}
                />
                {adjustForm.errors.quantity_mt ? (
                  <p className="text-sm text-destructive">{adjustForm.errors.quantity_mt}</p>
                ) : null}
              </div>

              <div className="md:col-span-2 lg:col-span-3 flex flex-col gap-1">
                <Label htmlFor="adj_remarks">Remarks</Label>
                <Input
                  id="adj_remarks"
                  value={adjustForm.data.remarks}
                  onChange={(e) => adjustForm.setData('remarks', e.target.value)}
                />
                {adjustForm.errors.remarks ? (
                  <p className="text-sm text-destructive">{adjustForm.errors.remarks}</p>
                ) : null}
              </div>

              <div className="md:col-span-2 lg:col-span-3">
                <Button type="submit" disabled={adjustForm.processing}>
                  Save adjustment
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Ledger ({ledgers.total})</CardTitle>
          </CardHeader>
          <CardContent className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>When</TableHead>
                  <TableHead>Siding</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead className="text-right">Qty (MT)</TableHead>
                  <TableHead className="text-right">Opening</TableHead>
                  <TableHead className="text-right">Closing</TableHead>
                  <TableHead>Rake</TableHead>
                  <TableHead>Reference</TableHead>
                  <TableHead>Remarks</TableHead>
                  <TableHead>Created by</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {ledgers.data.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell className="whitespace-nowrap text-sm">
                      {row.created_at ? new Date(row.created_at).toLocaleString() : '—'}
                    </TableCell>
                    <TableCell>{row.siding?.name ?? '—'}</TableCell>
                    <TableCell className="capitalize">{row.transaction_type}</TableCell>
                    <TableCell className="text-right font-mono">{fmtMt(row.quantity_mt)}</TableCell>
                    <TableCell className="text-right font-mono">{fmtMt(row.opening_balance_mt)}</TableCell>
                    <TableCell className="text-right font-mono">{fmtMt(row.closing_balance_mt)}</TableCell>
                    <TableCell>
                      {row.rake?.rake_number ? `#${row.rake.rake_number}` : '—'}
                    </TableCell>
                    <TableCell className="max-w-[140px] truncate text-sm">{row.reference_number ?? '—'}</TableCell>
                    <TableCell className="max-w-[200px] truncate text-sm">{row.remarks ?? '—'}</TableCell>
                    <TableCell className="text-sm">
                      {row.creator ? (
                        <span title={row.creator.email}>{row.creator.name}</span>
                      ) : (
                        '—'
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>

            {ledgers.data.length === 0 ? (
              <div className="py-8 text-center text-muted-foreground">No rows match the filters.</div>
            ) : null}

            {ledgers.last_page > 1 ? (
              <div className="mt-6 flex justify-center space-x-2">
                {ledgers.links.map((link, index) => (
                  <Link
                    key={index}
                    href={link.url || '#'}
                    className={`rounded-md px-3 py-2 text-sm ${
                      link.active
                        ? 'bg-primary text-primary-foreground'
                        : link.url
                          ? 'bg-secondary text-secondary-foreground hover:bg-secondary/80'
                          : 'cursor-not-allowed bg-muted text-muted-foreground'
                    }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                  />
                ))}
              </div>
            ) : null}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
