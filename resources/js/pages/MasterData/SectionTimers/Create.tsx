import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';

export default function Create() {
  const { data, setData, post, processing, errors } = useForm({
    section_name: '',
    free_minutes: '',
    warning_minutes: '',
    penalty_applicable: true,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    post('/master-data/section-timers');
  }

  const sections = [
    { value: 'loading', label: 'Loading' },
    { value: 'guard', label: 'Guard' },
    { value: 'weighment', label: 'Weighment' },
  ];

  return (
    <AppLayout>
      <Head title="Create Section Timer" />
      
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Create Section Timer</h1>
          <p className="text-muted-foreground">Configure time limits for a workflow section</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Section Timer Details</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label htmlFor="section_name">Section Name</Label>
                  <Select value={data.section_name} onValueChange={(value) => setData('section_name', value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select section" />
                    </SelectTrigger>
                    <SelectContent>
                      {sections.map((section) => (
                        <SelectItem key={section.value} value={section.value}>
                          {section.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.section_name && <p className="text-sm text-red-600 mt-1">{errors.section_name}</p>}
                </div>

                <div>
                  <Label htmlFor="free_minutes">Free Time (minutes)</Label>
                  <Input
                    id="free_minutes"
                    type="number"
                    value={data.free_minutes}
                    onChange={(e) => setData('free_minutes', e.target.value)}
                    placeholder="e.g., 180"
                    required
                  />
                  {errors.free_minutes && <p className="text-sm text-red-600 mt-1">{errors.free_minutes}</p>}
                </div>

                <div>
                  <Label htmlFor="warning_minutes">Warning Time (minutes)</Label>
                  <Input
                    id="warning_minutes"
                    type="number"
                    value={data.warning_minutes}
                    onChange={(e) => setData('warning_minutes', e.target.value)}
                    placeholder="e.g., 150"
                    required
                  />
                  {errors.warning_minutes && <p className="text-sm text-red-600 mt-1">{errors.warning_minutes}</p>}
                </div>

                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="penalty_applicable"
                    checked={data.penalty_applicable}
                    onCheckedChange={(checked) => setData('penalty_applicable', checked as boolean)}
                  />
                  <Label htmlFor="penalty_applicable">Penalty Applicable</Label>
                </div>
              </div>

              <div className="flex justify-end space-x-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                  Create Section Timer
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
