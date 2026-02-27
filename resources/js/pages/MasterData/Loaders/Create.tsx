import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';

interface Siding {
  id: number;
  name: string;
  code: string;
}

interface Props {
  sidings: Siding[];
}

export default function Create({ sidings }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    siding_id: '',
    loader_name: '',
    code: '',
    loader_type: '',
    make_model: '',
    capacity_mt: '',
    last_calibration_date: '',
    is_active: true,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    post('/master-data/loaders');
  }

  return (
    <AppLayout>
      <Head title="Create Loader" />
      
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Create Loader</h1>
          <p className="text-muted-foreground">Add a new loader to the system</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Loader Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label htmlFor="siding_id">Siding</Label>
                  <Select value={data.siding_id} onValueChange={(value) => setData('siding_id', value)}>
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
                  {errors.siding_id && <p className="text-sm text-red-600 mt-1">{errors.siding_id}</p>}
                </div>

                <div>
                  <Label htmlFor="loader_name">Loader Name</Label>
                  <Input
                    id="loader_name"
                    value={data.loader_name}
                    onChange={(e) => setData('loader_name', e.target.value)}
                    placeholder="e.g., Loader 16"
                    required
                  />
                  {errors.loader_name && <p className="text-sm text-red-600 mt-1">{errors.loader_name}</p>}
                </div>

                <div>
                  <Label htmlFor="code">Code</Label>
                  <Input
                    id="code"
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    placeholder="e.g., L16"
                    maxLength={10}
                    required
                  />
                  {errors.code && <p className="text-sm text-red-600 mt-1">{errors.code}</p>}
                </div>

                <div>
                  <Label htmlFor="loader_type">Loader Type</Label>
                  <Input
                    id="loader_type"
                    value={data.loader_type}
                    onChange={(e) => setData('loader_type', e.target.value)}
                    placeholder="e.g., Backhoe"
                    required
                  />
                  {errors.loader_type && <p className="text-sm text-red-600 mt-1">{errors.loader_type}</p>}
                </div>

                <div>
                  <Label htmlFor="make_model">Make/Model</Label>
                  <Input
                    id="make_model"
                    value={data.make_model}
                    onChange={(e) => setData('make_model', e.target.value)}
                    placeholder="e.g., JCB 3DX"
                  />
                  {errors.make_model && <p className="text-sm text-red-600 mt-1">{errors.make_model}</p>}
                </div>

                <div>
                  <Label htmlFor="capacity_mt">Capacity (MT)</Label>
                  <Input
                    id="capacity_mt"
                    type="number"
                    step="0.01"
                    value={data.capacity_mt}
                    onChange={(e) => setData('capacity_mt', e.target.value)}
                    placeholder="e.g., 2.5"
                  />
                  {errors.capacity_mt && <p className="text-sm text-red-600 mt-1">{errors.capacity_mt}</p>}
                </div>

                <div>
                  <Label htmlFor="last_calibration_date">Last Calibration Date</Label>
                  <Input
                    id="last_calibration_date"
                    type="date"
                    value={data.last_calibration_date}
                    onChange={(e) => setData('last_calibration_date', e.target.value)}
                  />
                  {errors.last_calibration_date && <p className="text-sm text-red-600 mt-1">{errors.last_calibration_date}</p>}
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
                  Create Loader
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
