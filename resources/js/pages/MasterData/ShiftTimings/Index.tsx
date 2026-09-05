import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
  sidings: Siding[];
}

function formatTime(s: string): string {
  if (!s) return '—';
  const match = s.match(/^(\d{1,2}):(\d{2})/);
  if (match) return `${match[1].padStart(2, '0')}:${match[2]}`;
  try {
    const d = new Date(s);
    return d.toTimeString().slice(0, 5);
  } catch {
    return s.slice(0, 5);
  }
}

export default function Index({ sidings }: Props) {
  return (
    <AppLayout>
      <Head title="Shift Timings" />

      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Shift Timings</h1>
          <p className="text-muted-foreground">Manage shift start and end times per siding</p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Shift timings by siding</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Siding</TableHead>
                  <TableHead>1st Shift</TableHead>
                  <TableHead>2nd Shift</TableHead>
                  <TableHead>3rd Shift</TableHead>
                  <TableHead className="w-[100px]">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {sidings.map((siding) => {
                  const [s1, s2, s3] = siding.shifts ?? [];
                  return (
                    <TableRow key={siding.id}>
                      <TableCell className="font-medium">{siding.name}</TableCell>
                      <TableCell>
                        {s1 ? `${formatTime(s1.start_time)} – ${formatTime(s1.end_time)}` : '—'}
                      </TableCell>
                      <TableCell>
                        {s2 ? `${formatTime(s2.start_time)} – ${formatTime(s2.end_time)}` : '—'}
                      </TableCell>
                      <TableCell>
                        {s3 ? `${formatTime(s3.start_time)} – ${formatTime(s3.end_time)}` : '—'}
                      </TableCell>
                      <TableCell>
                        <Link href={`/master-data/shift-timings/${siding.id}/edit`}>
                          <Button variant="outline" size="sm" data-pan="shift-timings-edit">
                            Edit
                          </Button>
                        </Link>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
