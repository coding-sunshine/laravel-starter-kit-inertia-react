import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';

interface Props {
  categories: Record<string, string>;
  calculationTypes: Record<string, string>;
}

export default function Create({ categories, calculationTypes }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    code: '',
    name: '',
    category: '',
    calculation_type: '',
    description: '',
    default_rate: '',
    is_active: true,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    post('/master-data/penalty-types');
  }

  return (
    <AppLayout>
      <Head title="Create Penalty Type" />
      
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Create Penalty Type</h1>
          <p className="text-muted-foreground">Add a new penalty type to the system</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Penalty Type Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label htmlFor="code">Code</Label>
                  <Input
                    id="code"
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value)}
                    placeholder="e.g., POL1"
                    maxLength={10}
                    required
                  />
                  {errors.code && <p className="text-sm text-red-600 mt-1">{errors.code}</p>}
                </div>

                <div>
                  <Label htmlFor="name">Name</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="e.g., Punitive Overloading (Individual Wagon)"
                    required
                  />
                  {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                </div>

                <div>
                  <Label htmlFor="category">Category</Label>
                  <Select value={data.category} onValueChange={(value) => setData('category', value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select category" />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(categories).map(([key, value]) => (
                        <SelectItem key={key} value={key}>
                          {value}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.category && <p className="text-sm text-red-600 mt-1">{errors.category}</p>}
                </div>

                <div>
                  <Label htmlFor="calculation_type">Calculation Type</Label>
                  <Select value={data.calculation_type} onValueChange={(value) => setData('calculation_type', value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select calculation type" />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(calculationTypes).map(([key, value]) => (
                        <SelectItem key={key} value={key}>
                          {value}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.calculation_type && <p className="text-sm text-red-600 mt-1">{errors.calculation_type}</p>}
                </div>

                <div>
                  <Label htmlFor="default_rate">Default Rate</Label>
                  <Input
                    id="default_rate"
                    type="number"
                    step="0.01"
                    value={data.default_rate}
                    onChange={(e) => setData('default_rate', e.target.value)}
                    placeholder="e.g., 100.00"
                  />
                  {errors.default_rate && <p className="text-sm text-red-600 mt-1">{errors.default_rate}</p>}
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

              <div>
                <Label htmlFor="description">Description</Label>
                <Input
                  id="description"
                  value={data.description}
                  onChange={(e) => setData('description', e.target.value)}
                  placeholder="Describe the penalty type..."
                />
                {errors.description && <p className="text-sm text-red-600 mt-1">{errors.description}</p>}
              </div>

              <div className="flex justify-end space-x-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                  Create Penalty Type
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
