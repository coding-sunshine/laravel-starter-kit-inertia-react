import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';

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

export default function Edit({ powerPlant }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    name: powerPlant.name,
    code: powerPlant.code,
    location: powerPlant.location || '',
    is_active: powerPlant.is_active,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/master-data/power-plants/${powerPlant.id}`);
  }

  return (
    <AppLayout>
      <Head title="Edit Power Plant" />
      
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Edit Power Plant</h1>
          <p className="text-muted-foreground">Update power plant information</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Power Plant Details</CardTitle>
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
                    placeholder="e.g., PSPM"
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
                    placeholder="e.g., PSPM"
                    maxLength={20}
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
                  />
                  {errors.location && <p className="text-sm text-red-600 mt-1">{errors.location}</p>}
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
                  Update Power Plant
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
