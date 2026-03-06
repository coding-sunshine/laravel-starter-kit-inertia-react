import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, Plus, Download } from 'lucide-react';
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
  trip_id_no: string | null;
  transport_name: string | null;
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

interface Siding {
  id: number;
  name: string;
}

interface ShiftStatus {
  is_active: boolean;
  is_available: boolean;
  is_completed: boolean;
}

interface ShiftTime {
  start: string;
  end: string;
}

interface Props {
  entries: DailyVehicleEntry[];
  date: string;
  activeShift: number;
  shiftSummary: Record<number, number>;
  shiftStatus?: Record<number, ShiftStatus>;
  shiftTimes: Record<number, ShiftTime>;
  sidings: Siding[];
}

export default function DailyVehicleEntriesIndex({ entries, date, activeShift, shiftSummary, shiftStatus, shiftTimes, sidings }: Props) {
  const [selectedDate, setSelectedDate] = useState(date);
  const [activeShiftState, setActiveShiftState] = useState(activeShift);
  const [exportShift, setExportShift] = useState<string>('all');
  const [exportSiding, setExportSiding] = useState<number>(sidings[0]?.id || 1);
  const [isExporting, setIsExporting] = useState(false);

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
    // Check if shift is available for today
    if (shiftStatus && selectedDate === new Date().toISOString().split('T')[0]) {
      if (!shiftStatus[shift]?.is_available) {
        // Show alert or message instead of changing shift
        const messages = {
          2: '2nd shift will be available after 1st shift completion (after 11:00)',
          3: '3rd shift will be available after 2nd shift completion (after 22:00)',
        };
        alert(messages[shift as keyof typeof messages] || 'This shift is not available at the current time.');
        return;
      }
    }

    setActiveShiftState(shift);
    router.get(
      '/road-dispatch/daily-vehicle-entries',
      { date: selectedDate, shift },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleAddRow = () => {
    // Check if current shift is available for today
    if (shiftStatus && selectedDate === new Date().toISOString().split('T')[0]) {
      if (!shiftStatus[activeShiftState]?.is_available) {
        const messages = {
          1: '1st shift is only available between 06:00 - 11:00',
          2: '2nd shift will be available after 1st shift completion (after 11:00)',
          3: '3rd shift will be available after 2nd shift completion (after 22:00)',
        };
        alert(messages[activeShiftState as keyof typeof messages] || 'This shift is not available at the current time.');
        return;
      }
    }

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

  const handleExport = async () => {
    setIsExporting(true);
    
    try {
      // Create export URL with parameters
      const exportUrl = `/road-dispatch/daily-vehicle-entries/export?date=${selectedDate}&siding=${exportSiding}&shift=${exportShift}`;
      
      // Use fetch to get the file with authentication cookies
      const response = await fetch(exportUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin', // Include cookies
      });
      
      if (!response.ok) {
        throw new Error(`Export failed: ${response.statusText}`);
      }
      
      // Get the filename from the Content-Disposition header or use a default
      const contentDisposition = response.headers.get('Content-Disposition');
      let filename = 'export.xlsx';
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="(.+)"/);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }
      
      // Convert response to blob
      const blob = await response.blob();
      
      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      
      // Cleanup
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
    } catch (error) {
      console.error('Export error:', error);
      // Show error message to user
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      alert('Export failed: ' + errorMessage);
    } finally {
      setIsExporting(false);
    }
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
          <div className="flex gap-3">
            {/* Export Controls */}
            <div className="flex items-center gap-2">
              <Select value={exportSiding.toString()} onValueChange={(value) => setExportSiding(Number(value))}>
                <SelectTrigger className="w-40">
                  <SelectValue placeholder="Select siding" />
                </SelectTrigger>
                <SelectContent>
                  {sidings.map((siding) => (
                    <SelectItem key={siding.id} value={siding.id.toString()}>
                      {siding.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select value={exportShift} onValueChange={setExportShift}>
                <SelectTrigger className="w-32">
                  <SelectValue placeholder="Export" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Shifts</SelectItem>
                  <SelectItem value="1">Shift 1</SelectItem>
                  <SelectItem value="2">Shift 2</SelectItem>
                  <SelectItem value="3">Shift 3</SelectItem>
                </SelectContent>
              </Select>
              <Button 
                onClick={handleExport} 
                disabled={isExporting}
                variant="outline"
                className="flex items-center gap-2"
              >
                <Download className="h-4 w-4" />
                {isExporting ? 'Exporting...' : 'Export'}
              </Button>
            </div>
            <Button onClick={handleAddRow} className="flex items-center gap-2">
              <Plus className="h-4 w-4" />
              Add Row
            </Button>
          </div>
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

        {/* Shift Times */}
        <p className="text-sm text-gray-500">
          Shift 1: {shiftTimes[1]?.start ?? '06:00'}–{shiftTimes[1]?.end ?? '11:00'} &nbsp;|&nbsp;{' '}
          Shift 2: {shiftTimes[2]?.start ?? '11:00'}–{shiftTimes[2]?.end ?? '22:00'} &nbsp;|&nbsp;{' '}
          Shift 3: {shiftTimes[3]?.start ?? '22:00'}–{shiftTimes[3]?.end ?? '06:00'}
        </p>

        {/* Shift Tabs */}
        <ShiftTabs
          activeShift={activeShiftState}
          onShiftChange={handleShiftChange}
          shiftSummary={shiftSummary}
          shiftStatus={shiftStatus}
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
