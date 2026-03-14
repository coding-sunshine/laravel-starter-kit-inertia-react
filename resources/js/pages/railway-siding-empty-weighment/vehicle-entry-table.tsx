import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import VehicleEntryRow from './vehicle-entry-row';

interface EmptyWeighmentEntry {
  id: number;
  siding_id: number;
  siding?: { id: number; name: string };
  entry_date: string;
  shift: number;
  vehicle_no: string | null;
  transport_name: string | null;
  tare_wt_two: number | null;
  reached_at: string;
  created_at: string;
  status: 'draft' | 'completed';
}

interface VehicleEntryTableProps {
  entries: EmptyWeighmentEntry[];
  date: string;
  shift: number;
  onEntryUpdated?: (entry: EmptyWeighmentEntry) => void;
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
  const totalRows = Math.max(totalEntries, 100);

  return (
    <div className="overflow-x-auto">
      <div className="border border-gray-300 border-b-0 px-2 py-1 flex flex-wrap items-center justify-end gap-4 text-[11px] bg-white">
        <span>
          Rows: <span className="font-semibold">{totalEntries}</span>
        </span>
      </div>

      <Table className="text-xs border border-gray-300 border-collapse">
        <TableHeader>
          <TableRow>
            <TableHead className="w-12 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              SL NO
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Vehicle No
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Transporter Name
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              T2 (Tare WT)
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Entry time
            </TableHead>
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
                <TableCell className={emptyCellClassLast} />
              </TableRow>
            );
          })}
        </TableBody>
      </Table>

      <div className="border border-gray-300 border-t-0 px-2 py-2 flex items-center justify-center bg-white">
        {addRowButton}
      </div>
    </div>
  );
}
