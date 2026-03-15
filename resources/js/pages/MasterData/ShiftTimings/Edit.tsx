import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

interface Shift {
  id: number;
  shift_name: string;
  start_time: string;
  end_time: string;
  sort_order: number;
}

interface Siding {
  id: number;
  name: string;
  shifts: Shift[];
}

interface Props {
  siding: Siding;
}

function toHHmm(s: string): string {
  if (!s) return '';
  const match = s.match(/^(\d{1,2}):(\d{2})/);
  if (match) return `${match[1].padStart(2, '0')}:${match[2]}`;
  try {
    const d = new Date(s);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
  } catch {
    return '';
  }
}

export default function Edit({ siding }: Props) {
  const shifts = siding.shifts ?? [];
  const defaultShifts = [
    { start_time: toHHmm(shifts[0]?.start_time ?? ''), end_time: toHHmm(shifts[0]?.end_time ?? '') },
    { start_time: toHHmm(shifts[1]?.start_time ?? ''), end_time: toHHmm(shifts[1]?.end_time ?? '') },
    { start_time: toHHmm(shifts[2]?.start_time ?? ''), end_time: toHHmm(shifts[2]?.end_time ?? '') },
  ];

  const { data, setData, put, processing, errors } = useForm({
    shifts: defaultShifts,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/master-data/shift-timings/${siding.id}`);
  }

  function setShiftTime(index: number, field: 'start_time' | 'end_time', value: string) {
    setData('shifts', (prev) => {
      const next = prev.map((s, i) => (i === index ? { ...s, [field]: value } : s));
      return next;
    });
  }

  return (
    <AppLayout>
      <Head title={`Edit shift timings – ${siding.name}`} />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Edit shift timings</h1>
          <p className="text-muted-foreground">Siding: {siding.name}</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Shift time ranges</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              {[0, 1, 2].map((index) => (
                <div key={index} className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg">
                  <h3 className="md:col-span-2 font-medium">
                    {index === 0 ? '1st' : index === 1 ? '2nd' : '3rd'} shift
                  </h3>
                  <div>
                    <Label htmlFor={`start_${index}`}>Start time</Label>
                    <Input
                      id={`start_${index}`}
                      type="time"
                      value={data.shifts[index]?.start_time ?? ''}
                      onChange={(e) => setShiftTime(index, 'start_time', e.target.value)}
                      required
                    />
                    {errors[`shifts.${index}.start_time`] && (
                      <p className="text-sm text-destructive mt-1">{errors[`shifts.${index}.start_time`]}</p>
                    )}
                  </div>
                  <div>
                    <Label htmlFor={`end_${index}`}>End time</Label>
                    <Input
                      id={`end_${index}`}
                      type="time"
                      value={data.shifts[index]?.end_time ?? ''}
                      onChange={(e) => setShiftTime(index, 'end_time', e.target.value)}
                      required
                    />
                    {errors[`shifts.${index}.end_time`] && (
                      <p className="text-sm text-destructive mt-1">{errors[`shifts.${index}.end_time`]}</p>
                    )}
                  </div>
                </div>
              ))}

              <div className="flex justify-end space-x-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                  Update shift timings
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
