import React, { useState, useRef, useCallback, useEffect } from 'react';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Check, Loader2, MoreHorizontal, Trash2, X } from 'lucide-react';

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
  entry: EmptyWeighmentEntry;
  serialNumber: number;
  onEntryUpdated?: (entry: EmptyWeighmentEntry) => void;
  onEntryDeleted?: (id: number) => void;
}

export default function VehicleEntryRow({
  entry,
  serialNumber,
  onEntryUpdated,
  onEntryDeleted,
}: VehicleEntryRowProps) {
  const [isSaving, setIsSaving] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [showDetailModal, setShowDetailModal] = useState(false);

  const [formData, setFormData] = useState({
    vehicle_no: entry.vehicle_no || '',
    transport_name: entry.transport_name || '',
    tare_wt_two: entry.tare_wt_two?.toString() || '',
  });

  const formDataRef = useRef(formData);
  useEffect(() => {
    formDataRef.current = formData;
  }, [formData]);

  const updateField = (field: keyof typeof formData, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const debounceTimerRef = useRef<NodeJS.Timeout>();

  const save = useCallback(async () => {
    const dataToSave = formDataRef.current;
    setIsSaving(true);
    setShowSuccess(false);
    setSaveError(null);
    try {
      const res = await fetch(`/railway-siding-empty-weighment/${entry.id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        body: JSON.stringify({
          vehicle_no: dataToSave.vehicle_no || null,
          transport_name: dataToSave.transport_name || null,
          tare_wt_two: dataToSave.tare_wt_two ? parseFloat(dataToSave.tare_wt_two) : null,
        }),
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
      const updated = (data as { entry?: EmptyWeighmentEntry }).entry;
      if (updated) onEntryUpdated?.(updated);
      setShowSuccess(true);
      setTimeout(() => setShowSuccess(false), 2000);
    } catch {
      setSaveError('Network error. Please try again.');
    } finally {
      setIsSaving(false);
    }
  }, [entry.id, onEntryUpdated, onEntryDeleted]);

  const debouncedSave = useCallback(() => {
    clearTimeout(debounceTimerRef.current);
    debounceTimerRef.current = setTimeout(save, 1000);
  }, [save]);

  useEffect(() => {
    return () => clearTimeout(debounceTimerRef.current);
  }, []);

  const shouldShowDeleteButton = () =>
    !entry.vehicle_no?.trim() && !entry.transport_name?.trim() && !entry.tare_wt_two && entry.status === 'draft';

  const handleDelete = async () => {
    setShowDetailModal(false);
    if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
      return;
    }
    setSaveError(null);
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

  const handleMarkCompleted = async () => {
    setIsSaving(true);
    setSaveError(null);
    try {
      const res = await fetch(`/railway-siding-empty-weighment/${entry.id}/complete`, {
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
      const updated = (data as { entry?: EmptyWeighmentEntry }).entry;
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

  const entryTime = entry.reached_at || entry.created_at;

  return (
    <>
      <TableRow
        className={`group relative ${entry.status === 'draft' ? 'bg-red-50/30 dark:bg-red-950/20' : ''}`}
        onMouseEnter={() => {}}
        onMouseLeave={() => {}}
      >
        <TableCell className="px-2 py-3 font-medium border-t border-r border-gray-300 min-h-[4rem]">
          <div className="flex items-center gap-2">
            <div className="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
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

        <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
          <Input
            value={formData.vehicle_no}
            onChange={(e) => {
              updateField('vehicle_no', e.target.value);
              debouncedSave();
            }}
            onBlur={async () => {
              const vehicleNo = formDataRef.current.vehicle_no.trim();
              if (!vehicleNo) return;
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
                if (!res.ok) return;
                const data = (await res.json()) as { transport_name?: string | null };
                setFormData((prev) => {
                  const next = { ...prev };
                  if (data.transport_name) {
                    next.transport_name = data.transport_name;
                  }
                  return next;
                });
                debouncedSave();
              } catch {
                // fail silently; user can enter transporter manually
              }
            }}
            placeholder="Vehicle No"
            className="w-28 h-12 px-2 text-xs"
          />
        </TableCell>

        <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
          <Input
            value={formData.transport_name}
            onChange={(e) => {
              updateField('transport_name', e.target.value);
              debouncedSave();
            }}
            placeholder="Transporter Name"
            className="w-40 h-12 px-2 text-xs"
          />
        </TableCell>

        <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
          <Input
            type="number"
            step="0.01"
            value={formData.tare_wt_two}
            onChange={(e) => {
              updateField('tare_wt_two', e.target.value);
              debouncedSave();
            }}
            placeholder="T2"
            className="w-24 h-12 px-2 text-right text-xs"
          />
        </TableCell>

        <TableCell className="px-2 py-3 text-xs text-gray-600 border-t border-r border-gray-300 min-h-[4rem]">
          {entryTime ? formatDateTime(entryTime) : '—'}
        </TableCell>

        <TableCell className="px-2 py-3 border-t border-gray-300 min-h-[4rem]">
          {entry.status === 'completed' ? (
            <Badge variant="default" className="text-xs px-2 py-0.5">
              Completed
            </Badge>
          ) : (
            <Badge variant="secondary" className="text-xs px-2 py-0.5">
              Draft
            </Badge>
          )}
        </TableCell>
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
                  onChange={(e) => updateField('vehicle_no', e.target.value)}
                  onBlur={async () => {
                    const vehicleNo = formDataRef.current.vehicle_no.trim();
                    if (!vehicleNo) return;
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
                      if (!res.ok) return;
                      const data = (await res.json()) as { transport_name?: string | null };
                      if (data.transport_name) {
                        setFormData((prev) => ({ ...prev, transport_name: data.transport_name }));
                      }
                    } catch {
                      // fail silently
                    }
                  }}
                  placeholder="Vehicle No"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transporter Name</label>
                <Input
                  value={formData.transport_name}
                  onChange={(e) => updateField('transport_name', e.target.value)}
                  placeholder="Transporter Name"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tare WT (T2)</label>
                <Input
                  type="number"
                  step="0.01"
                  value={formData.tare_wt_two}
                  onChange={(e) => updateField('tare_wt_two', e.target.value)}
                  placeholder="T2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Entry time</label>
                <p className="text-sm text-muted-foreground">{entryTime ? formatDateTime(entryTime) : '—'}</p>
              </div>
            </div>

            {saveError && <p className="text-sm text-destructive mb-2">{saveError}</p>}
            <div className="flex flex-wrap justify-end gap-2">
              <Button variant="outline" onClick={() => setShowDetailModal(false)}>
                Cancel
              </Button>
              <Button onClick={save} disabled={isSaving} className="min-w-[100px]">
                {isSaving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Check className="h-4 w-4 mr-2" />}
                {isSaving ? 'Saving...' : 'Save'}
              </Button>
              {entry.status === 'draft' && (
                <Button onClick={handleMarkCompleted} disabled={isSaving} className="min-w-[100px]">
                  {isSaving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : null}
                  Mark completed
                </Button>
              )}
              {shouldShowDeleteButton() && (
                <Button variant="destructive" onClick={handleDelete} disabled={isSaving} className="min-w-[100px]">
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
