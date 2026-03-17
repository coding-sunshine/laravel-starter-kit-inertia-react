import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import HistoricalRakeRow from '@/components/historical/historical-rake-row';
import { useCan } from '@/hooks/use-can';

interface HistoricalRake {
  id: number;
  siding_id: number;
  siding_name?: string | null;
  rake_number: number | null;
  priority_number: number | null;
  rr_number: number | null;
  wagon_count: number | null;
  loaded_weight_mt: string | number | null;
  under_load_mt: string | number | null;
  over_load_mt: string | number | null;
  overload_wagon_count: number | null;
  detention_hours: string | number | null;
  shunting_hours: string | number | null;
  total_amount_rs: string | number | null;
  destination: string | null;
  pakur_imwb_period: string | null;
  loading_date: string | null;
  remarks: string | null;
  data_source?: string | null;
}

interface HistoricalRakeTableProps {
  rakes: HistoricalRake[];
  editingId: number | null;
  onEditingChange: (id: number | null) => void;
  onRakeUpdated?: (rake: HistoricalRake) => void;
  onRakeDeleted?: (id: number) => void;
  onAddRow?: () => void;
  isAddingRow?: boolean;
}

export default function HistoricalRakeTable({
  rakes,
  editingId,
  onEditingChange,
  onRakeUpdated,
  onRakeDeleted,
  onAddRow,
  isAddingRow = false,
}: HistoricalRakeTableProps) {
  const canCreate = useCan('sections.historical_railway_siding.create');
  const totalEntries = rakes.length;
  const totalRows = Math.max(totalEntries, 100);

  return (
    <div className="overflow-x-auto">
      <div className="border border-gray-300 border-b-0 px-2 py-1 flex flex-wrap items-center justify-between gap-4 text-[11px] bg-white">
        <span>
          Rows: <span className="font-semibold">{totalEntries}</span>
        </span>
        {onAddRow && canCreate && (
          <Button
            size="sm"
            onClick={() => onAddRow()}
            disabled={isAddingRow}
            className="h-7 px-3 text-[11px]"
          >
            {isAddingRow ? 'Adding...' : 'Add Row'}
          </Button>
        )}
      </div>

      <Table className="text-xs border border-gray-300 border-collapse">
        <TableHeader>
          <TableRow>
            <TableHead className="w-12 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">SL NO</TableHead>
            <TableHead className="w-32 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Siding</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Loading Date</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Rake No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Priority No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">RR No</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Wagons</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Loaded WT (MT)</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Under Load (MT)</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Over Load (MT)</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">O/L Wagons</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Detention Hrs</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Shunting Hrs</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Total Amount (Rs)</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Destination</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">IMWB Period</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Remarks</TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {Array.from({ length: totalRows }).map((_, index) => {
            if (index < totalEntries) {
              const rake = rakes[index];
              return (
                <HistoricalRakeRow
                  key={rake.id}
                  rake={rake}
                  index={index}
                  isEditing={editingId === rake.id}
                  onEditClick={() => onEditingChange(rake.id)}
                  onSaveSuccess={() => onEditingChange(null)}
                  onRakeUpdated={onRakeUpdated}
                  onRakeDeleted={onRakeDeleted}
                />
              );
            }

            const emptyCellClass = 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';
            const emptyCellClassLast = 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';

            return (
              <TableRow
                key={`empty-${index}`}
                className={isAddingRow ? 'opacity-60' : 'cursor-pointer hover:bg-gray-50'}
                onClick={() => {
                  if (onAddRow && canCreate && !isAddingRow) {
                    onAddRow();
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
        {onAddRow && canCreate && (
          <Button
            onClick={() => onAddRow()}
            disabled={isAddingRow}
            className="flex items-center gap-2"
          >
            {isAddingRow ? 'Adding...' : 'Add Row'}
          </Button>
        )}
      </div>
    </div>
  );
}

