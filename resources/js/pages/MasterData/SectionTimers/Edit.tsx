import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';

interface SectionTimer {
  id: number;
  section_name: string;
  free_minutes: number;
  warning_minutes: number;
  penalty_applicable: boolean;
  created_at: string;
  updated_at: string;
}

interface Props {
  sectionTimer: SectionTimer;
}

export default function Edit({ sectionTimer }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    section_name: sectionTimer.section_name,
    free_minutes: String(sectionTimer.free_minutes),
    warning_minutes: String(sectionTimer.warning_minutes),
    penalty_applicable: sectionTimer.penalty_applicable,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/master-data/section-timers/${sectionTimer.id}`);
  }

  const sections = [
    { value: 'loading', label: 'Loading' },
    { value: 'guard', label: 'Guard' },
    { value: 'weighment', label: 'Weighment' },
  ];

  return (
    <AppLayout>
      <Head title="Edit Section Timer" />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Edit Section Timer</h1>
          <p className="text-muted-foreground">Update time limits for this workflow section</p>
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
                  {errors.warning_minutes && (
                    <p className="text-sm text-red-600 mt-1">{errors.warning_minutes}</p>
                  )}
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
                  Update Section Timer
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
