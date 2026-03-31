import React, { useState, useRef, useCallback, useEffect } from 'react';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Loader2, Trash2, Edit, X, Pencil } from 'lucide-react';
import {
  digitsToGrossDisplay,
  formatNetMtFromGrossTare,
  grossDigitsOnly,
  grossDisplayFromMt,
  parseGrossInputToMt,
} from './gross-weight-input';

export interface DailyVehicleEntry {
  id: number;
  siding_id: number;
  siding?: {
    id: number;
    name: string;
    station_code?: string | null;
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
  reached_at: string;
  wb_no: string | null;
  d_challan_no: string | null;
  challan_mode: 'offline' | 'online' | null;
  status: 'draft' | 'completed';
  remarks?: string | null;
  net_wt?: number | null;
  created_by: number;
  updated_by: number | null;
  created_at: string;
  updated_at: string;
  inline_submitted_at: string | null;
  creator?: {
    id: number;
    name: string;
  } | null;
}

type VehicleLookupResponse = {
  tare_wt?: number | null;
  transport_name?: string | null;
};

function focusNextRowFirstField(fromElement: HTMLElement): boolean {
  const currentRow = fromElement.closest('tr');
  const nextRow = currentRow?.nextElementSibling;
  if (!(nextRow instanceof HTMLElement)) {
    return false;
  }

  const first = nextRow.querySelector<HTMLElement>('[data-field="e_challan_no"]');
  if (!first) {
    return false;
  }

  first.focus();
  // @ts-expect-error HTMLInputElement#select exists; HTMLElement typing is generic here
  first.select?.();
  return true;
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

async function fetchVehicleWorkorderHints(vehicleNo: string): Promise<VehicleLookupResponse | null> {
  const trimmed = vehicleNo.trim();
  if (!trimmed) {
    return null;
  }
  try {
    const res = await fetch(
      `/road-dispatch/vehicle-workorders/lookup?vehicle_no=${encodeURIComponent(trimmed)}`,
      {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'include',
      },
    );
    if (!res.ok) {
      return null;
    }
    return (await res.json()) as VehicleLookupResponse;
  } catch {
    return null;
  }
}

type InlineFormState = {
  e_challan_no: string;
  vehicle_no: string;
  trip_id_no: string;
  transport_name: string;
  gross_wt: string;
  tare_wt: string;
  wb_no: string;
  d_challan_no: string;
  challan_mode: string;
  status: string;
  remarks: string;
};

function mergeVehicleHints(
  prev: InlineFormState,
  data: VehicleLookupResponse | null,
  options?: { overwriteTransportName?: boolean },
): InlineFormState {
  if (!data) {
    return prev;
  }
  const next = { ...prev };
  if (data.tare_wt != null) {
    next.tare_wt = data.tare_wt.toString();
  }
  if (
    data.transport_name &&
    (options?.overwriteTransportName === true || next.transport_name.trim() === '')
  ) {
    next.transport_name = data.transport_name;
  }
  return next;
}

function entryToFormState(entry: DailyVehicleEntry): InlineFormState {
  return {
    e_challan_no: entry.e_challan_no || '',
    vehicle_no: entry.vehicle_no || '',
    trip_id_no: entry.trip_id_no || '',
    transport_name: entry.transport_name || '',
    gross_wt: grossDisplayFromMt(entry.gross_wt),
    tare_wt: entry.tare_wt?.toString() || '',
    wb_no: entry.wb_no || '',
    d_challan_no: entry.d_challan_no || '',
    challan_mode: entry.challan_mode || 'online',
    status: entry.status || 'draft',
    remarks: entry.remarks || '',
  };
}

function formatEntryNetMt(entry: DailyVehicleEntry): string {
  if (entry.net_wt != null && entry.net_wt !== undefined) {
    return Number(entry.net_wt).toFixed(2);
  }
  const gross = parseFloat(String(entry.gross_wt ?? 0)) || 0;
  const tare = parseFloat(String(entry.tare_wt ?? 0)) || 0;
  return (gross - tare).toFixed(2);
}

function formatChallanModeLabel(mode: string | null): string {
  if (mode === 'offline') {
    return 'Offline';
  }
  if (mode === 'online') {
    return 'Online';
  }
  return '—';
}

function formatCreatorLabel(entry: DailyVehicleEntry): string {
  const name = entry.creator?.name?.trim();
  return name && name.length > 0 ? name : '—';
}

interface VehicleEntryRowProps {
  entry: DailyVehicleEntry;
  serialNumber: number;
  date: string;
  shift: number;
  canUpdate?: boolean;
  canDelete?: boolean;
  onEntryUpdated?: (
    entry: DailyVehicleEntry,
    context?: { replaceClientId?: number; inlineSubmitted?: boolean; wasLocalDraft?: boolean },
  ) => void;
  onEntryDeleted?: (id: number) => void;
  showCreatedByColumn?: boolean;
}

function VehicleEntryRow({
  entry,
  serialNumber,
  date,
  shift,
  canUpdate = false,
  canDelete = false,
  onEntryUpdated,
  onEntryDeleted,
  showCreatedByColumn = false,
}: VehicleEntryRowProps) {
  const isLocalDraft = entry.id < 0;
  const [isSaving, setIsSaving] = useState(false);
  const [, setShowSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [showDetailModal, setShowDetailModal] = useState(false);

  const isCommitted = Boolean(entry.inline_submitted_at);

  const [formData, setFormData] = useState<InlineFormState>(() => entryToFormState(entry));
  const [displayedNetMt, setDisplayedNetMt] = useState(() =>
    formatNetMtFromGrossTare(grossDisplayFromMt(entry.gross_wt), entry.tare_wt?.toString() ?? ''),
  );

  /** Digits-only buffer while gross is focused; MT dot applied only on blur (not on each keystroke). */
  const [grossDigitsLocal, setGrossDigitsLocal] = useState(() =>
    grossDigitsOnly(grossDisplayFromMt(entry.gross_wt)),
  );
  const [isGrossFocused, setIsGrossFocused] = useState(false);

  const formDataRef = useRef(formData);
  const grossDigitsLocalRef = useRef(grossDigitsLocal);
  const grossFocusedRef = useRef(false);
  const autoTransportNameRef = useRef(true);

  useEffect(() => {
    formDataRef.current = formData;
  }, [formData]);

  useEffect(() => {
    grossDigitsLocalRef.current = grossDigitsLocal;
  }, [grossDigitsLocal]);

  useEffect(() => {
    grossFocusedRef.current = isGrossFocused;
  }, [isGrossFocused]);

  useEffect(() => {
    const next = entryToFormState(entry);
    // eslint-disable-next-line @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- controlled reset when entry changes
    setFormData(next);
    // eslint-disable-next-line @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- align net with entry from server
    setDisplayedNetMt(formatNetMtFromGrossTare(next.gross_wt, next.tare_wt));
  }, [
    entry.id,
    entry.updated_at,
    entry.inline_submitted_at,
    entry.gross_wt,
    entry.tare_wt,
    entry.net_wt,
    entry.wb_no,
  ]);

  useEffect(() => {
    if (!showDetailModal) {
      return;
    }
    // eslint-disable-next-line @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- sync modal net when opened
    setDisplayedNetMt(formatNetMtFromGrossTare(formDataRef.current.gross_wt, formDataRef.current.tare_wt));
  }, [showDetailModal]);

  useEffect(() => {
    // eslint-disable-next-line @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- tare changes from vehicle lookup without gross blur
    setDisplayedNetMt(formatNetMtFromGrossTare(formDataRef.current.gross_wt, formData.tare_wt));
  }, [formData.tare_wt]);

  const updateField = (field: keyof InlineFormState, value: string) => {
    if (field === 'transport_name') {
      autoTransportNameRef.current = value.trim() === '';
    }
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const commitGrossFromDraft = useCallback(() => {
    if (!grossFocusedRef.current) {
      return;
    }
    const digits = grossDigitsLocalRef.current;
    const formatted = digitsToGrossDisplay(digits);
    const tare = formDataRef.current.tare_wt;
    grossFocusedRef.current = false;
    setIsGrossFocused(false);
    setFormData((prev) => ({ ...prev, gross_wt: formatted }));
    setDisplayedNetMt(formatNetMtFromGrossTare(formatted, tare));
  }, []);

  const save = useCallback(
    async (options?: { inlineSubmit?: boolean }) => {
      if (!canUpdate) {
        return;
      }
      const wasGrossFocused = grossFocusedRef.current;
      const grossCommitted = wasGrossFocused
        ? digitsToGrossDisplay(grossDigitsLocalRef.current)
        : formDataRef.current.gross_wt;
      if (wasGrossFocused) {
        grossFocusedRef.current = false;
        setIsGrossFocused(false);
        setFormData((prev) => ({ ...prev, gross_wt: grossCommitted }));
        setGrossDigitsLocal(grossDigitsOnly(grossCommitted));
      }
      const dataToSave = { ...formDataRef.current, gross_wt: grossCommitted };
      setDisplayedNetMt(formatNetMtFromGrossTare(grossCommitted, dataToSave.tare_wt));
      setIsSaving(true);
      setShowSuccess(false);
      setSaveError(null);
      try {
        const numOrNull = (s: string): number | null => {
          if (s.trim() === '') {
            return null;
          }
          const n = parseFloat(s);
          return Number.isFinite(n) ? n : null;
        };

        const grossMt = parseGrossInputToMt(dataToSave.gross_wt);

        let res: Response;
        if (isLocalDraft) {
          const storeBody: Record<string, unknown> = {
            siding_id: entry.siding_id,
            entry_date: date,
            shift,
            e_challan_no: dataToSave.e_challan_no.trim() || null,
            vehicle_no: dataToSave.vehicle_no.trim() || null,
            trip_id_no: dataToSave.trip_id_no.trim() || null,
            transport_name: dataToSave.transport_name.trim() || null,
            gross_wt: grossMt,
            tare_wt: numOrNull(dataToSave.tare_wt),
            wb_no: dataToSave.wb_no.trim() || null,
            d_challan_no: dataToSave.d_challan_no.trim() || null,
            challan_mode: dataToSave.challan_mode || null,
            status: dataToSave.status || 'draft',
            remarks: dataToSave.remarks.trim() || null,
          };
          if (options?.inlineSubmit) {
            storeBody.inline_submit = true;
          }
          res = await fetch('/road-dispatch/daily-vehicle-entries', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              ...getCsrfHeaders(),
            },
            body: JSON.stringify(storeBody),
            credentials: 'include',
          });
        } else {
          const body: Record<string, unknown> = {
            ...dataToSave,
            gross_wt: grossMt,
            tare_wt: numOrNull(dataToSave.tare_wt),
          };
          if (options?.inlineSubmit) {
            body.inline_submit = true;
          }
          res = await fetch(`/road-dispatch/daily-vehicle-entries/${entry.id}`, {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              ...getCsrfHeaders(),
            },
            body: JSON.stringify(body),
            credentials: 'include',
          });
        }

        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          if (res.status === 404) {
            onEntryDeleted?.(entry.id);
            return;
          }
          const msg =
            (data as { message?: string }).message ??
            (typeof (data as { errors?: Record<string, string[]> }).errors === 'object'
              ? Object.values((data as { errors: Record<string, string[]> }).errors).flat().join(', ')
              : null) ??
            res.statusText ??
            'Save failed';
          setSaveError(res.status === 419 ? 'Session expired. Please refresh the page.' : msg);
          return;
        }
        const updated = (data as { entry?: DailyVehicleEntry }).entry;
        if (updated) {
          if (isLocalDraft) {
            onEntryUpdated?.(updated, {
              replaceClientId: entry.id,
              inlineSubmitted: options?.inlineSubmit ?? false,
              wasLocalDraft: true,
            });
          } else {
            onEntryUpdated?.(updated, {
              inlineSubmitted: options?.inlineSubmit ?? false,
              wasLocalDraft: false,
            });
          }
        }
        setShowSuccess(true);
        setTimeout(() => setShowSuccess(false), 2000);
        setShowDetailModal(false);
      } catch {
        setSaveError('Network error. Please try again.');
      } finally {
        setIsSaving(false);
      }
    },
    [canUpdate, date, entry.id, entry.siding_id, isLocalDraft, onEntryUpdated, onEntryDeleted, shift],
  );

  const handleVehicleNoBlur = useCallback(async () => {
    const hints = await fetchVehicleWorkorderHints(formDataRef.current.vehicle_no);
    if (hints) {
      setFormData((prev) => {
        const next = mergeVehicleHints(prev, hints, {
          overwriteTransportName: autoTransportNameRef.current,
        });
        // Ensure modal + row net weight updates immediately when tare comes from lookup.
        setDisplayedNetMt(formatNetMtFromGrossTare(next.gross_wt, next.tare_wt));
        return next;
      });
      if (hints.transport_name) {
        autoTransportNameRef.current = true;
      }
    }
  }, []);

  const hasMeaningfulValues = () => {
    if (isLocalDraft) {
      return !!(
        formData.e_challan_no.trim() ||
        formData.vehicle_no.trim() ||
        formData.trip_id_no.trim() ||
        formData.transport_name.trim() ||
        formData.gross_wt.trim() ||
        formData.tare_wt.trim() ||
        formData.wb_no.trim() ||
        formData.challan_mode === 'offline'
      );
    }
    return !!(
      entry.e_challan_no?.trim() ||
      entry.vehicle_no?.trim() ||
      entry.trip_id_no?.trim() ||
      entry.transport_name?.trim() ||
      entry.gross_wt ||
      entry.tare_wt ||
      entry.wb_no?.trim() ||
      entry.challan_mode === 'offline'
    );
  };

  const shouldShowDeleteButton = () => {
    if (isLocalDraft) {
      return true;
    }
    return (
      !entry.e_challan_no?.trim() &&
      !entry.vehicle_no?.trim() &&
      !entry.trip_id_no?.trim() &&
      !entry.transport_name?.trim() &&
      !entry.gross_wt &&
      !entry.tare_wt &&
      !entry.wb_no?.trim() &&
      entry.status === 'draft'
    );
  };

  const handleDelete = async () => {
    if (!canDelete) {
      return;
    }
    setShowDetailModal(false);
    if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
      return;
    }
    setSaveError(null);
    if (isLocalDraft) {
      onEntryDeleted?.(entry.id);
      return;
    }
    try {
      const res = await fetch(`/road-dispatch/daily-vehicle-entries/${entry.id}`, {
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
        if (res.status === 404) {
          onEntryDeleted?.(entry.id);
          return;
        }
        const msg = (data as { message?: string }).message ?? 'Cannot delete this entry.';
        setSaveError(msg);
        alert(msg);
        return;
      }
      const payload = data as { deleted?: boolean; id?: number };
      if (payload.deleted && payload.id != null) {
        onEntryDeleted?.(payload.id);
      }
    } catch {
      setSaveError('Network error. Please try again.');
      alert('Error deleting entry. Please try again.');
    }
  };

  const formatDateTime = (dateTime: string) => {
    return new Date(dateTime).toLocaleString('en-IN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const handleGrossFocus = () => {
    grossFocusedRef.current = true;
    setIsGrossFocused(true);
    setGrossDigitsLocal(grossDigitsOnly(formDataRef.current.gross_wt));
  };

  const displayNetMtModal = () => {
    // In modal edit mode we want *live* Gross − Tare.
    // Important: while gross is focused we keep the "live" digits in `grossDigitsLocal`
    // and only commit to `formData.gross_wt` on blur, so net must read from that buffer.
    const grossForNet = isGrossFocused
      ? digitsToGrossDisplay(grossDigitsLocal)
      : formData.gross_wt;

    return formatNetMtFromGrossTare(grossForNet, formData.tare_wt);
  };

  const tareDisplayText =
    formData.tare_wt.trim() === ''
      ? '—'
      : (() => {
          const n = parseFloat(formData.tare_wt);
          return Number.isFinite(n) ? n.toFixed(2) : '—';
        })();

  const readOnlyClass = 'px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-xs align-middle';
  const focusWithinCellClass =
    'focus-within:bg-green-100 focus-within:ring-2 focus-within:ring-green-500 focus-within:ring-inset dark:focus-within:bg-green-950/30';
  const interactiveCellClass = `px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] ${focusWithinCellClass}`;
  const submitButtonFocusClass =
    'focus-visible:ring-4 focus-visible:ring-yellow-600 focus-visible:ring-offset-2 focus-visible:ring-offset-background';
  const deleteButtonFocusClass =
    'focus-visible:ring-4 focus-visible:ring-red-700 focus-visible:ring-offset-2 focus-visible:ring-offset-background';

  return (
    <>
      <TableRow className={entry.status === 'draft' ? 'bg-red-50/30 dark:bg-red-950/20' : ''}>
        <TableCell className="px-2 py-3 font-medium border-t border-r border-gray-300 min-h-[4rem] text-center">
          {serialNumber}
        </TableCell>

        <TableCell className="px-2 py-3 text-muted-foreground text-xs whitespace-nowrap border-t border-r border-gray-300 min-h-[4rem]" title="Siding (read-only)">
          {entry.siding?.station_code?.trim() || entry.siding?.name || '—'}
        </TableCell>

        {isCommitted ? (
          <>
            <TableCell className={readOnlyClass}>{entry.e_challan_no?.trim() || '—'}</TableCell>
            <TableCell className={readOnlyClass}>{entry.vehicle_no?.trim() || '—'}</TableCell>
            <TableCell className={readOnlyClass}>{entry.trip_id_no?.trim() || '—'}</TableCell>
            <TableCell className={readOnlyClass}>{entry.transport_name?.trim() || '—'}</TableCell>
            <TableCell className={`${readOnlyClass} text-right`}>
              {entry.gross_wt != null ? Number(entry.gross_wt).toFixed(2) : '—'}
            </TableCell>
            <TableCell className={`${readOnlyClass} text-right`}>
              {entry.tare_wt != null ? Number(entry.tare_wt).toFixed(2) : '—'}
            </TableCell>
            <TableCell className={`${readOnlyClass} font-medium text-blue-600 text-right`}>{formatEntryNetMt(entry)}</TableCell>
            <TableCell className={readOnlyClass}>{entry.wb_no?.trim() || '—'}</TableCell>
            <TableCell className={`${readOnlyClass} text-gray-600 whitespace-nowrap`}>{formatDateTime(entry.reached_at)}</TableCell>
            <TableCell className={readOnlyClass}>{formatChallanModeLabel(entry.challan_mode)}</TableCell>
            {showCreatedByColumn ? (
              <TableCell className={`${readOnlyClass} text-muted-foreground whitespace-nowrap`} title="Created by">
                {formatCreatorLabel(entry)}
              </TableCell>
            ) : null}
            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
              {entry.status === 'completed' ? (
                <Badge variant="default" className="text-xs px-2 py-0.5">
                  Stock updated
                </Badge>
              ) : hasMeaningfulValues() ? (
                <Badge variant="secondary" className="text-xs px-2 py-0.5">
                  In progress
                </Badge>
              ) : (
                <span className="text-xs text-muted-foreground">Empty</span>
              )}
            </TableCell>
            <TableCell className="px-2 py-3 border-t border-gray-300 min-h-[4rem]">
              <div className="flex flex-col items-stretch gap-1">
                {canUpdate && (
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="h-8 gap-1 px-2"
                    onClick={() => setShowDetailModal(true)}
                    title="Edit entry"
                  >
                    <Pencil className="h-3.5 w-3.5" />
                    <span className="text-xs">Edit</span>
                  </Button>
                )}
                {saveError && !showDetailModal && <p className="text-[10px] text-destructive">{saveError}</p>}
              </div>
            </TableCell>
          </>
        ) : (
          <>
            <TableCell className={interactiveCellClass}>
              <Input
                data-entry-id={entry.id}
                data-field="e_challan_no"
                value={formData.e_challan_no}
                disabled={!canUpdate}
                onChange={(e) => updateField('e_challan_no', e.target.value)}
                placeholder="E-CH"
                className="w-24 h-12 px-2 text-xs"
              />
            </TableCell>

            <TableCell className={interactiveCellClass}>
              <Input
                value={formData.vehicle_no}
                disabled={!canUpdate}
                onChange={(e) => updateField('vehicle_no', e.target.value)}
                onBlur={handleVehicleNoBlur}
                placeholder="VEH"
                className="w-24 h-12 px-2 text-xs"
              />
            </TableCell>

            <TableCell className={interactiveCellClass}>
              <Input
                value={formData.trip_id_no}
                disabled={!canUpdate}
                onChange={(e) => updateField('trip_id_no', e.target.value)}
                placeholder="TRIP"
                className="w-20 h-12 px-2 text-xs"
              />
            </TableCell>

            <TableCell className={interactiveCellClass}>
              <Input
                value={formData.transport_name}
                disabled={!canUpdate}
                onChange={(e) => updateField('transport_name', e.target.value)}
                placeholder="TRANS"
                className="w-32 h-12 px-2 text-xs"
              />
            </TableCell>

            <TableCell className={interactiveCellClass}>
              <Input
                type="text"
                inputMode="numeric"
                autoComplete="off"
                value={isGrossFocused ? grossDigitsLocal : formData.gross_wt}
                disabled={!canUpdate || showDetailModal}
                onFocus={handleGrossFocus}
                onChange={(e) => setGrossDigitsLocal(grossDigitsOnly(e.target.value))}
                onBlur={commitGrossFromDraft}
                placeholder="G2"
                className="w-[5.5rem] h-12 px-2 text-right text-xs"
              />
            </TableCell>

            <TableCell className={`px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-right text-xs ${!canUpdate ? 'text-muted-foreground' : ''}`}>
              {tareDisplayText}
            </TableCell>

            <TableCell className="px-2 py-3 font-medium text-blue-600 text-right text-xs border-t border-r border-gray-300 min-h-[4rem]">
              {displayedNetMt}
            </TableCell>

            <TableCell className={interactiveCellClass}>
              <Input
                value={formData.wb_no}
                disabled={!canUpdate}
                onChange={(e) => updateField('wb_no', e.target.value)}
                placeholder="WB"
                className="w-20 h-12 px-2 text-xs"
              />
            </TableCell>

            <TableCell className="px-2 py-3 text-xs text-gray-600 border-t border-r border-gray-300 min-h-[4rem] whitespace-nowrap">
              {formatDateTime(entry.reached_at)}
            </TableCell>

            <TableCell className={interactiveCellClass}>
              <Select
                value={formData.challan_mode}
                disabled={!canUpdate}
                onValueChange={(value) => {
                  updateField('challan_mode', value as 'offline' | 'online');
                }}
              >
                <SelectTrigger className="w-20 h-12 text-xs px-2">
                  <SelectValue placeholder="MODE" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="offline">Offline</SelectItem>
                  <SelectItem value="online">Online</SelectItem>
                </SelectContent>
              </Select>
            </TableCell>

            {showCreatedByColumn ? (
              <TableCell className={`${readOnlyClass} text-muted-foreground whitespace-nowrap`} title="Created by">
                {formatCreatorLabel(entry)}
              </TableCell>
            ) : null}

            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
              {entry.status === 'completed' ? (
                <Badge variant="default" className="text-xs px-2 py-0.5">
                  Stock updated
                </Badge>
              ) : hasMeaningfulValues() ? (
                <Badge variant="secondary" className="text-xs px-2 py-0.5">
                  In progress
                </Badge>
              ) : (
                <span className="text-xs text-muted-foreground">Empty</span>
              )}
            </TableCell>

            <TableCell className={`px-2 py-3 border-t border-gray-300 min-h-[4rem] ${focusWithinCellClass}`}>
              <div className="flex flex-col items-stretch gap-1">
                {canUpdate && (
                  <Button
                    type="button"
                    size="sm"
                    className={`h-8 text-xs ${submitButtonFocusClass}`}
                    disabled={isSaving}
                    onClick={() => save({ inlineSubmit: true })}
                  >
                    {isSaving ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : 'Submit'}
                  </Button>
                )}
                {canDelete && shouldShowDeleteButton() && (
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className={`h-8 text-xs ${deleteButtonFocusClass}`}
                    disabled={isSaving}
                    onClick={handleDelete}
                    onKeyDown={(event) => {
                      if (event.key === 'Tab' && !event.shiftKey) {
                        const moved = focusNextRowFirstField(event.currentTarget);
                        if (moved) {
                          event.preventDefault();
                        }
                      }
                    }}
                  >
                    Delete
                  </Button>
                )}
                {saveError && <p className="text-[10px] text-destructive">{saveError}</p>}
              </div>
            </TableCell>
          </>
        )}
      </TableRow>

      {showDetailModal && (
        <div className="fixed inset-0 bg-gray-500 bg-opacity-30 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-semibold">Vehicle Entry Details</h2>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  commitGrossFromDraft();
                  setShowDetailModal(false);
                }}
                className="h-8 w-8 p-0"
              >
                <X className="h-4 w-4" />
              </Button>
            </div>

            <div className="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">E Challan No</label>
                <Input value={formData.e_challan_no} disabled={!canUpdate} onChange={(e) => updateField('e_challan_no', e.target.value)} placeholder="E Challan No" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Vehicle No</label>
                <Input
                  value={formData.vehicle_no}
                  disabled={!canUpdate}
                  onChange={(e) => updateField('vehicle_no', e.target.value)}
                  onBlur={handleVehicleNoBlur}
                  placeholder="Vehicle No"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Trip ID No</label>
                <Input value={formData.trip_id_no} disabled={!canUpdate} onChange={(e) => updateField('trip_id_no', e.target.value)} placeholder="Trip ID" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Transport Name</label>
                <Input
                  value={formData.transport_name}
                  disabled={!canUpdate}
                  onChange={(e) => updateField('transport_name', e.target.value)}
                  placeholder="Transport"
                />
              </div>
              <div className="col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-1">Gross WT (G2), MT</label>
                <Input
                  type="text"
                  inputMode="numeric"
                  autoComplete="off"
                  value={isGrossFocused ? grossDigitsLocal : formData.gross_wt}
                  disabled={!canUpdate}
                  onFocus={handleGrossFocus}
                  onChange={(e) => setGrossDigitsLocal(grossDigitsOnly(e.target.value))}
                  onBlur={commitGrossFromDraft}
                  placeholder="Gross WT"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tare WT (T1)</label>
                <Input
                  value={tareDisplayText}
                  readOnly
                  disabled={!canUpdate}
                  className="bg-muted/50"
                  title="From vehicle work order lookup"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Net Weight (MT)</label>
                <Input
                  value={displayNetMtModal()}
                  disabled
                  className="bg-gray-50"
                  title={
                    entry.net_wt != null
                      ? `Gross − Tare (stored net: ${Number(entry.net_wt).toFixed(2)})`
                      : 'Gross − Tare'
                  }
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">WB No.</label>
                <Input
                  value={formData.wb_no}
                  disabled={!canUpdate}
                  onChange={(e) => updateField('wb_no', e.target.value)}
                  placeholder="Weighbridge slip / ref"
                />
              </div>
              <div className="col-span-2">
                <label className="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                <textarea
                  value={formData.remarks}
                  disabled={!canUpdate}
                  onChange={(e) => updateField('remarks', e.target.value)}
                  rows={3}
                  className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  placeholder="Optional notes for reports"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Challan Mode</label>
                <Select
                  value={formData.challan_mode}
                  disabled={!canUpdate}
                  onValueChange={(value) => updateField('challan_mode', value as 'offline' | 'online')}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Mode" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="offline">Offline</SelectItem>
                    <SelectItem value="online">Online</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="flex flex-col gap-3">
              <div className="flex items-center gap-3">
                <span className="text-sm font-medium text-gray-700">Status:</span>
                <Select value={formData.status} disabled={!canUpdate} onValueChange={(value) => updateField('status', value)}>
                  <SelectTrigger className="w-40">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="draft">Draft</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                  </SelectContent>
                </Select>
                {formData.status !== entry.status && (
                  <span className="text-xs text-amber-600 font-medium">
                    Changed from {entry.status} → {formData.status}
                  </span>
                )}
              </div>

              {saveError && <p className="text-sm text-destructive mb-2">{saveError}</p>}
              <div className="flex justify-end gap-2">
                <Button
                  variant="outline"
                  onClick={() => {
                    commitGrossFromDraft();
                    setShowDetailModal(false);
                  }}
                >
                  Cancel
                </Button>

                {canUpdate && (
                  <Button onClick={() => save()} disabled={isSaving} className="min-w-[100px]">
                    {isSaving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Edit className="h-4 w-4 mr-2" />}
                    {isSaving ? 'Saving...' : 'Update'}
                  </Button>
                )}

                {canDelete && (
                  <Button variant="destructive" onClick={handleDelete} disabled={isSaving} className="min-w-[100px]">
                    <Trash2 className="h-4 w-4 mr-2" />
                    Delete
                  </Button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

export default React.memo(VehicleEntryRow, (prev, next) => {
  return (
    prev.entry === next.entry &&
    prev.serialNumber === next.serialNumber &&
    prev.canUpdate === next.canUpdate &&
    prev.canDelete === next.canDelete &&
    prev.date === next.date &&
    prev.shift === next.shift &&
    prev.showCreatedByColumn === next.showCreatedByColumn &&
    prev.onEntryUpdated === next.onEntryUpdated &&
    prev.onEntryDeleted === next.onEntryDeleted
  );
});
