import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

interface Distance {
  id: number;
  power_plant: {
    id: number;
    name: string;
    code: string;
  };
  siding: {
    id: number;
    name: string;
    code: string;
  };
  distance_km: number;
  created_at: string;
  updated_at: string;
}

interface Props {
  distances: Distance[];
}

export default function Index({ distances }: Props) {
  return (
    <AppLayout>
      <Head title="Distance Matrix" />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Distance Matrix</h1>
            <p className="text-muted-foreground">Manage power plant to siding distances</p>
          </div>
          <Link href="/master-data/distance-matrix/create">
            <Button>Add Distance</Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>All Distances</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Power Plant</TableHead>
                  <TableHead>Siding</TableHead>
                  <TableHead>Distance (km)</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {distances.map((distance) => (
                  <TableRow key={distance.id}>
                    <TableCell className="font-medium">
                      {distance.power_plant.name} ({distance.power_plant.code})
                    </TableCell>
                    <TableCell>
                      {distance.siding.name} ({distance.siding.code})
                    </TableCell>
                    <TableCell>{distance.distance_km}</TableCell>
                    <TableCell>
                      <div className="flex space-x-2">
                        <Link href={`/master-data/distance-matrix/${distance.id}`}>
                          <Button variant="outline" size="sm">View</Button>
                        </Link>
                        <Link href={`/master-data/distance-matrix/${distance.id}/edit`}>
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
