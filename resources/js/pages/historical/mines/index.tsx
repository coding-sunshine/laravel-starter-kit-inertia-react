import React, { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent } from '@/components/ui/card';
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

interface HistoricalMine {
  id: number;
  month: string | null;
  trips_dispatched: number | null;
  dispatched_qty: string | number | null;
  trips_received: number | null;
  received_qty: string | number | null;
  coal_production_qty: string | number | null;
  ob_production_qty: string | number | null;
}

interface PaginatedMines {
  data: HistoricalMine[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Props {
  mines: PaginatedMines;
}

export default function HistoricalMinesIndex({ mines }: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Historical', href: '/historical/mines' },
    { title: 'Mines', href: '' },
  ];

  const [minesState, setMinesState] = useState<HistoricalMine[]>(() =>
    Array.isArray(mines?.data) ? mines.data : [],
  );

  useEffect(() => {
    setMinesState(Array.isArray(mines?.data) ? mines.data : []);
  }, [mines]);

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
          <CardContent className="pt-4">
            <HistoricalMineTable
              mines={minesState}
              onMineUpdated={(updated) => {
                setMinesState((prev) => prev.map((m) => (m.id === updated.id ? updated : m)));
              }}
              onMineDeleted={(id) => {
                setMinesState((prev) => prev.filter((m) => m.id !== id));
              }}
              onAddRow={async () => {
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
                  const data = await res.json().catch(() => ({}));
                  if (!res.ok) {
                    return;
                  }
                  const newMine = (data as { mine?: HistoricalMine }).mine;
                  if (newMine) {
                    setMinesState((prev) => [newMine, ...prev].sort((a, b) => b.id - a.id));
                  }
                } catch {
                  // ignore for now
                }
              }}
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
                    const query: Record<string, string> = {};
                    query.page = String(mines.current_page - 1);
                    router.get('/historical/mines', query, {
                      preserveState: true,
                      preserveScroll: true,
                    });
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
                    const query: Record<string, string> = {};
                    query.page = String(mines.current_page + 1);
                    router.get('/historical/mines', query, {
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

