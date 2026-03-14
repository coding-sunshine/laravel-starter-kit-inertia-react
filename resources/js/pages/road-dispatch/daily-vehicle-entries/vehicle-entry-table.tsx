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
  tare_wt_two: number | null;
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
  onEntryUpdated?: (entry: DailyVehicleEntry) => void;
  onEntryDeleted?: (id: number) => void;
  addRowButton?: React.ReactNode;
  onAddRow?: (count: number) => void;
  isAddingRow?: boolean;
}

export default function VehicleEntryTable({
  entries,
  date,
  shift,
  onEntryUpdated,
  onEntryDeleted,
  addRowButton,
  onAddRow,
  isAddingRow = false,
}: VehicleEntryTableProps) {
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
  
  const totalRows = Math.max(totalEntries, 100);

  return (
    <div className="overflow-x-auto">
      {/* Top strip: totals only */}
      <div className="border border-gray-300 border-b-0 px-2 py-1 flex flex-wrap items-center justify-end gap-4 text-[11px] bg-white">
        <span>
          Rows: <span className="font-semibold">{totalEntries}</span>
        </span>
        <span>
          Total Gross:{' '}
          <span className="font-semibold">{totalGrossWeight.toFixed(2)}</span>
        </span>
        <span>
          Total Net:{' '}
          <span className="font-semibold">{totalNetWeight.toFixed(2)}</span>
        </span>
      </div>

      <Table className="text-xs border border-gray-300 border-collapse">
        <TableHeader>
          <TableRow>
            <TableHead className="w-12 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">SL NO</TableHead>
            <TableHead className="w-28 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Siding</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">E Challan No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Vehicle No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Trip ID No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Transport Name</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Gross WT (G2)</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Tare WT (T1)</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Net Weight</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Reached At</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">WB No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">D Challan No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Challan Mode</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center">Status</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {Array.from({ length: totalRows }).map((_, index) => {
            if (index < totalEntries) {
              const entry = entries[index];

              return (
                <VehicleEntryRow
                  key={entry.id}
                  entry={entry}
                  serialNumber={index + 1}
                  date={date}
                  shift={shift}
                  onEntryUpdated={onEntryUpdated}
                  onEntryDeleted={onEntryDeleted}
                />
              );
            }

            const emptyCellClass = 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';
            const emptyCellClassLast = 'min-h-[4rem] px-2 py-3 border-t border-gray-300';
            return (
              <TableRow
                key={`empty-${index}`}
                className={isAddingRow ? 'opacity-60' : 'cursor-pointer hover:bg-gray-50'}
                onClick={() => {
                  if (onAddRow && !isAddingRow) {
                    onAddRow(1);
                  }
                }}
              >
                <TableCell className={`${emptyCellClass} text-center`} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClass} />
                <TableCell className={emptyCellClassLast} />
              </TableRow>
            );
          })}
        </TableBody>
      </Table>

      {/* Add Row button at bottom */}
      <div className="border border-gray-300 border-t-0 px-2 py-2 flex items-center justify-center bg-white">
        {addRowButton}
      </div>
    </div>
  );
}
