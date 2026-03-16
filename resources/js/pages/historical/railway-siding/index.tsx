import React, { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent } from '@/components/ui/card';
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
  rake_number: number | null;
  priority_number: number | null;
  rr_number: number | null;
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
}

interface PaginatedRakes {
  data: HistoricalRake[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Props {
  rakes: PaginatedRakes;
  sidings: Siding[];
  sidingId?: number | null;
}

export default function HistoricalRailwaySidingIndex({
  rakes,
  sidings,
  sidingId,
}: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Historical', href: '/historical/railway-siding' },
    { title: 'Railway Siding', href: '' },
  ];

  const [rakesState, setRakesState] = useState<HistoricalRake[]>(() =>
    Array.isArray(rakes?.data) ? rakes.data : []
  );

  useEffect(() => {
    setRakesState(Array.isArray(rakes?.data) ? rakes.data : []);
  }, [rakes]);

  const firstSidingId = sidings[0]?.id ?? null;
  const effectiveSidingId = sidingId ?? firstSidingId;

  const handleFilterChange = (params: { siding_id?: number | null }) => {
    const query: Record<string, string> = {};

    const finalSidingId = params.siding_id ?? effectiveSidingId;
    if (finalSidingId != null) {
      query.siding_id = String(finalSidingId);
    }

    router.get('/historical/railway-siding', query, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Historical Railway Siding" />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Historical Railway Siding</h1>
            <p className="text-gray-600 mt-1">Historical rake records imported from Excel, grouped by siding.</p>
          </div>
        </div>

        <ToggleGroup
          type="single"
          value={effectiveSidingId == null ? '' : String(effectiveSidingId)}
          onValueChange={(value) => {
            const id = Number(value);
            if (Number.isNaN(id)) return;
            handleFilterChange({ siding_id: id });
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
          <CardContent className="pt-4">
            <HistoricalRakeTable
              rakes={rakesState}
              onRakeUpdated={(updated) => {
                setRakesState((prev) =>
                  prev.map((r) => (r.id === updated.id ? updated : r))
                );
              }}
              onRakeDeleted={(id) => {
                setRakesState((prev) => prev.filter((r) => r.id !== id));
              }}
              onAddRow={async () => {
                const payload = {
                  siding_id: effectiveSidingId,
                };
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
                  const data = await res.json().catch(() => ({}));
                  if (!res.ok) {
                    return;
                  }
                  const newRake = (data as { rake?: HistoricalRake }).rake;
                  if (newRake) {
                    setRakesState((prev) =>
                      [newRake, ...prev].sort((a, b) => b.id - a.id)
                    );
                  }
                } catch {
                  // ignore for now
                }
              }}
            />

            <div className="mt-6 flex flex-col items-center gap-3 text-sm text-gray-700">
              <div className="font-medium">
                Page <span className="font-semibold">{rakes.current_page}</span> of{' '}
                <span className="font-semibold">{rakes.last_page}</span> · Total{' '}
                <span className="font-semibold">{rakes.total}</span> records
              </div>
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  disabled={rakes.current_page <= 1}
                  className="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:shadow-none"
                  onClick={() => {
                    if (rakes.current_page <= 1) return;
                    const query: Record<string, string> = {};
                    if (effectiveSidingId != null) {
                      query.siding_id = String(effectiveSidingId);
                    }
                    query.page = String(rakes.current_page - 1);
                    router.get('/historical/railway-siding', query, {
                      preserveState: true,
                      preserveScroll: true,
                    });
                  }}
                >
                  Previous
                </button>
                <button
                  type="button"
                  disabled={rakes.current_page >= rakes.last_page}
                  className="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:shadow-none"
                  onClick={() => {
                    if (rakes.current_page >= rakes.last_page) return;
                    const query: Record<string, string> = {};
                    if (effectiveSidingId != null) {
                      query.siding_id = String(effectiveSidingId);
                    }
                    query.page = String(rakes.current_page + 1);
                    router.get('/historical/railway-siding', query, {
                      preserveState: true,
                      preserveScroll: true,
                    });
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

