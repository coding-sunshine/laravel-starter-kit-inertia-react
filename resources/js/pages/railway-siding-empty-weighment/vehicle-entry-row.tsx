import React, { useCallback, useEffect, useRef, useState } from 'react';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Loader2, Pencil, Trash2, X } from 'lucide-react';
import {
  type Meridiem,
  reachedAtIsoTo12hParts,
  sheetDateAnd12hToReachedAtLocalString,
} from './reached-at-time';

export interface EmptyWeighmentEntry {
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
  inline_submitted_at: string | null;
}

const numberInputNoSpinner =
  '[&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [appearance:textfield]';

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

type InlineFormState = {
  vehicle_no: string;
  transport_name: string;
  tare_wt_two: string;
  status: string;
  /** 1–12 as string (Select values) */
  reached_hour: string;
  /** 0–59 */
  reached_minute: string;
  reached_meridiem: Meridiem;
};

function tareDigitsOnly(value: string): string {
  return value.replace(/\D+/g, '').slice(0, 4);
}

function normalizeTareDigits4(digits: string): string {
  const d = tareDigitsOnly(digits);
  if (d.length === 0) {
    return '';
  }
  if (d.length <= 2) {
    // Treat 1–2 digits as whole MT: "16" => "1600" (16.00)
    return d.padStart(2, '0') + '00';
  }
  if (d.length === 3) {
    // "123" => "1230" (12.30)
    return d + '0';
  }
  return d;
}

/** Convert 4 digits to display with dot after 2 digits (e.g. 1234 -> 12.34). */
function digitsToTareDisplay(digits: string): string {
  const d4 = normalizeTareDigits4(digits);
  if (d4.length === 0) {
    return '';
  }
  return `${d4.slice(0, 2)}.${d4.slice(2)}`;
}

/** Parse T2 to MT using 2-decimal fixed digits entry (e.g. "12.34" -> 12.34). */
function parseTareInputToMt(value: string): number | null {
  const d4 = normalizeTareDigits4(value);
  if (d4.length === 0) {
    return null;
  }
  const n = Number(d4) / 100;
  return Number.isFinite(n) ? n : null;
}

function entryToFormState(entry: EmptyWeighmentEntry): InlineFormState {
  const p = reachedAtIsoTo12hParts(entry.reached_at);
  return {
    vehicle_no: entry.vehicle_no || '',
    transport_name: entry.transport_name || '',
    tare_wt_two:
      entry.tare_wt_two != null
        ? digitsToTareDisplay(
            String(Math.max(0, Math.round(Number(entry.tare_wt_two) * 100))),
          )
        : '',
    status: entry.status || 'draft',
    reached_hour: String(p.hour),
    reached_minute: String(p.minute).padStart(2, '0'),
    reached_meridiem: p.meridiem,
  };
}

const HOUR_OPTIONS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12] as const;
const MINUTE_OPTIONS = Array.from({ length: 60 }, (_, i) => i);

const YELLOW_TAB_FOCUS_RING_CLASS =
  'focus-visible:ring-4 focus-visible:ring-yellow-600 focus-visible:ring-offset-2 focus-visible:ring-offset-background';

function buildReachedAtFromForm(fd: InlineFormState, sheetYmd: string): string {
  const hour = Math.min(12, Math.max(1, parseInt(fd.reached_hour, 10) || 1));
  const minute = Math.min(59, Math.max(0, parseInt(fd.reached_minute, 10) || 0));
  return sheetDateAnd12hToReachedAtLocalString(sheetYmd, {
    hour,
    minute,
    meridiem: fd.reached_meridiem,
  });
}

type ReachedAt12hFieldsProps = {
  formData: Pick<InlineFormState, 'reached_hour' | 'reached_minute' | 'reached_meridiem'>;
  canUpdate: boolean;
  onChange: (field: 'reached_hour' | 'reached_minute' | 'reached_meridiem', value: string) => void;
  sheetDateYmd: string;
  compact?: boolean;
};

/** Modal: wider triggers + tight padding override `SelectTrigger`’s `px-3`/`gap-2` so values aren’t clipped. */
const REACHED_AT_MODAL_TRIGGER_HOUR_MIN =
  'h-9 w-[5.5rem] min-w-[5.5rem] shrink-0 px-1.5 text-sm gap-1';
const REACHED_AT_MODAL_TRIGGER_MERIDIEM = 'h-9 w-16 min-w-16 shrink-0 px-1.5 text-sm gap-1';

function ReachedAt12hFields({
  formData,
  canUpdate,
  onChange,
  sheetDateYmd,
  compact = true,
}: ReachedAt12hFieldsProps) {
  return (
    <div
      className={compact ? 'flex flex-wrap items-center gap-1' : 'flex flex-wrap items-end gap-2 w-full min-w-0'}
    >
      <span
        className={`text-muted-foreground tabular-nums ${compact ? 'text-[10px]' : 'shrink-0 text-xs'}`}
        title="Sheet date (not editable)"
      >
        {sheetDateYmd}
      </span>
      <Select
        value={formData.reached_hour}
        disabled={!canUpdate}
        onValueChange={(v) => onChange('reached_hour', v)}
      >
        <SelectTrigger
          className={
            compact
              ? `h-8 w-[4.5rem] text-xs px-1 ${YELLOW_TAB_FOCUS_RING_CLASS}`
              : `${REACHED_AT_MODAL_TRIGGER_HOUR_MIN} ${YELLOW_TAB_FOCUS_RING_CLASS}`
          }
        >
          <SelectValue placeholder="Hr" />
        </SelectTrigger>
        <SelectContent>
          {HOUR_OPTIONS.map((h) => (
            <SelectItem key={h} value={String(h)} className="text-xs">
              {h}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      <span className="text-muted-foreground text-xs">:</span>
      <Select
        value={formData.reached_minute}
        disabled={!canUpdate}
        onValueChange={(v) => onChange('reached_minute', v)}
      >
        <SelectTrigger
          className={
            compact
              ? `h-8 w-[4.5rem] text-xs px-1 ${YELLOW_TAB_FOCUS_RING_CLASS}`
              : `${REACHED_AT_MODAL_TRIGGER_HOUR_MIN} ${YELLOW_TAB_FOCUS_RING_CLASS}`
          }
        >
          <SelectValue placeholder="Min" />
        </SelectTrigger>
        <SelectContent className="max-h-48">
          {MINUTE_OPTIONS.map((m) => (
            <SelectItem key={m} value={String(m)} className="text-xs">
              {String(m).padStart(2, '0')}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      <Select
        value={formData.reached_meridiem}
        disabled={!canUpdate}
        onValueChange={(v) => onChange('reached_meridiem', v as Meridiem)}
      >
        <SelectTrigger
          className={
            compact
              ? `h-8 w-[3.5rem] text-xs px-1 ${YELLOW_TAB_FOCUS_RING_CLASS}`
              : `${REACHED_AT_MODAL_TRIGGER_MERIDIEM} ${YELLOW_TAB_FOCUS_RING_CLASS}`
          }
        >
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="AM" className="text-xs">
            AM
          </SelectItem>
          <SelectItem value="PM" className="text-xs">
            PM
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
  );
}

type VehicleLookupResponse = {
  transport_name?: string | null;
};

async function fetchVehicleWorkorderHints(
  vehicleNo: string,
): Promise<VehicleLookupResponse | null> {
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

function focusNextRowFirstField(fromElement: HTMLElement): boolean {
  const currentRow = fromElement.closest('tr');
  const nextRow = currentRow?.nextElementSibling;
  if (!(nextRow instanceof HTMLElement)) {
    return false;
  }

  const first = nextRow.querySelector<HTMLElement>(
    '[data-field="vehicle_no"]',
  );
  if (!first) {
    return false;
  }

  first.focus();
  // @ts-expect-error HTMLInputElement#select exists; HTMLElement typing is generic here
  first.select?.();
  return true;
}

interface VehicleEntryRowProps {
  entry: EmptyWeighmentEntry;
  serialNumber: number;
  date: string;
  shift: number;
  canUpdate?: boolean;
  canDelete?: boolean;
  onEntryUpdated?: (
    entry: EmptyWeighmentEntry,
    context?: { replaceClientId?: number; inlineSubmitted?: boolean; wasLocalDraft?: boolean },
  ) => void;
  onEntryDeleted?: (id: number) => void;
}

export default function VehicleEntryRow({
  entry,
  serialNumber,
  date,
  shift,
  canUpdate = false,
  canDelete = false,
  onEntryUpdated,
  onEntryDeleted,
}: VehicleEntryRowProps) {
  const isLocalDraft = entry.id < 0;
  const isCommitted = Boolean(entry.inline_submitted_at);
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [showDetailModal, setShowDetailModal] = useState(false);

  const [formData, setFormData] = useState<InlineFormState>(() =>
    entryToFormState(entry),
  );

  const formDataRef = useRef(formData);
  const autoTransportNameRef = useRef(true);
  /** Digits-only buffer while T2 is focused; dot applied only on blur. */
  const [tareDigitsLocal, setTareDigitsLocal] = useState(() =>
    tareDigitsOnly(formData.tare_wt_two),
  );
  const [isTareFocused, setIsTareFocused] = useState(false);
  const tareDigitsLocalRef = useRef(tareDigitsLocal);
  const tareFocusedRef = useRef(false);
  useEffect(() => {
    formDataRef.current = formData;
  }, [formData]);

  useEffect(() => {
    tareDigitsLocalRef.current = tareDigitsLocal;
  }, [tareDigitsLocal]);

  useEffect(() => {
    tareFocusedRef.current = isTareFocused;
  }, [isTareFocused]);

  useEffect(() => {
    const next = entryToFormState(entry);
    // eslint-disable-next-line @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- controlled reset when entry changes
    setFormData(next);
    setIsTareFocused(false);
    setTareDigitsLocal(tareDigitsOnly(next.tare_wt_two));
    autoTransportNameRef.current = true;
  }, [entry.id, entry.inline_submitted_at, entry.created_at, entry.reached_at]);

  const updateField = (field: keyof InlineFormState, value: string) => {
    if (field === 'transport_name') {
      autoTransportNameRef.current = value.trim() === '';
    }
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleVehicleNoBlur = useCallback(async () => {
    const hints = await fetchVehicleWorkorderHints(
      formDataRef.current.vehicle_no,
    );
    if (hints?.transport_name) {
      setFormData((prev) => {
        const shouldOverwrite =
          autoTransportNameRef.current || prev.transport_name.trim() === '';
        if (!shouldOverwrite) {
          return prev;
        }
        return { ...prev, transport_name: hints.transport_name ?? '' };
      });
      autoTransportNameRef.current = true;
    }
  }, []);

  const handleTareFocus = () => {
    tareFocusedRef.current = true;
    setIsTareFocused(true);
    setTareDigitsLocal(tareDigitsOnly(formDataRef.current.tare_wt_two));
  };

  const commitTareFromDraft = useCallback(() => {
    if (!tareFocusedRef.current) {
      return;
    }
    const digits = tareDigitsLocalRef.current;
    const formatted = digitsToTareDisplay(digits);
    tareFocusedRef.current = false;
    setIsTareFocused(false);
    setFormData((prev) => ({ ...prev, tare_wt_two: formatted }));
  }, []);

  const save = useCallback(
    async (options?: { inlineSubmit?: boolean }) => {
      if (!canUpdate) {
        return;
      }
      setIsSaving(true);
      setSaveError(null);

      const wasTareFocused = tareFocusedRef.current;
      const tareCommitted = wasTareFocused
        ? digitsToTareDisplay(tareDigitsLocalRef.current)
        : formData.tare_wt_two;
      if (wasTareFocused) {
        tareFocusedRef.current = false;
        setIsTareFocused(false);
        setFormData((prev) => ({ ...prev, tare_wt_two: tareCommitted }));
        setTareDigitsLocal(tareDigitsOnly(tareCommitted));
      }

      // Use `formData` (state), not only `formDataRef`, so hour/min/AM-PM always match the last render (ref can lag by one effect tick).
      const dataToSave: InlineFormState = { ...formData, tare_wt_two: tareCommitted };
      const tareMt = parseTareInputToMt(dataToSave.tare_wt_two);

      try {
        let res: Response;

        if (isLocalDraft) {
          const storeBody: Record<string, unknown> = {
            siding_id: entry.siding_id,
            entry_date: date,
            shift,
            vehicle_no: dataToSave.vehicle_no.trim() || null,
            transport_name: dataToSave.transport_name.trim() || null,
            tare_wt_two: tareMt,
            status: dataToSave.status || 'draft',
            reached_at: buildReachedAtFromForm(dataToSave, date),
          };
          if (options?.inlineSubmit) {
            storeBody.inline_submit = true;
          }

          res = await fetch('/railway-siding-empty-weighment', {
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
          const { reached_hour, reached_minute, reached_meridiem, ...patchRest } = dataToSave;
          const body: Record<string, unknown> = {
            ...patchRest,
            vehicle_no: dataToSave.vehicle_no.trim() || null,
            transport_name: dataToSave.transport_name.trim() || null,
            tare_wt_two: tareMt,
            status: dataToSave.status || 'draft',
            reached_at: buildReachedAtFromForm(dataToSave, date),
          };
          void [reached_hour, reached_minute, reached_meridiem];
          if (options?.inlineSubmit) {
            body.inline_submit = true;
          }

          res = await fetch(`/railway-siding-empty-weighment/${entry.id}`, {
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
            (typeof (data as { errors?: Record<string, string[]> }).errors ===
            'object'
              ? Object.values(
                  (data as { errors: Record<string, string[]> }).errors,
                )
                  .flat()
                  .join(', ')
              : null) ??
            res.statusText ??
            'Save failed';
          setSaveError(
            res.status === 419
              ? 'Session expired. Please refresh the page.'
              : msg,
          );
          return;
        }

        const updated = (data as { entry?: EmptyWeighmentEntry }).entry;
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

        setShowDetailModal(false);
      } catch {
        setSaveError('Network error. Please try again.');
      } finally {
        setIsSaving(false);
      }
    },
    [
      canUpdate,
      date,
      entry.id,
      entry.siding_id,
      formData,
      isLocalDraft,
      onEntryDeleted,
      onEntryUpdated,
      shift,
    ],
  );

  const shouldShowDeleteButton = () => {
    if (isLocalDraft) {
      return true;
    }
    if (canDelete) {
      return true;
    }
    return entry.status === 'draft';
  };

  const hasMeaningfulValues = () => {
    if (isLocalDraft) {
      return Boolean(
        formData.vehicle_no.trim() ||
          formData.transport_name.trim() ||
          formData.tare_wt_two.trim(),
      );
    }
    return Boolean(
      entry.vehicle_no?.trim() ||
        entry.transport_name?.trim() ||
        entry.tare_wt_two,
    );
  };

  const handleDelete = async () => {
    if (!shouldShowDeleteButton()) {
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
      const res = await fetch(`/railway-siding-empty-weighment/${entry.id}`, {
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
      hour12: true,
    });
  };

  const entryTime = entry.reached_at || entry.created_at;

  return (
    <>
      <TableRow
        className={entry.status === 'draft' ? 'bg-red-50/30 dark:bg-red-950/20' : ''}
      >
        <TableCell className="px-2 py-3 font-medium border-t border-r border-gray-300 min-h-[4rem] text-center">
          {serialNumber}
        </TableCell>

        <TableCell className="px-2 py-3 text-xs border-t border-r border-gray-300 min-h-[4rem] text-muted-foreground">
          {entry.siding?.name ?? '—'}
        </TableCell>

        {isCommitted ? (
          <>
            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-xs">
              {entry.vehicle_no?.trim() || '—'}
            </TableCell>
            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-xs">
              {entry.transport_name?.trim() || '—'}
            </TableCell>
            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-right text-xs tabular-nums">
              {entry.tare_wt_two != null ? Number(entry.tare_wt_two).toFixed(2) : '—'}
            </TableCell>
            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-xs text-gray-600 whitespace-nowrap">
              {entryTime ? formatDateTime(entryTime) : '—'}
            </TableCell>
            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
              {entry.status === 'completed' ? (
                <Badge variant="default" className="text-xs px-2 py-0.5">
                  Completed
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
                {saveError && !showDetailModal && (
                  <p className="text-[10px] text-destructive">{saveError}</p>
                )}
              </div>
            </TableCell>
          </>
        ) : (
          <>
            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
              <Input
                data-field="vehicle_no"
                value={formData.vehicle_no}
                disabled={!canUpdate}
                onChange={(e) => updateField('vehicle_no', e.target.value)}
                onBlur={handleVehicleNoBlur}
                placeholder="Vehicle No"
                className="w-28 h-12 px-2 text-xs"
              />
            </TableCell>

            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
              <Input
                value={formData.transport_name}
                disabled={!canUpdate}
                onChange={(e) => updateField('transport_name', e.target.value)}
                placeholder="Transporter Name"
                className="w-40 h-12 px-2 text-xs"
              />
            </TableCell>

            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
              <Input
                type="text"
                inputMode="numeric"
                autoComplete="off"
                value={isTareFocused ? tareDigitsLocal : formData.tare_wt_two}
                disabled={!canUpdate}
                onFocus={handleTareFocus}
                onChange={(e) =>
                  setTareDigitsLocal(tareDigitsOnly(e.target.value))
                }
                onBlur={commitTareFromDraft}
                placeholder="T2"
                className={`w-24 h-12 px-2 text-right text-xs ${numberInputNoSpinner}`}
              />
            </TableCell>

            <TableCell
              className={`px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] align-top !min-h-[4.5rem] ${!canUpdate ? 'text-muted-foreground' : ''}`}
              title="Entry time (12h) — date follows sheet"
            >
              {canUpdate ? (
                <ReachedAt12hFields
                  canUpdate={canUpdate}
                  formData={formData}
                  sheetDateYmd={date}
                  onChange={(field, v) => updateField(field, v)}
                  compact
                />
              ) : entryTime ? (
                <span className="text-xs text-gray-600 whitespace-nowrap">{formatDateTime(entryTime)}</span>
              ) : (
                '—'
              )}
            </TableCell>

            <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
              {entry.status === 'completed' ? (
                <Badge variant="default" className="text-xs px-2 py-0.5">
                  Completed
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
                    className={`h-8 text-xs ${YELLOW_TAB_FOCUS_RING_CLASS}`}
                    disabled={isSaving}
                    onClick={() => void save({ inlineSubmit: true })}
                  >
                    {isSaving ? (
                      <Loader2 className="h-3.5 w-3.5 animate-spin" />
                    ) : (
                      'Submit'
                    )}
                  </Button>
                )}
                {canDelete && shouldShowDeleteButton() && (
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="h-8 text-xs"
                    disabled={isSaving}
                    onClick={handleDelete}
                    onKeyDown={(event) => {
                      if (event.key === 'Tab' && !event.shiftKey) {
                        const moved = focusNextRowFirstField(
                          event.currentTarget,
                        );
                        if (moved) {
                          event.preventDefault();
                        }
                      }
                    }}
                  >
                    Delete
                  </Button>
                )}
                {saveError && (
                  <p className="text-[10px] text-destructive">{saveError}</p>
                )}
              </div>
            </TableCell>
          </>
        )}
      </TableRow>

      {showDetailModal && (
        <div className="fixed inset-0 bg-gray-500 bg-opacity-30 flex items-center justify-center z-50">
          <div className="bg-white dark:bg-gray-900 rounded-lg p-6 w-full max-w-md overflow-y-auto">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-semibold">Empty Weighment Entry</h2>
              <Button variant="ghost" size="sm" onClick={() => setShowDetailModal(false)} className="h-8 w-8 p-0">
                <X className="h-4 w-4" />
              </Button>
            </div>

            <div className="grid gap-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vehicle No</label>
                <Input
                  value={formData.vehicle_no}
                  disabled={!canUpdate}
                  onChange={(e) => updateField('vehicle_no', e.target.value)}
                  onBlur={handleVehicleNoBlur}
                  placeholder="Vehicle No"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transporter Name</label>
                <Input
                  value={formData.transport_name}
                  disabled={!canUpdate}
                  onChange={(e) => updateField('transport_name', e.target.value)}
                  placeholder="Transporter Name"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tare WT (T2)</label>
                <Input
                  type="text"
                  inputMode="numeric"
                  autoComplete="off"
                  value={isTareFocused ? tareDigitsLocal : formData.tare_wt_two}
                  disabled={!canUpdate}
                  onFocus={handleTareFocus}
                  onChange={(e) =>
                    setTareDigitsLocal(tareDigitsOnly(e.target.value))
                  }
                  onBlur={commitTareFromDraft}
                  placeholder="T2"
                  className={numberInputNoSpinner}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select
                  value={formData.status}
                  disabled={!canUpdate}
                  onChange={(e) => updateField('status', e.target.value)}
                  className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="draft">Draft</option>
                  <option value="completed">Completed</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Entry time</label>
                {canUpdate ? (
                  <ReachedAt12hFields
                    canUpdate={canUpdate}
                    formData={formData}
                    sheetDateYmd={date}
                    onChange={(field, v) => updateField(field, v)}
                    compact={false}
                  />
                ) : (
                  <p className="text-sm text-muted-foreground">{entryTime ? formatDateTime(entryTime) : '—'}</p>
                )}
              </div>
            </div>

            {saveError && <p className="text-sm text-destructive mb-2">{saveError}</p>}
            <div className="flex flex-wrap justify-end gap-2">
              <Button variant="outline" onClick={() => setShowDetailModal(false)}>
                Cancel
              </Button>
              {canUpdate && (
                <Button
                  onClick={() => void save()}
                  disabled={isSaving}
                  className="min-w-[100px]"
                >
                  {isSaving ? (
                    <Loader2 className="h-4 w-4 animate-spin mr-2" />
                  ) : null}
                  {isSaving ? 'Saving...' : 'Update'}
                </Button>
              )}
              {shouldShowDeleteButton() && (
                <Button
                  variant="destructive"
                  onClick={handleDelete}
                  disabled={isSaving}
                  className="min-w-[100px]"
                >
                  <Trash2 className="h-4 w-4 mr-2" />
                  Delete
                </Button>
              )}
            </div>
          </div>
        </div>
      )}
    </>
  );
}
