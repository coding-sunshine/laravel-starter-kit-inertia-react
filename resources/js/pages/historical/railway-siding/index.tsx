import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import HistoricalRakeTable from '@/components/historical/historical-rake-table';

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

interface Siding {
  id: number;
  name: string;
}

interface HistoricalRake {
  id: number;
  siding_id: number;
  siding_name?: string | null;
  rake_number: number | string | null;
  priority_number: number | null;
  rr_number: number | string | null;
  wagon_count: number | null;
  loaded_weight_mt: string | number | null;
  under_load_mt: string | number | null;
  over_load_mt: string | number | null;
  overload_wagon_count: number | null;
  detention_hours: string | number | null;
  shunting_hours: string | number | null;
  total_amount_rs: string | number | null;
  destination: string | null;
  pakur_imwb_period: string | null;
  loading_date: string | null;
  data_source?: string | null;
  remarks: string | null;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginatedRakes {
  data: HistoricalRake[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: PaginationLink[];
}

interface ListFilters {
  loading_date_from: string;
  loading_date_to: string;
  rake_number: string;
  rr_number: string;
  destination: string;
}

interface Props {
  rakes: PaginatedRakes;
  sidings: Siding[];
  sidingId?: number | null;
  filters: ListFilters;
}

export default function HistoricalRailwaySidingIndex({
  rakes,
  sidings,
  sidingId,
  filters,
}: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Historical', href: '/historical/railway-siding' },
    { title: 'Railway Siding', href: '' },
  ];

  const { data, setData, processing } = useForm({
    loading_date_from: filters.loading_date_from ?? '',
    loading_date_to: filters.loading_date_to ?? '',
    rake_number: filters.rake_number ?? '',
    rr_number: filters.rr_number ?? '',
    destination: filters.destination ?? '',
  });

  useEffect(() => {
    setData({
      loading_date_from: filters.loading_date_from ?? '',
      loading_date_to: filters.loading_date_to ?? '',
      rake_number: filters.rake_number ?? '',
      rr_number: filters.rr_number ?? '',
      destination: filters.destination ?? '',
    });
  }, [
    filters.loading_date_from,
    filters.loading_date_to,
    filters.rake_number,
    filters.rr_number,
    filters.destination,
    setData,
  ]);

  const [rakesState, setRakesState] = useState<HistoricalRake[]>(() =>
    Array.isArray(rakes?.data) ? rakes.data : [],
  );
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isAddingRow, setIsAddingRow] = useState(false);

  useEffect(() => {
    setRakesState(Array.isArray(rakes?.data) ? rakes.data : []);
  }, [rakes]);

  const firstSidingId = sidings[0]?.id ?? null;
  const effectiveSidingId = sidingId ?? firstSidingId;

  function listQueryParams(opts: { page?: number; sidingId?: number | null } = {}): Record<string, string> {
    const q: Record<string, string> = {};
    const sid = opts.sidingId !== undefined ? opts.sidingId : effectiveSidingId;
    if (sid != null) {
      q.siding_id = String(sid);
    }
    if (data.loading_date_from.trim()) {
      q.loading_date_from = data.loading_date_from.trim();
    }
    if (data.loading_date_to.trim()) {
      q.loading_date_to = data.loading_date_to.trim();
    }
    if (data.rake_number.trim()) {
      q.rake_number = data.rake_number.trim();
    }
    if (data.rr_number.trim()) {
      q.rr_number = data.rr_number.trim();
    }
    if (data.destination.trim()) {
      q.destination = data.destination.trim();
    }
    const page = opts.page ?? rakes.current_page;
    if (page > 1) {
      q.page = String(page);
    }
    return q;
  }

  const exportHref = useMemo(() => {
    const params = new URLSearchParams();
    const sid = sidingId ?? firstSidingId;
    if (sid != null) {
      params.set('siding_id', String(sid));
    }
    if (filters.loading_date_from.trim()) {
      params.set('loading_date_from', filters.loading_date_from.trim());
    }
    if (filters.loading_date_to.trim()) {
      params.set('loading_date_to', filters.loading_date_to.trim());
    }
    if (filters.rake_number.trim()) {
      params.set('rake_number', filters.rake_number.trim());
    }
    if (filters.rr_number.trim()) {
      params.set('rr_number', filters.rr_number.trim());
    }
    if (filters.destination.trim()) {
      params.set('destination', filters.destination.trim());
    }
    const qs = params.toString();
    return qs ? `/historical/railway-siding/export?${qs}` : '/historical/railway-siding/export';
  }, [sidingId, firstSidingId, filters]);

  function applySearch(e: React.FormEvent) {
    e.preventDefault();
    router.get('/historical/railway-siding', listQueryParams({ page: 1 }), {
      preserveState: true,
      preserveScroll: true,
    });
  }

  function clearFilters() {
    setData({
      loading_date_from: '',
      loading_date_to: '',
      rake_number: '',
      rr_number: '',
      destination: '',
    });
    const sid = effectiveSidingId;
    const q: Record<string, string> = {};
    if (sid != null) {
      q.siding_id = String(sid);
    }
    router.get('/historical/railway-siding', q, {
      preserveState: true,
      preserveScroll: true,
    });
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Historical Railway Siding" />

      <div className="space-y-6">
        <div className="flex justify-between items-center gap-4 flex-wrap">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Historical Railway Siding</h1>
            <p className="text-gray-600 mt-1">Historical rake records imported from Excel, grouped by siding.</p>
          </div>
          <a
            href={exportHref}
            className="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
            data-pan="historical-railway-siding-export-xlsx"
          >
            Export XLSX
          </a>
        </div>

        <ToggleGroup
          type="single"
          value={effectiveSidingId == null ? '' : String(effectiveSidingId)}
          onValueChange={(value) => {
            const id = Number(value);
            if (Number.isNaN(id)) return;
            router.get('/historical/railway-siding', listQueryParams({ page: 1, sidingId: id }), {
              preserveState: true,
              preserveScroll: true,
            });
          }}
          className="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
        >
          {sidings.map((siding) => (
            <ToggleGroupItem
              key={siding.id}
              value={String(siding.id)}
              aria-label={siding.name}
              className={`flex items-center rounded-md px-3.5 py-1.5 text-sm border transition-colors ${
                siding.id === effectiveSidingId
                  ? 'bg-primary text-primary-foreground border-primary'
                  : 'bg-white text-gray-700 border-neutral-200 hover:bg-neutral-100'
              }`}
            >
              {siding.name}
            </ToggleGroupItem>
          ))}
        </ToggleGroup>

        <Card>
          <CardContent className="pt-4 space-y-4">
            <form onSubmit={applySearch} className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 items-end">
              <div className="space-y-1.5">
                <Label htmlFor="loading_date_from">Loading date from</Label>
                <Input
                  id="loading_date_from"
                  type="date"
                  value={data.loading_date_from}
                  onChange={(e) => setData('loading_date_from', e.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="loading_date_to">Loading date to</Label>
                <Input
                  id="loading_date_to"
                  type="date"
                  value={data.loading_date_to}
                  onChange={(e) => setData('loading_date_to', e.target.value)}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="rake_number">Rake no</Label>
                <Input
                  id="rake_number"
                  value={data.rake_number}
                  onChange={(e) => setData('rake_number', e.target.value)}
                  autoComplete="off"
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="rr_number">RR no</Label>
                <Input
                  id="rr_number"
                  value={data.rr_number}
                  onChange={(e) => setData('rr_number', e.target.value)}
                  autoComplete="off"
                />
              </div>
              <div className="space-y-1.5 xl:col-span-1">
                <Label htmlFor="destination">Destination</Label>
                <Input
                  id="destination"
                  value={data.destination}
                  onChange={(e) => setData('destination', e.target.value)}
                  autoComplete="off"
                />
              </div>
              <div className="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-3 xl:col-span-6">
                <Button type="submit" disabled={processing} data-pan="historical-railway-siding-filter-search">
                  Search
                </Button>
                <Button
                  type="button"
                  variant="outline"
                  disabled={processing}
                  onClick={clearFilters}
                  data-pan="historical-railway-siding-filter-clear"
                >
                  Clear
                </Button>
              </div>
            </form>

            <HistoricalRakeTable
              rakes={rakesState}
              editingId={editingId}
              onEditingChange={setEditingId}
              onRakeUpdated={(updated) => {
                setRakesState((prev) =>
                  prev.map((r) => (r.id === updated.id ? updated : r))
                );
              }}
              onRakeDeleted={(id) => {
                setEditingId((prev) => (prev === id ? null : prev));
                router.reload({
                  only: ['rakes'],
                  preserveScroll: true,
                });
              }}
              onAddRow={async () => {
                const payload = {
                  siding_id: effectiveSidingId,
                };
                setIsAddingRow(true);
                try {
                  const res = await fetch('/historical/railway-siding', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      Accept: 'application/json',
                      'X-Requested-With': 'XMLHttpRequest',
                      ...getCsrfHeaders(),
                    },
                    body: JSON.stringify(payload),
                    credentials: 'include',
                  });
                  const resData = await res.json().catch(() => ({}));
                  if (!res.ok) {
                    return;
                  }
                  const newRake = (resData as { rake?: HistoricalRake }).rake;
                  if (newRake) {
                    setEditingId(newRake.id);
                    router.get('/historical/railway-siding', listQueryParams({ page: 1 }), {
                      preserveScroll: true,
                    });
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
                Page <span className="font-semibold">{rakes.current_page}</span> of{' '}
                <span className="font-semibold">{rakes.last_page}</span> · Total{' '}
                <span className="font-semibold">{rakes.total}</span> records
              </div>
              {rakes.last_page > 1 && Array.isArray(rakes.links) && (
                <div className="flex flex-wrap justify-center gap-1">
                  {rakes.links.map((link, index) => (
                    <Link
                      key={index}
                      href={link.url || '#'}
                      preserveScroll
                      className={`px-3 py-2 rounded-md text-sm ${
                        link.active
                          ? 'bg-primary text-primary-foreground'
                          : link.url
                            ? 'bg-secondary text-secondary-foreground hover:bg-secondary/80'
                            : 'bg-muted text-muted-foreground cursor-not-allowed pointer-events-none'
                      }`}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  ))}
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
