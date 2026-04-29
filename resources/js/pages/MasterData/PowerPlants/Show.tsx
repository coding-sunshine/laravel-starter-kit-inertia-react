import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { index, show } from '@/routes/master-data/power-plants';

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
  powerPlant: PowerPlant;
}

export default function Show({ powerPlant }: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
      { title: 'Master Data', href: index.url() },
      { title: 'Power Plants', href: index.url() },
      { title: powerPlant.name, href: show.url(powerPlant.id) },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Power Plant - ${powerPlant.name}`} />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">{powerPlant.name}</h1>
            <p className="text-muted-foreground">Power plant details</p>
          </div>
          <div className="flex space-x-2">
            <Link href={`/master-data/power-plants/${powerPlant.id}/edit`}>
              <Button>Edit</Button>
            </Link>
            <Link href="/master-data/power-plants">
              <Button variant="outline">Back to List</Button>
            </Link>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Power Plant Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Name</h3>
                <p className="text-lg font-semibold">{powerPlant.name}</p>
              </div>
              
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Code</h3>
                <p className="text-lg font-semibold">{powerPlant.code}</p>
              </div>
              
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Location</h3>
                <p className="text-lg font-semibold">{powerPlant.location || 'Not specified'}</p>
              </div>
              
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Status</h3>
                <div className="mt-1">
                  <Badge variant={powerPlant.is_active ? 'default' : 'secondary'}>
                    {powerPlant.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </div>
              </div>
              
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Created</h3>
                <p className="text-lg font-semibold">{new Date(powerPlant.created_at).toLocaleDateString()}</p>
              </div>
              
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Last Updated</h3>
                <p className="text-lg font-semibold">{new Date(powerPlant.updated_at).toLocaleDateString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
