import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

interface SidingRow {
  id: number;
  name: string;
  opening_balance_mt: number;
}

interface Props {
  sidings: SidingRow[];
}

export default function Index({ sidings }: Props) {
  return (
    <AppLayout>
      <Head title="Opening Coal Stock" />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Opening Coal Stock</h1>
          <p className="text-muted-foreground">Set initial opening balance per siding (used when no ledger history exists)</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Opening balance by siding</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Siding</TableHead>
                  <TableHead>Opening balance (MT)</TableHead>
                  <TableHead className="w-[100px]">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {sidings.map((siding) => (
                  <TableRow key={siding.id}>
                    <TableCell className="font-medium">{siding.name}</TableCell>
                    <TableCell>{Number(siding.opening_balance_mt).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Link href={`/master-data/opening-coal-stock/${siding.id}/edit`}>
                          <Button variant="outline" size="sm" data-pan="opening-coal-stock-edit">
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
      </div>
    </AppLayout>
  );
}
