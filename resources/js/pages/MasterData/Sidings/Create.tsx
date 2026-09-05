import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';

interface OrganizationOption {
  id: number;
  name: string;
}

export default function Create() {
  const { auth } = usePage<SharedData>().props;
  const organizations: OrganizationOption[] = (auth.organizations ?? []) as OrganizationOption[];
  const defaultOrganizationId = auth.current_organization?.id ?? organizations[0]?.id;

  const { data, setData, post, processing, errors } = useForm({
    organization_id: defaultOrganizationId ? defaultOrganizationId.toString() : '',
    name: '',
    code: '',
    location: '',
    station_code: '',
    is_active: true,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    post('/master-data/sidings');
  }

  return (
    <AppLayout>
      <Head title="Create Siding" />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Create Siding</h1>
          <p className="text-muted-foreground">Add a new siding configuration</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Siding Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {organizations.length > 0 && (
                  <div>
                    <Label htmlFor="organization_id">Organization</Label>
                    <Select
                      value={data.organization_id}
                      onValueChange={(value) => setData('organization_id', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select organization" />
                      </SelectTrigger>
                      <SelectContent>
                        {organizations.map((org) => (
                          <SelectItem key={org.id} value={org.id.toString()}>
                            {org.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.organization_id && (
                      <p className="text-sm text-red-600 mt-1">{errors.organization_id}</p>
                    )}
                  </div>
                )}

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
                  Create Siding
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}

