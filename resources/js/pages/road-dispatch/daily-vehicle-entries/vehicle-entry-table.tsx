import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import VehicleEntryRow from './vehicle-entry-row';

interface DailyVehicleEntry {
  id: number;
  siding_id: number;
  siding?: {
    id: number;
    name: string;
  };
  entry_date: string;
  shift: number;
  e_challan_no: string | null;
  vehicle_no: string | null;
  trip_id_no: string | null;
  transport_name: string | null;
  gross_wt: number | null;
  tare_wt: number | null;
  reached_at: string | null;
  wb_no: string | null;
  d_challan_no: string | null;
  challan_mode: 'offline' | 'online' | null;
  status: 'draft' | 'completed';
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
}

interface VehicleEntryTableProps {
  entries: DailyVehicleEntry[];
  date: string;
  shift: number;
}

export default function VehicleEntryTable({ entries, date, shift }: VehicleEntryTableProps) {
  const totalEntries = entries.length;
  
  // Check if the selected date is today
  const isToday = date === new Date().toISOString().split('T')[0];
  
  // Calculate side-wise totals
  const sideWiseTotals = entries.reduce((acc, entry) => {
    const sidingName = entry.siding?.name || 'Unknown Siding';
    const gross = parseFloat(entry.gross_wt?.toString() || '0') || 0;
    const tare = parseFloat(entry.tare_wt?.toString() || '0') || 0;
    const net = gross - tare;
    
    if (!acc[sidingName]) {
      acc[sidingName] = {
        totalGross: 0,
        totalNet: 0,
        count: 0
      };
    }
    
    acc[sidingName].totalGross += gross;
    acc[sidingName].totalNet += net;
    acc[sidingName].count += 1;
    
    return acc;
  }, {} as Record<string, { totalGross: number; totalNet: number; count: number }>);

  // Calculate overall totals
  const totalGrossWeight = entries.reduce((sum, entry) => {
    return sum + (parseFloat(entry.gross_wt?.toString() || '0') || 0);
  }, 0);
  
  const totalNetWeight = entries.reduce((sum, entry) => {
    const gross = parseFloat(entry.gross_wt?.toString() || '0') || 0;
    const tare = parseFloat(entry.tare_wt?.toString() || '0') || 0;
    return sum + (gross - tare);
  }, 0);
  
  return (
    <div className="bg-white rounded-lg shadow">
      {/* Side-wise Summary Section */}
      {entries.length > 0 && (
        <div className="border-b border-gray-200 p-4 bg-gray-50">
          <div className="space-y-4">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Side-wise Summary</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {Object.entries(sideWiseTotals).map(([sidingName, totals]) => (
                <div key={sidingName} className="bg-white p-4 rounded-lg border border-gray-200">
                  <h4 className="font-medium text-gray-900 mb-3">{sidingName}</h4>
                  <div className="space-y-2">
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">Entries:</span>
                      <span className="text-sm font-medium">
                        {isToday ? `${totals.count} text(today)` : `${totals.count} text(${date})`}
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">Total Gross Weight:</span>
                      <span className="text-sm font-medium">{totals.totalGross.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600">Total Net Weight:</span>
                      <span className="text-sm font-medium text-blue-600">{totals.totalNet.toFixed(2)}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            
            {/* Overall Totals */}
            <div className="mt-6 pt-4 border-t border-gray-300">
              <div className="flex justify-end gap-8">
                <div className="text-right">
                  <div className="text-sm text-gray-600">Overall Total Gross Weight</div>
                  <div className="text-xl font-bold text-gray-900">
                    {totalGrossWeight.toFixed(2)}
                  </div>
                </div>
                <div className="text-right">
                  <div className="text-sm text-gray-600">Overall Total Net Weight</div>
                  <div className="text-xl font-bold text-blue-600">
                    {totalNetWeight.toFixed(2)}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
      
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="w-16">SL NO</TableHead>
            <TableHead>E Challan No</TableHead>
            <TableHead>Vehicle No</TableHead>
            <TableHead>Trip ID No</TableHead>
            <TableHead>Transport Name</TableHead>
            <TableHead>Gross WT (G2)</TableHead>
            <TableHead>Tare WT (T1)</TableHead>
            <TableHead>Net Weight</TableHead>
            <TableHead>Reached At</TableHead>
            <TableHead>WB No</TableHead>
            <TableHead>D Challan No</TableHead>
            <TableHead>Challan Mode</TableHead>
            <TableHead>Status</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {entries.length === 0 ? (
            <TableRow>
              <TableCell colSpan={13} className="text-center py-8 text-gray-500">
                No entries found for this shift. Click "Add Row" to create a new entry.
              </TableCell>
            </TableRow>
          ) : (
            entries.map((entry, index) => (
              <VehicleEntryRow
                key={entry.id}
                entry={entry}
                serialNumber={totalEntries - index} // Descending: newest gets highest number
                date={date}
                shift={shift}
              />
            ))
          )}
        </TableBody>
      </Table>
    </div>
  );
}
