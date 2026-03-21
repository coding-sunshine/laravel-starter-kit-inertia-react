import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface Siding {
  id: number;
  name: string;
  code: string;
}

interface Loader {
  id: number;
  siding_id: number;
  loader_name: string;
  code: string;
  loader_type: string;
  make_model: string | null;
  capacity_mt: number | string | null;
  last_calibration_date: string | null;
  is_active: boolean;
  siding: Siding;
  created_at: string;
  updated_at: string;
}

interface Props {
  loader: Loader;
}

export default function Show({ loader }: Props) {
  return (
    <AppLayout>
      <Head title={`Loader - ${loader.loader_name}`} />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">{loader.loader_name}</h1>
            <p className="text-muted-foreground">Loader details</p>
          </div>

          <div className="flex space-x-2">
            <Link href={`/master-data/loaders/${loader.id}/edit`}>
              <Button>Edit</Button>
            </Link>
            <Link href="/master-data/loaders">
              <Button variant="outline">Back to List</Button>
            </Link>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Loader Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Code</h3>
                <p className="text-lg font-semibold">{loader.code}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Loader Type</h3>
                <p className="text-lg font-semibold">{loader.loader_type}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Siding</h3>
                <p className="text-lg font-semibold">
                  {loader.siding?.name} ({loader.siding?.code})
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Make/Model</h3>
                <p className="text-lg font-semibold">{loader.make_model ?? 'Not specified'}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Capacity (MT)</h3>
                <p className="text-lg font-semibold">
                  {loader.capacity_mt != null ? loader.capacity_mt : 'Not specified'}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">
                  Last Calibration Date
                </h3>
                <p className="text-lg font-semibold">
                  {loader.last_calibration_date ?? 'Not specified'}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Status</h3>
                <div className="mt-1">
                  <Badge variant={loader.is_active ? 'default' : 'secondary'}>
                    {loader.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Created</h3>
                <p className="text-lg font-semibold">
                  {new Date(loader.created_at).toLocaleDateString()}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Last Updated</h3>
                <p className="text-lg font-semibold">
                  {new Date(loader.updated_at).toLocaleDateString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

