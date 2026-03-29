import React, { useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import HistoricalMineTable from '@/components/historical/historical-mine-table';

function getCsrfHeaders(): Record<string, string> {
  const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
  if (cookieMatch) {
    return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
  }
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta?.getAttribute('content')) {
    return { 'X-CSRF-TOKEN': meta.getAttribute('content')! };
  }
  return {};
}

/** Radix Select does not allow SelectItem with an empty string value. */
const ALL_SIDINGS_VALUE = '__all__';

interface HistoricalMine {
  id: number;
  month: string | null;
  siding_id: number | null;
  siding_name: string | null;
  trips_dispatched: number | null;
  dispatched_qty: string | number | null;
  trips_received: number | null;
  received_qty: string | number | null;
  coal_production_qty: string | number | null;
  ob_production_qty: string | number | null;
  remarks: string | null;
}

interface PaginatedMines {
  data: HistoricalMine[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface SidingOption {
  id: number;
  name: string;
}

interface Filters {
  date_from: string | null;
  date_to: string | null;
  siding_id: number | null;
}

interface Props {
  mines: PaginatedMines;
  sidings: SidingOption[];
  filters: Filters;
}

export default function HistoricalMinesIndex({ mines, sidings, filters }: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Historical', href: '/historical/mines' },
    { title: 'Mines', href: '' },
  ];

  const { data, setData, processing } = useForm({
    date_from: filters.date_from ?? '',
    date_to: filters.date_to ?? '',
    siding_id: filters.siding_id != null ? String(filters.siding_id) : ALL_SIDINGS_VALUE,
  });

  useEffect(() => {
    setData({
      date_from: filters.date_from ?? '',
      date_to: filters.date_to ?? '',
      siding_id: filters.siding_id != null ? String(filters.siding_id) : ALL_SIDINGS_VALUE,
    });
  }, [filters.date_from, filters.date_to, filters.siding_id, setData]);

  const [minesState, setMinesState] = useState<HistoricalMine[]>(() =>
    Array.isArray(mines?.data) ? mines.data : [],
  );
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isAddingRow, setIsAddingRow] = useState(false);

  useEffect(() => {
    setMinesState(Array.isArray(mines?.data) ? mines.data : []);
  }, [mines]);

  function filterQueryParams(page?: number): Record<string, string> {
    const q: Record<string, string> = {};
    if (data.date_from) {
      q.date_from = data.date_from;
    }
    if (data.date_to) {
      q.date_to = data.date_to;
    }
    if (data.siding_id && data.siding_id !== ALL_SIDINGS_VALUE) {
      q.siding_id = data.siding_id;
    }
    if (page !== undefined && page >= 1) {
      q.page = String(page);
    }

    return q;
  }

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    router.get('/historical/mines', filterQueryParams(1), {
      preserveState: true,
      preserveScroll: true,
    });
  }

  function clearFilters() {
    setData({
      date_from: '',
      date_to: '',
      siding_id: ALL_SIDINGS_VALUE,
    });
    router.get('/historical/mines', {}, { preserveState: true, preserveScroll: true });
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Historical Mines" />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Historical Mines</h1>
            <p className="text-gray-600 mt-1">
              Monthly historical mines data for dispatched and received trips and quantities, and
              production.
            </p>
          </div>
        </div>

        <Card>
          <CardContent className="pt-6">
            <form
              onSubmit={applyFilters}
              className="flex flex-wrap items-end gap-4 border-b border-gray-200 pb-6 mb-6"
            >
              <div className="space-y-2">
                <Label htmlFor="historical-mines-date-from">Date from</Label>
                <Input
                  id="historical-mines-date-from"
                  type="date"
                  value={data.date_from}
                  onChange={(e) => setData('date_from', e.target.value)}
                  className="w-[11rem]"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="historical-mines-date-to">Date to</Label>
                <Input
                  id="historical-mines-date-to"
                  type="date"
                  value={data.date_to}
                  onChange={(e) => setData('date_to', e.target.value)}
                  className="w-[11rem]"
                />
              </div>
              <div className="space-y-2 min-w-[12rem]">
                <Label htmlFor="historical-mines-siding">Siding</Label>
                <Select
                  value={data.siding_id}
                  onValueChange={(v) => setData('siding_id', v)}
                >
                  <SelectTrigger id="historical-mines-siding">
                    <SelectValue placeholder="All sidings" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={ALL_SIDINGS_VALUE}>All sidings</SelectItem>
                    {sidings.map((s) => (
                      <SelectItem key={s.id} value={String(s.id)}>
                        {s.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="flex gap-2">
                <Button type="submit" disabled={processing} data-pan="historical-mines-filter-apply">
                  Apply filters
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  onClick={clearFilters}
                  data-pan="historical-mines-filter-clear"
                >
                  Clear
                </Button>
              </div>
            </form>

            <HistoricalMineTable
              mines={minesState}
              editingId={editingId}
              onEditingChange={setEditingId}
              onMineUpdated={(updated) => {
                setMinesState((prev) => prev.map((m) => (m.id === updated.id ? updated : m)));
              }}
              onMineDeleted={(id) => {
                setMinesState((prev) => prev.filter((m) => m.id !== id));
                setEditingId((prev) => (prev === id ? null : prev));
              }}
              onAddRow={async () => {
                setIsAddingRow(true);
                try {
                  const res = await fetch('/historical/mines', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      Accept: 'application/json',
                      'X-Requested-With': 'XMLHttpRequest',
                      ...getCsrfHeaders(),
                    },
                    body: JSON.stringify({}),
                    credentials: 'include',
                  });
                  const responseData = await res.json().catch(() => ({}));
                  if (!res.ok) {
                    return;
                  }
                  const newMine = (responseData as { mine?: HistoricalMine }).mine;
                  if (newMine) {
                    setMinesState((prev) => [newMine, ...prev].sort((a, b) => b.id - a.id));
                    setEditingId(newMine.id);
                  }
                } catch {
                  // ignore for now
                } finally {
                  setIsAddingRow(false);
                }
              }}
              isAddingRow={isAddingRow}
            />

            <div className="mt-6 flex flex-col items-center gap-3 text-sm text-gray-700">
              <div className="font-medium">
                Page <span className="font-semibold">{mines.current_page}</span> of{' '}
                <span className="font-semibold">{mines.last_page}</span> · Total{' '}
                <span className="font-semibold">{mines.total}</span> records
              </div>
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  disabled={mines.current_page <= 1}
                  className="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:shadow-none"
                  onClick={() => {
                    if (mines.current_page <= 1) return;
                    router.get(
                      '/historical/mines',
                      filterQueryParams(mines.current_page - 1),
                      {
                        preserveState: true,
                        preserveScroll: true,
                      },
                    );
                  }}
                >
                  Previous
                </button>
                <button
                  type="button"
                  disabled={mines.current_page >= mines.last_page}
                  className="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:shadow-none"
                  onClick={() => {
                    if (mines.current_page >= mines.last_page) return;
                    router.get(
                      '/historical/mines',
                      filterQueryParams(mines.current_page + 1),
                      {
                        preserveState: true,
                        preserveScroll: true,
                      },
                    );
                  }}
                >
                  Next
                </button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
