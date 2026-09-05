import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
  sectionTimers: SectionTimer[];
}

export default function Index({ sectionTimers }: Props) {
  return (
    <AppLayout>
      <Head title="Section Timers" />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold">Section Timers</h1>
            <p className="text-muted-foreground">Manage workflow section time configurations</p>
          </div>
          <Link href="/master-data/section-timers/create">
            <Button>Add Section Timer</Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>All Section Timers</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Section Name</TableHead>
                  <TableHead>Free Time (minutes)</TableHead>
                  <TableHead>Warning Time (minutes)</TableHead>
                  <TableHead>Penalty Applicable</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {sectionTimers.map((timer) => (
                  <TableRow key={timer.id}>
                    <TableCell className="font-medium capitalize">{timer.section_name}</TableCell>
                    <TableCell>{timer.free_minutes}</TableCell>
                    <TableCell>{timer.warning_minutes}</TableCell>
                    <TableCell>
                      <Badge variant={timer.penalty_applicable ? 'default' : 'secondary'}>
                        {timer.penalty_applicable ? 'Yes' : 'No'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex space-x-2">
                        <Link href={`/master-data/section-timers/${timer.id}`}>
                          <Button variant="outline" size="sm">View</Button>
                        </Link>
                        <Link href={`/master-data/section-timers/${timer.id}/edit`}>
                          <Button variant="outline" size="sm">Edit</Button>
                        </Link>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
