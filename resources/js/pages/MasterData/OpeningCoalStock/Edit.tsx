import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

interface Siding {
  id: number;
  name: string;
}

interface OpeningBalance {
  id: number;
  opening_balance_mt: number;
  as_of_date: string | null;
  remarks: string | null;
}

interface Props {
  siding: Siding;
  openingBalance: OpeningBalance;
}

export default function Edit({ siding, openingBalance }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    opening_balance_mt: String(openingBalance.opening_balance_mt ?? 0),
    as_of_date: openingBalance.as_of_date ?? '',
    remarks: openingBalance.remarks ?? '',
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/master-data/opening-coal-stock/${siding.id}`);
  }

  return (
    <AppLayout>
      <Head title={`Opening coal stock – ${siding.name}`} />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Edit opening coal stock</h1>
          <p className="text-muted-foreground">Siding: {siding.name}</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Opening balance</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <Label htmlFor="opening_balance_mt">Opening balance (MT)</Label>
                <Input
                  id="opening_balance_mt"
                  type="number"
                  step="0.01"
                  min="0"
                  value={data.opening_balance_mt}
                  onChange={(e) => setData('opening_balance_mt', e.target.value)}
                  required
                />
                {errors.opening_balance_mt && (
                  <p className="text-sm text-destructive mt-1">{errors.opening_balance_mt}</p>
                )}
              </div>
              <div>
                <Label htmlFor="as_of_date">As of date (optional)</Label>
                <Input
                  id="as_of_date"
                  type="date"
                  value={data.as_of_date}
                  onChange={(e) => setData('as_of_date', e.target.value)}
                />
                {errors.as_of_date && <p className="text-sm text-destructive mt-1">{errors.as_of_date}</p>}
              </div>
              <div>
                <Label htmlFor="remarks">Remarks (optional)</Label>
                <Input
                  id="remarks"
                  type="text"
                  value={data.remarks}
                  onChange={(e) => setData('remarks', e.target.value)}
                  placeholder="e.g. Go-live initial stock"
                />
                {errors.remarks && <p className="text-sm text-destructive mt-1">{errors.remarks}</p>}
              </div>

              <div className="flex justify-end space-x-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                  Update opening coal stock
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
