import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download, Search, Plus, Edit, Trash2 } from 'lucide-react';
import { useCan } from '@/hooks/use-can';
import AppLayout from '@/layouts/app-layout';

interface Siding {
  id: number;
  name: string;
}

interface CoalStockDetail {
  id: number;
  siding_id: number | null;
  date: string | null;
  railway_siding_opening_coal_stock: number;
  railway_siding_closing_coal_stock: number;
  coal_dispatch_qty: number;
  no_of_rakes: string | null;
  rakes_qty: number;
  source: 'manual' | 'system' | null;
  remarks: string | null;
  siding?: Siding;
  created_at: string;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

/** Radix Select does not allow SelectItem with an empty string value. */
const ALL_SIDINGS_VALUE = '__all__';

type DatePreset = 'today' | 'yesterday' | 'custom';

function inferDatePreset(dateFrom: string, dateTo: string, today: string, yesterday: string): DatePreset {
  if (dateFrom === '' && dateTo === '') {
    return 'custom';
  }
  if (dateFrom === dateTo) {
    if (dateFrom === today) {
      return 'today';
    }
    if (dateFrom === yesterday) {
      return 'yesterday';
    }
  }

  return 'custom';
}

interface Props {
  coalStockDetails: {
    data: CoalStockDetail[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
  };
  sidings: Siding[];
  filters: {
    siding_id?: string;
    date_from?: string;
    date_to?: string;
  };
  calendar: {
    today: string;
    yesterday: string;
  };
}

export default function Index({ coalStockDetails, sidings, filters, calendar }: Props) {
  const canView = useCan('sections.daily_stock_details.view');
  const canCreate = useCan('sections.daily_stock_details.create');
  const canUpdate = useCan('sections.daily_stock_details.update');
  const canDelete = useCan('sections.daily_stock_details.delete');

  const { data, setData, processing } = useForm({
    siding_id: filters.siding_id ? String(filters.siding_id) : ALL_SIDINGS_VALUE,
    date_from: filters.date_from || '',
    date_to: filters.date_to || '',
  });

  const [datePreset, setDatePreset] = useState<DatePreset>(() =>
    inferDatePreset(filters.date_from || '', filters.date_to || '', calendar.today, calendar.yesterday),
  );

  useEffect(() => {
    setData('siding_id', filters.siding_id ? String(filters.siding_id) : ALL_SIDINGS_VALUE);
    setData('date_from', filters.date_from || '');
    setData('date_to', filters.date_to || '');
    setDatePreset(inferDatePreset(filters.date_from || '', filters.date_to || '', calendar.today, calendar.yesterday));
  }, [filters.siding_id, filters.date_from, filters.date_to, calendar.today, calendar.yesterday]);

  function filterQueryParams(): Record<string, string> {
    const q: Record<string, string> = {
      date_from: data.date_from,
      date_to: data.date_to,
    };
    if (data.siding_id && data.siding_id !== ALL_SIDINGS_VALUE) {
      q.siding_id = data.siding_id;
    } else {
      q.siding_id = '';
    }

    return q;
  }

  const exportHref = useMemo(() => {
    const params = new URLSearchParams(filterQueryParams());
    const qs = params.toString();

    return `/master-data/daily-stock-details/export${qs ? `?${qs}` : ''}`;
  }, [data.siding_id, data.date_from, data.date_to]);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    router.get('/master-data/daily-stock-details', filterQueryParams(), {
      preserveState: true,
    });
  }

  function clearFilters() {
    setDatePreset('custom');
    setData('siding_id', ALL_SIDINGS_VALUE);
    setData('date_from', '');
    setData('date_to', '');
    router.get(
      '/master-data/daily-stock-details',
      { siding_id: '', date_from: '', date_to: '' },
      { preserveState: true },
    );
  }

  function onDatePresetChange(value: DatePreset) {
    setDatePreset(value);
    if (value === 'today') {
      setData('date_from', calendar.today);
      setData('date_to', calendar.today);
    } else if (value === 'yesterday') {
      setData('date_from', calendar.yesterday);
      setData('date_to', calendar.yesterday);
    }
  }

  return (
    <AppLayout>
      <Head title="Daily Stock Details" />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Daily Stock Details</h1>
            <p className="text-muted-foreground">Manage daily coal stock approximation records</p>
          </div>
          {canCreate && (
            <Link href="/master-data/daily-stock-details/create">
              <Button data-pan="daily-stock-details-create">
                <Plus className="h-4 w-4 mr-2" />
                Add Record
              </Button>
            </Link>
          )}
        </div>

        <Card className="py-0 gap-0">
          <CardHeader className="space-y-0 px-4 py-2">
            <CardTitle className="text-base font-semibold">Filters</CardTitle>
          </CardHeader>
          <CardContent className="px-4 pb-2.5 pt-0">
            <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-x-2 gap-y-1.5">
              <div className="flex w-[11rem] shrink-0 flex-col gap-0.5">
                <Label htmlFor="siding_id" className="text-xs text-muted-foreground">
                  Siding
                </Label>
                <Select
                  value={data.siding_id}
                  onValueChange={(value) => setData('siding_id', value)}
                >
                  <SelectTrigger id="siding_id" className="h-9 w-full" data-pan="daily-stock-details-filter-siding">
                    <SelectValue placeholder="All Sidings" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={ALL_SIDINGS_VALUE}>All Sidings</SelectItem>
                    {sidings.map((siding) => (
                      <SelectItem key={siding.id} value={siding.id.toString()}>
                        {siding.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="flex w-[8.75rem] shrink-0 flex-col gap-0.5">
                <Label htmlFor="date_preset" className="text-xs text-muted-foreground">
                  Date
                </Label>
                <Select value={datePreset} onValueChange={(v) => onDatePresetChange(v as DatePreset)}>
                  <SelectTrigger id="date_preset" className="h-9 w-full" data-pan="daily-stock-details-date-preset">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="today">Today</SelectItem>
                    <SelectItem value="yesterday">Yesterday</SelectItem>
                    <SelectItem value="custom">Custom</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {datePreset === 'custom' && (
                <>
                  <div className="flex w-[9.25rem] shrink-0 flex-col gap-0.5">
                    <Label htmlFor="date_from" className="text-xs text-muted-foreground">
                      From
                    </Label>
                    <Input
                      id="date_from"
                      type="date"
                      className="h-9 w-full px-2"
                      value={data.date_from}
                      onChange={(e) => setData('date_from', e.target.value)}
                    />
                  </div>
                  <div className="flex w-[9.25rem] shrink-0 flex-col gap-0.5">
                    <Label htmlFor="date_to" className="text-xs text-muted-foreground">
                      To
                    </Label>
                    <Input
                      id="date_to"
                      type="date"
                      className="h-9 w-full px-2"
                      value={data.date_to}
                      onChange={(e) => setData('date_to', e.target.value)}
                    />
                  </div>
                </>
              )}

              <div className="flex shrink-0 items-center gap-1.5">
                <Button type="submit" size="sm" className="h-9" disabled={processing} data-pan="daily-stock-details-search">
                  <Search className="h-4 w-4 sm:mr-1.5" />
                  <span className="hidden sm:inline">Search</span>
                </Button>
                <Button type="button" size="sm" variant="outline" className="h-9" onClick={clearFilters}>
                  Clear
                </Button>
                {canView && (
                  <Button type="button" size="sm" variant="outline" className="h-9" asChild>
                    <a href={exportHref} data-pan="daily-stock-details-export-xlsx">
                      <Download className="h-4 w-4 sm:mr-1.5" />
                      <span className="hidden sm:inline">Export</span>
                    </a>
                  </Button>
                )}
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Records ({coalStockDetails.total})</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Date</TableHead>
                  <TableHead>Siding</TableHead>
                  <TableHead>Opening Stock</TableHead>
                  <TableHead>Closing Stock</TableHead>
                  <TableHead>Dispatch Qty</TableHead>
                  <TableHead>No of Rakes</TableHead>
                  <TableHead>Rakes Qty</TableHead>
                  <TableHead>Source</TableHead>
                  <TableHead className="w-[120px]">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {coalStockDetails.data.map((detail) => (
                  <TableRow key={detail.id}>
                    <TableCell>{detail.date || '-'}</TableCell>
                    <TableCell className="font-medium">{detail.siding?.name || '-'}</TableCell>
                    <TableCell>
                      {detail.railway_siding_opening_coal_stock
                        ? Number(detail.railway_siding_opening_coal_stock).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                        : '-'}
                    </TableCell>
                    <TableCell>
                      {detail.railway_siding_closing_coal_stock
                        ? Number(detail.railway_siding_closing_coal_stock).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                        : '-'}
                    </TableCell>
                    <TableCell>
                      {detail.coal_dispatch_qty
                        ? Number(detail.coal_dispatch_qty).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                        : '-'}
                    </TableCell>
                    <TableCell>{detail.no_of_rakes || '-'}</TableCell>
                    <TableCell>
                      {detail.rakes_qty
                        ? Number(detail.rakes_qty).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                        : '-'}
                    </TableCell>
                    <TableCell>
                      <span
                        className={`px-2 py-1 rounded-full text-xs font-medium ${
                          detail.source === 'manual'
                            ? 'bg-blue-100 text-blue-800'
                            : detail.source === 'system'
                              ? 'bg-green-100 text-green-800'
                              : 'bg-gray-100 text-gray-800'
                        }`}
                      >
                        {detail.source || '-'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <div className="flex space-x-2">
                        {canUpdate && (
                          <Link href={`/master-data/daily-stock-details/${detail.id}/edit`}>
                            <Button variant="outline" size="sm" data-pan="daily-stock-details-edit">
                              <Edit className="h-4 w-4" />
                            </Button>
                          </Link>
                        )}
                        {canDelete && (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                              if (confirm('Are you sure you want to delete this record?')) {
                                router.delete(`/master-data/daily-stock-details/${detail.id}`, {
                                  preserveScroll: true,
                                });
                              }
                            }}
                            data-pan="daily-stock-details-delete"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>

            {coalStockDetails.data.length === 0 && (
              <div className="text-center py-8 text-muted-foreground">
                No records found
              </div>
            )}

            {coalStockDetails.last_page > 1 && (
              <div className="flex justify-center mt-6 space-x-2">
                {coalStockDetails.links.map((link, index) => (
                  <Link
                    key={index}
                    href={link.url || '#'}
                    className={`px-3 py-2 rounded-md text-sm ${
                      link.active
                        ? 'bg-primary text-primary-foreground'
                        : link.url
                          ? 'bg-secondary text-secondary-foreground hover:bg-secondary/80'
                          : 'bg-muted text-muted-foreground cursor-not-allowed'
                    }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                  />
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
