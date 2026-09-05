import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

interface Siding {
  id: number;
  name: string;
  code: string;
  location: string;
  station_code: string;
  is_active: boolean;
  organization: {
    id: number;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

interface Props {
  sidings: Siding[];
}

export default function Index({ sidings }: Props) {
  return (
    <AppLayout>
      <Head title="Sidings" />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Sidings</h1>
            <p className="text-muted-foreground">Manage siding configurations</p>
          </div>
          <Link href="/master-data/sidings/create">
            <Button>Add Siding</Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>All Sidings</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Code</TableHead>
                  <TableHead>Location</TableHead>
                  <TableHead>Station Code</TableHead>
                  <TableHead>Organization</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {sidings.map((siding) => (
                  <TableRow key={siding.id}>
                    <TableCell className="font-medium">{siding.name}</TableCell>
                    <TableCell>{siding.code}</TableCell>
                    <TableCell>{siding.location}</TableCell>
                    <TableCell>{siding.station_code}</TableCell>
                    <TableCell>{siding.organization.name}</TableCell>
                    <TableCell>
                      <Badge variant={siding.is_active ? 'default' : 'secondary'}>
                        {siding.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex space-x-2">
                        <Link href={`/master-data/sidings/${siding.id}`}>
                          <Button variant="outline" size="sm">View</Button>
                        </Link>
                        <Link href={`/master-data/sidings/${siding.id}/edit`}>
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
