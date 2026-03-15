import React, { useState, useRef, useCallback, useEffect } from 'react';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Check, Loader2, MoreHorizontal, Trash2, Edit, X } from 'lucide-react';

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

interface VehicleEntryRowProps {
  entry: DailyVehicleEntry;
  serialNumber: number;
  date: string;
  shift: number;
  onEntryUpdated?: (entry: DailyVehicleEntry) => void;
  onEntryDeleted?: (id: number) => void;
}

export default function VehicleEntryRow({
  entry,
  serialNumber,
  date,
  shift,
  onEntryUpdated,
  onEntryDeleted,
}: VehicleEntryRowProps) {
  const [isSaving, setIsSaving] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [showContextMenu, setShowContextMenu] = useState(false);

  const [formData, setFormData] = useState({
    e_challan_no: entry.e_challan_no || '',
    vehicle_no: entry.vehicle_no || '',
    trip_id_no: entry.trip_id_no || '',
    transport_name: entry.transport_name || '',
    gross_wt: entry.gross_wt?.toString() || '',
    tare_wt: entry.tare_wt?.toString() || '',
    wb_no: entry.wb_no || '',
    d_challan_no: entry.d_challan_no || '',
    challan_mode: entry.challan_mode || '',
    status: entry.status || 'draft',
  });

  const formDataRef = useRef(formData);
  useEffect(() => {
    formDataRef.current = formData;
  }, [formData]);

  const updateField = (field: keyof typeof formData, value: string) => {
    setFormData((prev: any) => ({ ...prev, [field]: value }));
  };

  // Single stable ref for the debounce timer
  const debounceTimerRef = useRef<NodeJS.Timeout>();

  const save = useCallback(async () => {
    const dataToSave = formDataRef.current;
    setIsSaving(true);
    setShowSuccess(false);
    setSaveError(null);
    try {
      const res = await fetch(`/road-dispatch/daily-vehicle-entries/${entry.id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        body: JSON.stringify(dataToSave),
        credentials: 'include',
      });
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
      if (updated) onEntryUpdated?.(updated);
      setShowSuccess(true);
      setTimeout(() => setShowSuccess(false), 2000);
    } catch {
      setSaveError('Network error. Please try again.');
    } finally {
      setIsSaving(false);
    }
  }, [entry.id, entry, onEntryUpdated, onEntryDeleted]);

  const debouncedSave = useCallback(() => {
    clearTimeout(debounceTimerRef.current);
    debounceTimerRef.current = setTimeout(save, 1000);
  }, [save]);

  // Clean up debounce timer on unmount
  useEffect(() => {
    return () => clearTimeout(debounceTimerRef.current);
  }, []);

  // Check if entry has any meaningful values (not default/empty)
  const hasMeaningfulValues = () => {
    return !!(
      entry.e_challan_no?.trim() ||
      entry.vehicle_no?.trim() ||
      entry.trip_id_no?.trim() ||
      entry.transport_name?.trim() ||
      entry.gross_wt ||
      entry.tare_wt ||
      entry.wb_no?.trim() ||
      entry.d_challan_no?.trim() ||
      entry.challan_mode
    );
  };

  // Check if all key fields are empty for delete button
  const shouldShowDeleteButton = () => {
    return (
      !entry.e_challan_no?.trim() &&
      !entry.vehicle_no?.trim() &&
      !entry.trip_id_no?.trim() &&
      !entry.transport_name?.trim() &&
      !entry.gross_wt &&
      !entry.tare_wt &&
      entry.status === 'draft'
    );
  };

  const handleDelete = async () => {
    setShowDetailModal(false);
    if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
      return;
    }
    setSaveError(null);
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

  const handleMarkCompleted = async () => {
    setIsSaving(true);
    setSaveError(null);
    try {
      const res = await fetch(`/road-dispatch/daily-vehicle-entries/${entry.id}/complete`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        body: JSON.stringify({}),
        credentials: 'include',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        const msg =
          (data as { message?: string }).message ??
          (res.status === 419 ? 'Session expired. Please refresh the page.' : 'Failed to mark completed.');
        setSaveError(msg);
        return;
      }
      const updated = (data as { entry?: DailyVehicleEntry }).entry;
      if (updated) onEntryUpdated?.(updated);
      setShowSuccess(true);
      setTimeout(() => setShowSuccess(false), 2000);
    } catch {
      setSaveError('Network error. Please try again.');
    } finally {
      setIsSaving(false);
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

  const calculateNetWeight = () => {
    const gross = parseFloat(formData.gross_wt) || 0;
    const tare = parseFloat(formData.tare_wt) || 0;
    return (gross - tare).toFixed(2);
  };

  return (
    <>
      <TableRow
        className={`group relative ${entry.status === 'draft' ? 'bg-red-50/30 dark:bg-red-950/20' : ''}`}
        onMouseEnter={() => setShowContextMenu(true)}
        onMouseLeave={() => setShowContextMenu(false)}
      >
        <TableCell className="px-2 py-3 font-medium border-t border-r border-gray-300 min-h-[4rem]">
          <div className="flex items-center gap-2">
            <div className={`opacity-0 group-hover:opacity-100 transition-opacity duration-200 ${showContextMenu ? 'opacity-100' : ''}`}>
              <Button
                size="sm"
                variant="ghost"
                onClick={() => setShowDetailModal(true)}
                className="h-9 w-9 p-0 min-w-0"
                title="More options"
              >
                <MoreHorizontal className="h-3 w-3" />
              </Button>
            </div>
            {serialNumber}
          </div>
        </TableCell>

        <TableCell className="px-2 py-3 text-muted-foreground text-xs whitespace-nowrap border-t border-r border-gray-300 min-h-[4rem]" title="Siding (read-only)">
          {entry.siding?.name ?? '—'}
        </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.e_challan_no}
          onChange={(e) => {
            updateField('e_challan_no', e.target.value);
            debouncedSave();
          }}
          placeholder="E-CH"
          className="w-24 h-12 px-2 text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.vehicle_no}
          onChange={(e) => {
            updateField('vehicle_no', e.target.value);
          }}
          onBlur={async () => {
            const vehicleNo = formDataRef.current.vehicle_no.trim();
            if (!vehicleNo) {
              return;
            }
            try {
              const res = await fetch(
                `/road-dispatch/vehicle-workorders/lookup?vehicle_no=${encodeURIComponent(vehicleNo)}`,
                {
                  method: 'GET',
                  headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                  },
                  credentials: 'include',
                }
              );

              if (!res.ok) {
                return;
              }

              const data = (await res.json()) as {
                tare_wt?: number | null;
                transport_name?: string | null;
              };

              setFormData((prev) => {
                const next = { ...prev };
                if (!next.tare_wt && data.tare_wt != null) {
                  next.tare_wt = data.tare_wt.toString();
                }
                if (!next.transport_name && data.transport_name) {
                  next.transport_name = data.transport_name;
                }
                return next;
              });

              debouncedSave();
            } catch {
              // fail silently; user can still enter manually
            }
          }}
          placeholder="VEH"
          className="w-24 h-12 px-2 text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.trip_id_no}
          onChange={(e) => {
            updateField('trip_id_no', e.target.value);
            debouncedSave();
          }}
          placeholder="TRIP"
          className="w-20 h-12 px-2 text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.transport_name}
          onChange={(e) => {
            updateField('transport_name', e.target.value);
            debouncedSave();
          }}
          placeholder="TRANS"
          className="w-32 h-12 px-2 text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          type="number"
          step="0.01"
          value={formData.gross_wt}
          onChange={(e) => {
            updateField('gross_wt', e.target.value);
            debouncedSave();
          }}
          placeholder="G2"
          className="w-20 h-12 px-2 text-right text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          type="number"
          step="0.01"
          value={formData.tare_wt}
          onChange={(e) => {
            updateField('tare_wt', e.target.value);
            debouncedSave();
          }}
          placeholder="T1"
          className="w-20 h-12 px-2 text-right text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 font-medium text-blue-600 text-right text-xs border-t border-r border-gray-300 min-h-[4rem]">
        {calculateNetWeight()}
      </TableCell>

      <TableCell className="px-2 py-3 text-xs text-gray-600 border-t border-r border-gray-300 min-h-[4rem]">
        {formatDateTime(entry.reached_at)}
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.wb_no}
          onChange={(e) => {
            updateField('wb_no', e.target.value);
            debouncedSave();
          }}
          placeholder="WB"
          className="w-20 h-12 px-2 text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.d_challan_no}
          onChange={(e) => {
            updateField('d_challan_no', e.target.value);
            debouncedSave();
          }}
          placeholder="D-CH"
          className="w-24 h-12 px-2 text-xs"
        />
      </TableCell>

      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Select
          value={formData.challan_mode}
          onValueChange={(value) => {
            updateField('challan_mode', value as 'offline' | 'online');
            clearTimeout(debounceTimerRef.current);
            setTimeout(save, 50);
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

      <TableCell className="px-2 py-3 border-t border-gray-300 min-h-[4rem]">
        {entry.status === 'completed' ? (
          <Badge variant="default" className="text-xs px-2 py-0.5">Stock updated</Badge>
        ) : hasMeaningfulValues() ? (
          <Badge variant="secondary" className="text-xs px-2 py-0.5">In progress</Badge>
        ) : (
          <span className="text-xs text-muted-foreground">Empty</span>
        )}
      </TableCell>
      </TableRow>

      {/* Detail Modal */}
      {showDetailModal && (
        <div className="fixed inset-0 bg-gray-500 bg-opacity-30 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-semibold">Vehicle Entry Details</h2>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setShowDetailModal(false)}
                className="h-8 w-8 p-0"
              >
                <X className="h-4 w-4" />
              </Button>
            </div>

            <div className="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">E Challan No</label>
                <Input
                  value={formData.e_challan_no}
                  onChange={(e) => updateField('e_challan_no', e.target.value)}
                  placeholder="E Challan No"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Vehicle No</label>
                <Input
                  value={formData.vehicle_no}
                  onChange={(e) => updateField('vehicle_no', e.target.value)}
                  placeholder="Vehicle No"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Gross WT (G2)</label>
                <Input
                  type="number"
                  step="0.01"
                  value={formData.gross_wt}
                  onChange={(e) => updateField('gross_wt', e.target.value)}
                  placeholder="Gross WT"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tare WT (T1)</label>
                <Input
                  type="number"
                  step="0.01"
                  value={formData.tare_wt}
                  onChange={(e) => updateField('tare_wt', e.target.value)}
                  placeholder="Tare WT"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Net Weight</label>
                <Input
                  value={calculateNetWeight()}
                  disabled
                  className="bg-gray-50"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">WB No</label>
                <Input
                  value={formData.wb_no}
                  onChange={(e) => updateField('wb_no', e.target.value)}
                  placeholder="WB No"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">D Challan No</label>
                <Input
                  value={formData.d_challan_no}
                  onChange={(e) => updateField('d_challan_no', e.target.value)}
                  placeholder="D Challan No"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Challan Mode</label>
                <Select
                  value={formData.challan_mode}
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
                <Select
                  value={formData.status}
                  onValueChange={(value) => updateField('status', value)}
                >
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

              {saveError && (
                <p className="text-sm text-destructive mb-2">{saveError}</p>
              )}
              <div className="flex justify-end gap-2">
                <Button
                  variant="outline"
                  onClick={() => setShowDetailModal(false)}
                >
                  Cancel
                </Button>

                <Button
                  onClick={save}
                  disabled={isSaving}
                  className="min-w-[100px]"
                >
                  {isSaving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Edit className="h-4 w-4 mr-2" />}
                  {isSaving ? 'Saving...' : 'Update'}
                </Button>

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
        </div>
      )}
    </>
  );
}