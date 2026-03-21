import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface Organization {
  id: number;
  name: string;
}

interface Siding {
  id: number;
  organization_id: number;
  name: string;
  code: string;
  location: string;
  station_code: string;
  is_active: boolean;
  organization: Organization;
  created_at: string;
  updated_at: string;
}

interface Props {
  siding: Siding;
}

export default function Show({ siding }: Props) {
  return (
    <AppLayout>
      <Head title={`Siding - ${siding.name}`} />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">{siding.name}</h1>
            <p className="text-muted-foreground">Siding details</p>
          </div>

          <div className="flex space-x-2">
            <Link href={`/master-data/sidings/${siding.id}/edit`}>
              <Button>Edit</Button>
            </Link>
            <Link href="/master-data/sidings">
              <Button variant="outline">Back to List</Button>
            </Link>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Siding Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Code</h3>
                <p className="text-lg font-semibold">{siding.code}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Organization</h3>
                <p className="text-lg font-semibold">{siding.organization?.name}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Location</h3>
                <p className="text-lg font-semibold">{siding.location}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Station Code</h3>
                <p className="text-lg font-semibold">{siding.station_code}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Status</h3>
                <div className="mt-1">
                  <Badge variant={siding.is_active ? 'default' : 'secondary'}>
                    {siding.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Created</h3>
                <p className="text-lg font-semibold">
                  {new Date(siding.created_at).toLocaleDateString()}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Last Updated</h3>
                <p className="text-lg font-semibold">
                  {new Date(siding.updated_at).toLocaleDateString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

