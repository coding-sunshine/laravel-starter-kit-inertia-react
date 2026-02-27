import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

interface Loader {
  id: number;
  loader_name: string;
  code: string;
  loader_type: string;
  make_model: string | null;
  capacity_mt: number | null;
  last_calibration_date: string | null;
  is_active: boolean;
  siding: {
    id: number;
    name: string;
    code: string;
  };
  created_at: string;
  updated_at: string;
}

interface Props {
  loaders: Loader[];
}

export default function Index({ loaders }: Props) {
  return (
    <AppLayout>
      <Head title="Loaders" />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Loaders</h1>
            <p className="text-muted-foreground">Manage loader configurations</p>
          </div>
          <Link href="/master-data/loaders/create">
            <Button>Add Loader</Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>All Loaders</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Code</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Capacity (MT)</TableHead>
                  <TableHead>Siding</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loaders.map((loader) => (
                  <TableRow key={loader.id}>
                    <TableCell className="font-medium">{loader.loader_name}</TableCell>
                    <TableCell>{loader.code}</TableCell>
                    <TableCell>{loader.loader_type}</TableCell>
                    <TableCell>{loader.capacity_mt || '-'}</TableCell>
                    <TableCell>{loader.siding.name}</TableCell>
                    <TableCell>
                      <Badge variant={loader.is_active ? 'default' : 'secondary'}>
                        {loader.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex space-x-2">
                        <Link href={`/master-data/loaders/${loader.id}`}>
                          <Button variant="outline" size="sm">View</Button>
                        </Link>
                        <Link href={`/master-data/loaders/${loader.id}/edit`}>
                          <Button variant="outline" size="sm">Edit</Button>
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
