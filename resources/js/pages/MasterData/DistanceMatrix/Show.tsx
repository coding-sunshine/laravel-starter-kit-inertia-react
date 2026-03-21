import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface PowerPlant {
  id: number;
  name: string;
  code: string;
}

interface Siding {
  id: number;
  name: string;
  code: string;
}

interface Distance {
  id: number;
  power_plant_id: number;
  siding_id: number;
  distance_km: number | string;
  /** Laravel serializes relation keys as snake_case. */
  power_plant?: PowerPlant | null;
  siding?: Siding | null;
  created_at: string;
  updated_at: string;
}

interface Props {
  distance: Distance;
}

export default function Show({ distance }: Props) {
  return (
    <AppLayout>
      <Head title="Distance Matrix - Details" />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">
              {distance.powerPlant ? distance.powerPlant.name : '-'} to{' '}
              {distance.siding ? distance.siding.name : '-'}
            </h1>
            <p className="text-muted-foreground">Distance matrix entry details</p>
          </div>

          <div className="flex space-x-2">
            <Link href={`/master-data/distance-matrix/${distance.id}/edit`}>
              <Button>Edit</Button>
            </Link>
            <Link href="/master-data/distance-matrix">
              <Button variant="outline">Back to List</Button>
            </Link>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Distance Matrix Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Power Plant</h3>
                <p className="text-lg font-semibold">
                  {distance.power_plant
                    ? `${distance.power_plant.name} (${distance.power_plant.code})`
                    : '-'}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Siding</h3>
                <p className="text-lg font-semibold">
                  {distance.siding ? `${distance.siding.name} (${distance.siding.code})` : '-'}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Distance (km)</h3>
                <p className="text-lg font-semibold">{distance.distance_km}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Created</h3>
                <p className="text-lg font-semibold">
                  {new Date(distance.created_at).toLocaleDateString()}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Last Updated</h3>
                <p className="text-lg font-semibold">
                  {new Date(distance.updated_at).toLocaleDateString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

