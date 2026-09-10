import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { index } from '@/routes/master-data/power-plants';

interface PowerPlant {
  id: number;
  name: string;
  code: string;
  location: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface Props {
  powerPlants: PowerPlant[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: index.url() },
    { title: 'Power Plants', href: index.url() },
];

export default function Index({ powerPlants }: Props) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Power Plants" />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Power Plants</h1>
            <p className="text-muted-foreground">Manage power plant configurations</p>
          </div>
          <Link href="/master-data/power-plants/create">
            <Button>Add Power Plant</Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>All Power Plants</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Code</TableHead>
                  <TableHead>Location</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {powerPlants.map((plant) => (
                  <TableRow key={plant.id}>
                    <TableCell className="font-medium">{plant.name}</TableCell>
                    <TableCell>{plant.code}</TableCell>
                    <TableCell>{plant.location || '-'}</TableCell>
                    <TableCell>
                      <Badge variant={plant.is_active ? 'default' : 'secondary'}>
                        {plant.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex space-x-2">
                        <Link href={`/master-data/power-plants/${plant.id}`}>
                          <Button variant="outline" size="sm">View</Button>
                        </Link>
                        <Link href={`/master-data/power-plants/${plant.id}/edit`}>
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
