import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';

const textareaClassName =
  'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

/** Radix Select requires every value to match a SelectItem; use a sentinel for optional siding. */
const NO_SIDING_VALUE = '__none__';

interface Siding {
  id: number;
  name: string;
}

interface Props {
  sidings: Siding[];
}

export default function Create({ sidings }: Props) {
  const { data, setData, post, processing, errors, transform } = useForm({
    siding_id: NO_SIDING_VALUE,
    date: '',
    railway_siding_opening_coal_stock: '',
    railway_siding_closing_coal_stock: '',
    coal_dispatch_qty: '',
    no_of_rakes: '',
    rakes_qty: '',
    source: 'manual',
    remarks: '',
  });

  transform((form) => ({
    ...form,
    siding_id: form.siding_id === NO_SIDING_VALUE ? '' : form.siding_id,
  }));

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    post('/master-data/daily-stock-details');
  }

  return (
    <AppLayout>
      <Head title="Create Daily Stock Details" />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Create Daily Stock Details</h1>
          <p className="text-muted-foreground">Add new daily coal stock approximation record</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Stock Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label htmlFor="siding_id">Siding</Label>
                  <Select
                    value={data.siding_id}
                    onValueChange={(value) => setData('siding_id', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select siding" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value={NO_SIDING_VALUE}>No siding</SelectItem>
                      {sidings.map((siding) => (
                        <SelectItem key={siding.id} value={siding.id.toString()}>
                          {siding.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.siding_id && (
                    <p className="text-sm text-destructive mt-1">{errors.siding_id}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="date">Date</Label>
                  <Input
                    id="date"
                    type="date"
                    value={data.date}
                    onChange={(e) => setData('date', e.target.value)}
                  />
                  {errors.date && <p className="text-sm text-destructive mt-1">{errors.date}</p>}
                </div>

                <div>
                  <Label htmlFor="railway_siding_opening_coal_stock">Opening Coal Stock (MT)</Label>
                  <Input
                    id="railway_siding_opening_coal_stock"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.railway_siding_opening_coal_stock}
                    onChange={(e) => setData('railway_siding_opening_coal_stock', e.target.value)}
                    placeholder="0.00"
                  />
                  {errors.railway_siding_opening_coal_stock && (
                    <p className="text-sm text-destructive mt-1">{errors.railway_siding_opening_coal_stock}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="railway_siding_closing_coal_stock">Closing Coal Stock (MT)</Label>
                  <Input
                    id="railway_siding_closing_coal_stock"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.railway_siding_closing_coal_stock}
                    onChange={(e) => setData('railway_siding_closing_coal_stock', e.target.value)}
                    placeholder="0.00"
                  />
                  {errors.railway_siding_closing_coal_stock && (
                    <p className="text-sm text-destructive mt-1">{errors.railway_siding_closing_coal_stock}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="coal_dispatch_qty">Coal Dispatch Quantity (MT)</Label>
                  <Input
                    id="coal_dispatch_qty"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.coal_dispatch_qty}
                    onChange={(e) => setData('coal_dispatch_qty', e.target.value)}
                    placeholder="0.00"
                  />
                  {errors.coal_dispatch_qty && (
                    <p className="text-sm text-destructive mt-1">{errors.coal_dispatch_qty}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="no_of_rakes">Number of Rakes</Label>
                  <Input
                    id="no_of_rakes"
                    type="text"
                    value={data.no_of_rakes}
                    onChange={(e) => setData('no_of_rakes', e.target.value)}
                    placeholder="e.g., 5"
                  />
                  {errors.no_of_rakes && (
                    <p className="text-sm text-destructive mt-1">{errors.no_of_rakes}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="rakes_qty">Rakes Quantity (MT)</Label>
                  <Input
                    id="rakes_qty"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.rakes_qty}
                    onChange={(e) => setData('rakes_qty', e.target.value)}
                    placeholder="0.00"
                  />
                  {errors.rakes_qty && (
                    <p className="text-sm text-destructive mt-1">{errors.rakes_qty}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="source">Source</Label>
                  <Select
                    value={data.source}
                    onValueChange={(value) => setData('source', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select source" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="manual">Manual</SelectItem>
                      <SelectItem value="system">System</SelectItem>
                    </SelectContent>
                  </Select>
                  {errors.source && (
                    <p className="text-sm text-destructive mt-1">{errors.source}</p>
                  )}
                </div>
              </div>

              <div>
                <Label htmlFor="remarks">Remarks</Label>
                <textarea
                  id="remarks"
                  value={data.remarks}
                  onChange={(e) => setData('remarks', e.target.value)}
                  placeholder="Add any additional notes..."
                  rows={3}
                  className={textareaClassName}
                />
                {errors.remarks && (
                  <p className="text-sm text-destructive mt-1">{errors.remarks}</p>
                )}
              </div>

              <div className="flex justify-end space-x-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
                <Button type="submit" disabled={processing} data-pan="daily-stock-details-store">
                  Create Record
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
