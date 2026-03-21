import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface PenaltyType {
  id: number;
  code: string;
  name: string;
  category: string;
  calculation_type: string;
  description: string | null;
  default_rate: number | string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface Props {
  penaltyType: PenaltyType;
}

export default function Show({ penaltyType }: Props) {
  const categoryLabel = penaltyType.category.replace('_', ' ');
  const calculationTypeLabel = penaltyType.calculation_type.replace('_', ' ');
  const defaultRateLabel =
    penaltyType.default_rate != null ? `₹${penaltyType.default_rate}` : '-';

  return (
    <AppLayout>
      <Head title={`Penalty Type - ${penaltyType.name}`} />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">{penaltyType.name}</h1>
            <p className="text-muted-foreground">Penalty type details</p>
          </div>

          <div className="flex space-x-2">
            <Link href={`/master-data/penalty-types/${penaltyType.id}/edit`}>
              <Button>Edit</Button>
            </Link>
            <Link href="/master-data/penalty-types">
              <Button variant="outline">Back to List</Button>
            </Link>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Penalty Type Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Code</h3>
                <p className="text-lg font-semibold">{penaltyType.code}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Category</h3>
                <div className="mt-1">
                  <Badge variant="outline">{categoryLabel}</Badge>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Calculation Type</h3>
                <div className="mt-1">
                  <Badge variant="secondary">{calculationTypeLabel}</Badge>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Default Rate</h3>
                <p className="text-lg font-semibold">{defaultRateLabel}</p>
              </div>

              <div className="md:col-span-2">
                <h3 className="text-sm font-medium text-muted-foreground">Description</h3>
                <p className="text-lg font-semibold">
                  {penaltyType.description ?? 'Not specified'}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Status</h3>
                <div className="mt-1">
                  <Badge variant={penaltyType.is_active ? 'default' : 'secondary'}>
                    {penaltyType.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Created</h3>
                <p className="text-lg font-semibold">
                  {new Date(penaltyType.created_at).toLocaleDateString()}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Last Updated</h3>
                <p className="text-lg font-semibold">
                  {new Date(penaltyType.updated_at).toLocaleDateString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

