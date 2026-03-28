import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, Plus, Edit, Trash2 } from 'lucide-react';
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
}

export default function Index({ coalStockDetails, sidings, filters }: Props) {
  const canCreate = useCan('sections.daily_stock_details.create');
  const canUpdate = useCan('sections.daily_stock_details.update');
  const canDelete = useCan('sections.daily_stock_details.delete');
  const { data, setData, processing } = useForm({
    siding_id: filters.siding_id ? String(filters.siding_id) : ALL_SIDINGS_VALUE,
    date_from: filters.date_from || '',
    date_to: filters.date_to || '',
  });

  function filterQueryParams(): Record<string, string> {
    const q: Record<string, string> = {};
    if (data.siding_id && data.siding_id !== ALL_SIDINGS_VALUE) {
      q.siding_id = data.siding_id;
    }
    if (data.date_from) {
      q.date_from = data.date_from;
    }
    if (data.date_to) {
      q.date_to = data.date_to;
    }

    return q;
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    router.get('/master-data/daily-stock-details', filterQueryParams(), {
      preserveState: true,
    });
  }

  function clearFilters() {
    setData({
      siding_id: ALL_SIDINGS_VALUE,
      date_from: '',
      date_to: '',
    });
    router.get('/master-data/daily-stock-details', {}, { preserveState: true });
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

        <Card>
          <CardHeader>
            <CardTitle>Filters</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                  <Label htmlFor="siding_id">Siding</Label>
                  <Select
                    value={data.siding_id}
                    onValueChange={(value) => setData('siding_id', value)}
                  >
                    <SelectTrigger>
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
                <div>
                  <Label htmlFor="date_from">Date From</Label>
                  <Input
                    id="date_from"
                    type="date"
                    value={data.date_from}
                    onChange={(e) => setData('date_from', e.target.value)}
                  />
                </div>
                <div>
                  <Label htmlFor="date_to">Date To</Label>
                  <Input
                    id="date_to"
                    type="date"
                    value={data.date_to}
                    onChange={(e) => setData('date_to', e.target.value)}
                  />
                </div>
                <div className="flex items-end space-x-2">
                  <Button type="submit" disabled={processing}>
                    <Search className="h-4 w-4 mr-2" />
                    Search
                  </Button>
                  <Button type="button" variant="outline" onClick={clearFilters}>
                    Clear
                  </Button>
                </div>
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
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        detail.source === 'manual' 
                          ? 'bg-blue-100 text-blue-800' 
                          : detail.source === 'system'
                          ? 'bg-green-100 text-green-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}>
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

            {/* Pagination */}
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
