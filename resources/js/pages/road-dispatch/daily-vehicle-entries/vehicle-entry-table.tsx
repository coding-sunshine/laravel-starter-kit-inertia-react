import React, { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { Maximize2, Minimize2 } from 'lucide-react';
import VehicleEntryRow, { type DailyVehicleEntry } from './vehicle-entry-row';

export type { DailyVehicleEntry };

/** Extra blank slots below data rows (click to add). Temporarily low for testing add-row UX. */
const PLACEHOLDER_ROWS_BELOW_DATA = 5;

const emptyCellClass = 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';
const emptyCellClassLast = 'min-h-[4rem] px-2 py-3 border-t border-gray-300';

const EmptyPlaceholderRow = React.memo(function EmptyPlaceholderRow({
  allowAddInteraction,
  isAddingRow,
  onAddRow,
  staticOnly = false,
  showCreatedByColumn = false,
}: {
  allowAddInteraction: boolean;
  isAddingRow: boolean;
  onAddRow?: (count: number) => void;
  /** Empty row with no click handler (spacing below an editable draft). */
  staticOnly?: boolean;
  showCreatedByColumn?: boolean;
}) {
  const clickable =
    !staticOnly && allowAddInteraction && onAddRow && !isAddingRow;
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
      <TableCell className={emptyCellClass} />
      <TableCell className={emptyCellClass} />
      <TableCell className={emptyCellClass} />
      <TableCell className={emptyCellClass} />
      <TableCell className={emptyCellClass} />
      <TableCell className={emptyCellClass} />
      <TableCell className={emptyCellClass} />
      {showCreatedByColumn ? <TableCell className={emptyCellClass} /> : null}
      <TableCell className={emptyCellClass} />
      <TableCell className={emptyCellClassLast} />
    </TableRow>
  );
});

/** Net MT for an entry (matches row display: stored net_wt when set, else gross − tare). */
function entryNetWeightMt(entry: DailyVehicleEntry): number {
  if (entry.net_wt != null && entry.net_wt !== undefined) {
    return Number(entry.net_wt);
  }
  const gross = parseFloat(entry.gross_wt?.toString() || '0') || 0;
  const tare = parseFloat(entry.tare_wt?.toString() || '0') || 0;
  return gross - tare;
}

interface VehicleEntryTableProps {
  entries: DailyVehicleEntry[];
  date: string;
  shift: number;
  canCreate?: boolean;
  canUpdate?: boolean;
  canDelete?: boolean;
  /** When false, user must submit the last row before adding another. */
  canAddAnotherRow?: boolean;
  onEntryUpdated?: (entry: DailyVehicleEntry, context?: { replaceClientId?: number }) => void;
  onEntryDeleted?: (id: number) => void;
  addRowButton?: React.ReactNode;
  onAddRow?: (count: number) => void;
  isAddingRow?: boolean;
  /** Non-interactive blank rows directly under the last data row (e.g. after “Add 5 rows”). */
  plainRowsAfterLastEntry?: number;
  /** Super-admin / dispatch-manage-admin: extra “Created by” column. */
  showCreatedByColumn?: boolean;
  /** Shown at the top of the fullscreen overlay (e.g. shift countdown). Ignored when not fullscreen. */
  fullscreenTopContent?: React.ReactNode;
}

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
  showCreatedByColumn = false,
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

  const totalTrips = entries.filter(
    (e) => e.status === 'completed' && entryNetWeightMt(e) > 0,
  ).length;

  const totalNetWeight = entries.reduce((sum, entry) => sum + entryNetWeightMt(entry), 0);

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
      aria-label={isFullscreen ? 'Vehicle entries (full screen)' : undefined}
    >
      <div className="flex shrink-0 flex-wrap items-center justify-between gap-2 border border-gray-300 border-b-0 bg-white px-2 py-2 text-[11px]">
        <div className="flex flex-wrap items-center gap-4">
          <span>
            Total trips: <span className="font-semibold">{totalTrips}</span>
          </span>
          <span>
            Total Net:{' '}
            <span className="font-semibold">{totalNetWeight.toFixed(2)}</span>
          </span>
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="h-8 gap-1.5 text-xs"
          onClick={() => setIsFullscreen((open) => !open)}
          data-pan="daily-vehicle-entries-table-fullscreen"
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
            {showCreatedByColumn ? (
              <TableHead className="min-h-[4rem] h-14 min-w-[7rem] px-2 py-3 text-center border-r border-gray-300">
                Created by
              </TableHead>
            ) : null}
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">Status</TableHead>
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
              showCreatedByColumn={showCreatedByColumn}
            />
          ))}
          {Array.from({ length: plainRowsAfterLastEntry }).map((_, i) => (
            <EmptyPlaceholderRow
              key={`plain-tail-${totalEntries}-${i}`}
              allowAddInteraction={false}
              isAddingRow={false}
              staticOnly
              showCreatedByColumn={showCreatedByColumn}
            />
          ))}
          {Array.from({ length: PLACEHOLDER_ROWS_BELOW_DATA }).map((_, i) => (
            <EmptyPlaceholderRow
              key={`placeholder-${totalEntries + i}`}
              allowAddInteraction={allowAddInteraction}
              isAddingRow={isAddingRow}
              onAddRow={onAddRow}
              showCreatedByColumn={showCreatedByColumn}
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
