import React, { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { Maximize2, Minimize2 } from 'lucide-react';
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
  inline_submitted_at?: string | null;
}

interface VehicleEntryTableProps {
  entries: EmptyWeighmentEntry[];
  date: string;
  shift: number;
  canCreate?: boolean;
  canUpdate?: boolean;
  canDelete?: boolean;
  /** When false, user must submit the last row before adding another. */
  canAddAnotherRow?: boolean;
  onEntryUpdated?: (
    entry: EmptyWeighmentEntry,
    context?: { replaceClientId?: number; inlineSubmitted?: boolean; wasLocalDraft?: boolean },
  ) => void;
  onEntryDeleted?: (id: number) => void;
  addRowButton?: React.ReactNode;
  onAddRow?: (count: number) => void;
  isAddingRow?: boolean;
  /** Non-interactive blank rows directly under the last data row (e.g. after “Add 5 rows”). */
  plainRowsAfterLastEntry?: number;
}

/** Extra blank slots below data rows (click to add). */
const PLACEHOLDER_ROWS_BELOW_DATA = 5;

const emptyCellClass = 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';
const emptyCellClassLast = 'min-h-[4rem] px-2 py-3 border-t border-gray-300';

const EmptyPlaceholderRow = React.memo(function EmptyPlaceholderRow({
  allowAddInteraction,
  isAddingRow,
  onAddRow,
  staticOnly = false,
}: {
  allowAddInteraction: boolean;
  isAddingRow: boolean;
  onAddRow?: (count: number) => void;
  /** Empty row with no click handler (spacing below an editable draft). */
  staticOnly?: boolean;
}) {
  const clickable = !staticOnly && allowAddInteraction && onAddRow && !isAddingRow;
  return (
    <TableRow
      className={
        staticOnly
          ? ''
          : !allowAddInteraction || isAddingRow
            ? 'opacity-60'
            : 'cursor-pointer hover:bg-gray-50'
      }
      onClick={() => {
        if (clickable) {
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
      <TableCell className={emptyCellClassLast} />
    </TableRow>
  );
});

export default function VehicleEntryTable({
  entries,
  date,
  shift,
  canCreate = false,
  canUpdate = false,
  canDelete = false,
  canAddAnotherRow = true,
  onEntryUpdated,
  onEntryDeleted,
  addRowButton,
  onAddRow,
  isAddingRow = false,
  plainRowsAfterLastEntry = 0,
}: VehicleEntryTableProps) {
  const [isFullscreen, setIsFullscreen] = useState(false);

  useEffect(() => {
    if (!isFullscreen) {
      return;
    }
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [isFullscreen]);

  useEffect(() => {
    if (!isFullscreen) {
      return;
    }
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsFullscreen(false);
      }
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [isFullscreen]);

  const totalEntries = entries.length;
  const completedCount = entries.filter((e) => e.status === 'completed').length;
  const allowAddInteraction = canCreate && canAddAnotherRow;

  return (
    <div
      className={cn(
        'flex flex-col',
        isFullscreen
          ? 'fixed inset-0 z-[100] overflow-hidden bg-background p-3 shadow-2xl md:p-4'
          : 'relative',
      )}
      role={isFullscreen ? 'dialog' : undefined}
      aria-label={isFullscreen ? 'Empty weighment entries (full screen)' : undefined}
    >
      <div className="flex shrink-0 flex-wrap items-center justify-between gap-2 border border-gray-300 border-b-0 bg-white px-2 py-2 text-[11px]">
        <div className="flex flex-wrap items-center gap-4">
          <span>
            Total: <span className="font-semibold">{totalEntries}</span>
          </span>
          <span>
            Completed: <span className="font-semibold">{completedCount}</span>
          </span>
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="h-8 gap-1.5 text-xs"
          onClick={() => setIsFullscreen((open) => !open)}
          data-pan="railway-empty-weighment-table-fullscreen"
        >
          {isFullscreen ? (
            <>
              <Minimize2 className="h-3.5 w-3.5" aria-hidden />
              <span>Exit full screen</span>
            </>
          ) : (
            <>
              <Maximize2 className="h-3.5 w-3.5" aria-hidden />
              <span>Full screen</span>
            </>
          )}
        </Button>
      </div>

      <div
        className={cn(
          'min-h-0 bg-white',
          isFullscreen ? 'flex-1 overflow-auto' : 'overflow-x-auto',
        )}
      >
        <Table className="w-full border-collapse border border-gray-300 border-t-0 text-xs">
          <TableHeader>
            <TableRow>
              <TableHead className="w-12 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
                SL NO
              </TableHead>
              <TableHead className="w-28 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
                Siding
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
              <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
                Status
              </TableHead>
              <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {entries.map((entry, index) => (
              <VehicleEntryRow
                key={entry.id}
                entry={entry}
                serialNumber={index + 1}
                date={date}
                shift={shift}
                canUpdate={canUpdate}
                canDelete={canDelete}
                onEntryUpdated={onEntryUpdated}
                onEntryDeleted={onEntryDeleted}
              />
            ))}
            {Array.from({ length: plainRowsAfterLastEntry }).map((_, i) => (
              <EmptyPlaceholderRow
                key={`plain-tail-${totalEntries}-${i}`}
                allowAddInteraction={false}
                isAddingRow={false}
                staticOnly
              />
            ))}
            {Array.from({ length: PLACEHOLDER_ROWS_BELOW_DATA }).map((_, i) => (
              <EmptyPlaceholderRow
                key={`placeholder-${totalEntries + i}`}
                allowAddInteraction={allowAddInteraction}
                isAddingRow={isAddingRow}
                onAddRow={onAddRow}
              />
            ))}
          </TableBody>
        </Table>
      </div>

      <div className="flex shrink-0 items-center justify-center border border-gray-300 border-t-0 bg-white px-2 py-2">
        {canCreate ? addRowButton : null}
      </div>
    </div>
  );
}
