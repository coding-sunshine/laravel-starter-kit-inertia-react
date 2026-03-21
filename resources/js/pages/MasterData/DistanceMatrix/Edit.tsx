import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
}

interface Props {
  distance: Distance;
  powerPlants: PowerPlant[];
  sidings: Siding[];
}

export default function Edit({ distance, powerPlants, sidings }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    power_plant_id: distance.power_plant_id.toString(),
    siding_id: distance.siding_id.toString(),
    distance_km: String(distance.distance_km),
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/master-data/distance-matrix/${distance.id}`);
  }

  return (
    <AppLayout>
      <Head title="Edit Distance" />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Edit Distance</h1>
          <p className="text-muted-foreground">Update power plant to siding distance</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Distance Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {errors.combination && (
                <p className="text-sm text-red-600">{errors.combination}</p>
              )}

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <Label htmlFor="power_plant_id">Power Plant</Label>
                  <Select
                    value={data.power_plant_id === '' ? undefined : data.power_plant_id}
                    onValueChange={(value) => setData('power_plant_id', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select power plant" />
                    </SelectTrigger>
                    <SelectContent>
                      {powerPlants.map((plant) => (
                        <SelectItem key={plant.id} value={plant.id.toString()}>
                          {plant.name} ({plant.code})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.power_plant_id && (
                    <p className="text-sm text-red-600 mt-1">{errors.power_plant_id}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="siding_id">Siding</Label>
                  <Select
                    value={data.siding_id === '' ? undefined : data.siding_id}
                    onValueChange={(value) => setData('siding_id', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select siding" />
                    </SelectTrigger>
                    <SelectContent>
                      {sidings.map((siding) => (
                        <SelectItem key={siding.id} value={siding.id.toString()}>
                          {siding.name} ({siding.code})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.siding_id && (
                    <p className="text-sm text-red-600 mt-1">{errors.siding_id}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="distance_km">Distance (km)</Label>
                  <Input
                    id="distance_km"
                    type="number"
                    step="0.01"
                    value={data.distance_km}
                    onChange={(e) => setData('distance_km', e.target.value)}
                    placeholder="e.g., 25.50"
                    required
                  />
                  {errors.distance_km && (
                    <p className="text-sm text-red-600 mt-1">{errors.distance_km}</p>
                  )}
                </div>
              </div>

              <div className="flex justify-end space-x-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                  Update Distance
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

