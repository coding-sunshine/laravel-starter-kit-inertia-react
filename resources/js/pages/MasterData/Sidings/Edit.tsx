import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

interface Siding {
  id: number;
  organization_id: number;
  name: string;
  code: string;
  location: string;
  station_code: string;
  is_active: boolean;
}

interface Props {
  siding: Siding;
}

export default function Edit({ siding }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    organization_id: siding.organization_id.toString(),
    name: siding.name,
    code: siding.code,
    location: siding.location,
    station_code: siding.station_code,
    is_active: siding.is_active,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/master-data/sidings/${siding.id}`);
  }

  return (
    <AppLayout>
      <Head title={`Edit Siding - ${siding.name}`} />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Edit Siding</h1>
          <p className="text-muted-foreground">Update siding information</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Siding Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label htmlFor="name">Name</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="e.g., Pakur"
                    required
                  />
                  {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                </div>

                <div>
                  <Label htmlFor="code">Code</Label>
                  <Input
                    id="code"
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    placeholder="e.g., PKR"
                    maxLength={10}
                    required
                  />
                  {errors.code && <p className="text-sm text-red-600 mt-1">{errors.code}</p>}
                </div>

                <div>
                  <Label htmlFor="location">Location</Label>
                  <Input
                    id="location"
                    value={data.location}
                    onChange={(e) => setData('location', e.target.value)}
                    placeholder="e.g., Pakur"
                    required
                  />
                  {errors.location && (
                    <p className="text-sm text-red-600 mt-1">{errors.location}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="station_code">Station Code</Label>
                  <Input
                    id="station_code"
                    value={data.station_code}
                    onChange={(e) => setData('station_code', e.target.value)}
                    placeholder="e.g., PAK"
                    maxLength={10}
                    required
                  />
                  {errors.station_code && (
                    <p className="text-sm text-red-600 mt-1">{errors.station_code}</p>
                  )}
                </div>

                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                  />
                  <Label htmlFor="is_active">Active</Label>
                </div>
              </div>

              <div className="flex justify-end space-x-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                  Update Siding
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

