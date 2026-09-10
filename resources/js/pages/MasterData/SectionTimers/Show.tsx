import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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

export default function Show({ sectionTimer }: Props) {
  return (
    <AppLayout>
      <Head title={`Section Timer - ${sectionTimer.section_name}`} />

      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold capitalize">{sectionTimer.section_name}</h1>
            <p className="text-muted-foreground">Section timer details</p>
          </div>
          <div className="flex space-x-2">
            <Link href={`/master-data/section-timers/${sectionTimer.id}/edit`}>
              <Button>Edit</Button>
            </Link>
            <Link href="/master-data/section-timers">
              <Button variant="outline">Back to List</Button>
            </Link>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Section Timer Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Section Name</h3>
                <p className="text-lg font-semibold capitalize">{sectionTimer.section_name}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Free Time (minutes)</h3>
                <p className="text-lg font-semibold">{sectionTimer.free_minutes}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Warning Time (minutes)</h3>
                <p className="text-lg font-semibold">{sectionTimer.warning_minutes}</p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Penalty Applicable</h3>
                <div className="mt-1">
                  <Badge variant={sectionTimer.penalty_applicable ? 'default' : 'secondary'}>
                    {sectionTimer.penalty_applicable ? 'Yes' : 'No'}
                  </Badge>
                </div>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Created</h3>
                <p className="text-lg font-semibold">
                  {new Date(sectionTimer.created_at).toLocaleDateString()}
                </p>
              </div>

              <div>
                <h3 className="text-sm font-medium text-muted-foreground">Last Updated</h3>
                <p className="text-lg font-semibold">
                  {new Date(sectionTimer.updated_at).toLocaleDateString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
