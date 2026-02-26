import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar, Plus } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import ShiftTabs from './shift-tabs';
import VehicleEntryTable from './vehicle-entry-table';

interface DailyVehicleEntry {
  id: number;
  siding_id: number;
  entry_date: string;
  shift: number;
  e_challan_no: string | null;
  vehicle_no: string | null;
  gross_wt: number | null;
  tare_wt: number | null;
  reached_at: string;
  wb_no: string | null;
  d_challan_no: string | null;
  challan_mode: 'offline' | 'online' | null;
  status: 'draft' | 'completed';
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
}

interface Props {
  entries: DailyVehicleEntry[];
  date: string;
  activeShift: number;
  shiftSummary: Record<number, number>;
}

export default function DailyVehicleEntriesIndex({ entries, date, activeShift, shiftSummary }: Props) {
  const [selectedDate, setSelectedDate] = useState(date);
  const [activeShiftState, setActiveShiftState] = useState(activeShift);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Road Dispatch', href: '/road-dispatch/daily-vehicle-entries' },
    { title: 'Daily Vehicle Entries', href: '' },
  ];

  const handleDateChange = (newDate: string) => {
    setSelectedDate(newDate);
    router.get(
      '/road-dispatch/daily-vehicle-entries',
      { date: newDate, shift: activeShiftState },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleShiftChange = (shift: number) => {
    setActiveShiftState(shift);
    router.get(
      '/road-dispatch/daily-vehicle-entries',
      { date: selectedDate, shift },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleAddRow = () => {
    // Store current scroll position
    const scrollY = window.scrollY;
    
    router.post(
      '/road-dispatch/daily-vehicle-entries',
      {
        siding_id: 1, // Default siding - you might want to make this dynamic
        entry_date: selectedDate,
        shift: activeShiftState,
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Restore scroll position after page loads
          setTimeout(() => {
            window.scrollTo(0, scrollY);
          }, 100);
        },
        onError: (errors) => {
          console.error('Error adding row:', errors);
        },
      }
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Daily Vehicle Entries" />
      
      <div className="space-y-6">
        {/* Header */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Daily Vehicle Entries</h1>
            <p className="text-gray-600 mt-1">Excel-style shift-based vehicle entry management</p>
          </div>
          <Button onClick={handleAddRow} className="flex items-center gap-2">
            <Plus className="h-4 w-4" />
            Add Row
          </Button>
        </div>

        {/* Controls Section */}
        <Card>
          <CardContent className="pt-6">
            <div className="flex gap-4 items-center">
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-gray-500" />
                <Input
                  type="date"
                  value={selectedDate}
                  onChange={(e) => handleDateChange(e.target.value)}
                  className="w-auto"
                />
              </div>
              
              {/* Shift Summary */}
              <div className="flex gap-2 ml-auto">
                {[1, 2, 3].map((shift) => (
                  <Badge
                    key={shift}
                    variant={activeShiftState === shift ? "default" : "secondary"}
                    className="cursor-pointer"
                  >
                    {shift === 1 ? '1ST' : shift === 2 ? '2ND' : '3RD'} SHIFT: {shiftSummary[shift] || 0}
                  </Badge>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Shift Tabs */}
        <ShiftTabs
          activeShift={activeShiftState}
          onShiftChange={handleShiftChange}
          shiftSummary={shiftSummary}
        />

        {/* Vehicle Entry Table */}
        <VehicleEntryTable
          entries={entries}
          date={selectedDate}
          shift={activeShiftState}
        />
      </div>
    </AppLayout>
  );
}
