import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

interface PenaltyType {
  id: number;
  code: string;
  name: string;
  category: string;
  calculation_type: string;
  description: string | null;
  default_rate: number | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface Props {
  penaltyTypes: PenaltyType[];
}

export default function Index({ penaltyTypes }: Props) {
  return (
    <AppLayout>
      <Head title="Penalty Types" />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Penalty Types</h1>
            <p className="text-muted-foreground">Manage penalty type configurations</p>
          </div>
          <Link href="/master-data/penalty-types/create">
            <Button>Add Penalty Type</Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>All Penalty Types</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Code</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Calculation Type</TableHead>
                  <TableHead>Default Rate</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {penaltyTypes.map((type) => (
                  <TableRow key={type.id}>
                    <TableCell className="font-medium">{type.code}</TableCell>
                    <TableCell>{type.name}</TableCell>
                    <TableCell>
                      <Badge variant="outline">
                        {type.category.replace('_', ' ')}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant="secondary">
                        {type.calculation_type.replace('_', ' ')}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {type.default_rate ? `₹${type.default_rate}` : '-'}
                    </TableCell>
                    <TableCell>
                      <Badge variant={type.is_active ? 'default' : 'secondary'}>
                        {type.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex space-x-2">
                        <Link href={`/master-data/penalty-types/${type.id}`}>
                          <Button variant="outline" size="sm">View</Button>
                        </Link>
                        <Link href={`/master-data/penalty-types/${type.id}/edit`}>
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
