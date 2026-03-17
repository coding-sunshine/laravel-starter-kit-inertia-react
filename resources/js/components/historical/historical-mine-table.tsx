import React, { useRef } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Save, Pencil, Trash2 } from 'lucide-react';

interface HistoricalMine {
  id: number;
  month: string | null;
  trips_dispatched: number | null;
  dispatched_qty: string | number | null;
  trips_received: number | null;
  received_qty: string | number | null;
  coal_production_qty: string | number | null;
  ob_production_qty: string | number | null;
  remarks: string | null;
}

interface HistoricalMineTableProps {
  mines: HistoricalMine[];
  editingId: number | null;
  onEditingChange: (id: number | null) => void;
  onMineUpdated?: (mine: HistoricalMine) => void;
  onMineDeleted?: (id: number) => void;
  onAddRow?: () => void;
  isAddingRow?: boolean;
}

function getCsrfHeaders(): Record<string, string> {
  const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
  if (cookieMatch) {
    return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
  }
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta?.getAttribute('content')) {
    return { 'X-CSRF-TOKEN': meta.getAttribute('content')! };
  }
  return {};
}

function cellClass(): string {
  return 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';
}

export default function HistoricalMineTable({
  mines,
  editingId,
  onEditingChange,
  onMineUpdated,
  onMineDeleted,
  onAddRow,
  isAddingRow = false,
}: HistoricalMineTableProps) {
  const totalEntries = mines.length;
  const totalRows = Math.max(totalEntries, 100);

  const refMonth = useRef<HTMLInputElement>(null);
  const refTripsDispatched = useRef<HTMLInputElement>(null);
  const refDispatchedQty = useRef<HTMLInputElement>(null);
  const refTripsReceived = useRef<HTMLInputElement>(null);
  const refReceivedQty = useRef<HTMLInputElement>(null);
  const refCoalProductionQty = useRef<HTMLInputElement>(null);
  const refObProductionQty = useRef<HTMLInputElement>(null);
  const refRemarks = useRef<HTMLInputElement>(null);

  const handleSave = async (mine: HistoricalMine) => {
    const month = refMonth.current?.value?.trim() ?? '';
    const payload = {
      month: month || null,
      trips_dispatched: refTripsDispatched.current?.value === '' ? null : Number(refTripsDispatched.current?.value),
      dispatched_qty: refDispatchedQty.current?.value === '' ? null : refDispatchedQty.current?.value,
      trips_received: refTripsReceived.current?.value === '' ? null : Number(refTripsReceived.current?.value),
      received_qty: refReceivedQty.current?.value === '' ? null : refReceivedQty.current?.value,
      coal_production_qty: refCoalProductionQty.current?.value === '' ? null : refCoalProductionQty.current?.value,
      ob_production_qty: refObProductionQty.current?.value === '' ? null : refObProductionQty.current?.value,
      remarks: refRemarks.current?.value?.trim() || null,
    };

    try {
      const res = await fetch(`/historical/mines/${mine.id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        body: JSON.stringify(payload),
        credentials: 'include',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        return;
      }
      const updated = (data as { mine?: HistoricalMine }).mine;
      if (updated && onMineUpdated) {
        onMineUpdated(updated);
      }
      onEditingChange(null);
    } catch {
      // ignore for now
    }
  };

  const handleDelete = async (mine: HistoricalMine) => {
    try {
      const res = await fetch(`/historical/mines/${mine.id}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        credentials: 'include',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        return;
      }
      const deletedId = (data as { id?: number }).id ?? mine.id;
      if (onMineDeleted) {
        onMineDeleted(deletedId);
      }
      if (editingId === mine.id) {
        onEditingChange(null);
      }
    } catch {
      // ignore for now
    }
  };

  const formatCell = (value: string | number | null): string => {
    if (value === null || value === '') {
      return '—';
    }
    return String(value);
  };

  return (
    <div className="overflow-x-auto">
      <div className="border border-gray-300 border-b-0 px-2 py-1 flex flex-wrap items-center justify-between gap-4 text-[11px] bg-white">
        <span>
          Rows: <span className="font-semibold">{totalEntries}</span>
        </span>
        {onAddRow && (
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
            <TableHead className="w-12 min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              SL NO
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Month
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Trips Dispatched
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Dispatched Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Trips Received
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Received Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Coal Production Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              OB Production Qty
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center border-r border-gray-300">
              Remarks
            </TableHead>
            <TableHead className="min-h-[4rem] h-14 px-2 py-3 text-center">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {Array.from({ length: totalRows }).map((_, index) => {
            if (index < totalEntries) {
              const mine = mines[index];
              const isEditing = editingId === mine.id;

              return (
                <TableRow key={mine.id}>
                  <TableCell className={`${cellClass()} text-center`}>{index + 1}</TableCell>
                  {isEditing ? (
                    <>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refMonth}
                          type="text"
                          inputMode="numeric"
                          placeholder="YYYY-MM"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs"
                          defaultValue={mine.month ?? ''}
                        />
                      </TableCell>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refTripsDispatched}
                          type="number"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                          defaultValue={mine.trips_dispatched ?? ''}
                        />
                      </TableCell>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refDispatchedQty}
                          type="number"
                          step="0.01"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                          defaultValue={mine.dispatched_qty ?? ''}
                        />
                      </TableCell>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refTripsReceived}
                          type="number"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                          defaultValue={mine.trips_received ?? ''}
                        />
                      </TableCell>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refReceivedQty}
                          type="number"
                          step="0.01"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                          defaultValue={mine.received_qty ?? ''}
                        />
                      </TableCell>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refCoalProductionQty}
                          type="number"
                          step="0.01"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                          defaultValue={mine.coal_production_qty ?? ''}
                        />
                      </TableCell>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refObProductionQty}
                          type="number"
                          step="0.01"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs text-right"
                          defaultValue={mine.ob_production_qty ?? ''}
                        />
                      </TableCell>
                      <TableCell className={cellClass()}>
                        <input
                          ref={refRemarks}
                          type="text"
                          className="w-full border border-gray-300 rounded px-1 py-0.5 text-xs"
                          defaultValue={mine.remarks ?? ''}
                          placeholder="Remarks"
                        />
                      </TableCell>
                      <TableCell className={`${cellClass()} border-r-0 text-center`}>
                        <div className="flex items-center justify-center gap-1">
                          <Button
                            size="icon"
                            variant="outline"
                            className="h-7 w-7"
                            onClick={() => handleSave(mine)}
                            title="Save"
                            aria-label="Save"
                          >
                            <Save className="h-3.5 w-3.5" />
                          </Button>
                          <Button
                            size="icon"
                            variant="outline"
                            className="h-7 w-7 text-destructive hover:text-destructive"
                            onClick={() => handleDelete(mine)}
                            title="Delete"
                            aria-label="Delete"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </Button>
                        </div>
                      </TableCell>
                    </>
                  ) : (
                    <>
                      <TableCell className={cellClass()}>{formatCell(mine.month)}</TableCell>
                      <TableCell className={`${cellClass()} text-right`}>
                        {formatCell(mine.trips_dispatched)}
                      </TableCell>
                      <TableCell className={`${cellClass()} text-right`}>
                        {formatCell(mine.dispatched_qty)}
                      </TableCell>
                      <TableCell className={`${cellClass()} text-right`}>
                        {formatCell(mine.trips_received)}
                      </TableCell>
                      <TableCell className={`${cellClass()} text-right`}>
                        {formatCell(mine.received_qty)}
                      </TableCell>
                      <TableCell className={`${cellClass()} text-right`}>
                        {formatCell(mine.coal_production_qty)}
                      </TableCell>
                      <TableCell className={`${cellClass()} text-right`}>
                        {formatCell(mine.ob_production_qty)}
                      </TableCell>
                      <TableCell className={cellClass()}>
                        {mine.remarks ? String(mine.remarks) : '—'}
                      </TableCell>
                      <TableCell className={`${cellClass()} border-r-0 text-center`}>
                        <div className="flex items-center justify-center gap-1">
                          <Button
                            size="icon"
                            variant="outline"
                            className="h-7 w-7"
                            onClick={() => onEditingChange(mine.id)}
                            title="Edit"
                            aria-label="Edit"
                          >
                            <Pencil className="h-3.5 w-3.5" />
                          </Button>
                          <Button
                            size="icon"
                            variant="outline"
                            className="h-7 w-7 text-destructive hover:text-destructive"
                            onClick={() => handleDelete(mine)}
                            title="Delete"
                            aria-label="Delete"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </Button>
                        </div>
                      </TableCell>
                    </>
                  )}
                </TableRow>
              );
            }

            const emptyCellClass = 'min-h-[4rem] px-2 py-3 border-t border-r border-gray-300';

            return (
              <TableRow
                key={`empty-${index}`}
                className={isAddingRow ? 'opacity-60' : 'cursor-pointer hover:bg-gray-50'}
                onClick={() => {
                  if (onAddRow && !isAddingRow) {
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
              </TableRow>
            );
          })}
        </TableBody>
      </Table>

      <div className="border border-gray-300 border-t-0 px-2 py-2 flex items-center justify-center bg-white">
        {onAddRow && (
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
