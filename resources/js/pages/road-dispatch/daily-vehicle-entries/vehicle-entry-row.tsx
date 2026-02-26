import React, { useState, useRef, useCallback, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Check, Loader2, MoreHorizontal, Trash2, Edit, X } from 'lucide-react';

interface DailyVehicleEntry {
  id: number;
  siding_id: number;
  entry_date: string;
  shift: number;
  e_challan_no: string | null;
  vehicle_no: string | null;
  gross_wt: number | null;
  tare_wt: number | null;
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

interface VehicleEntryRowProps {
  entry: DailyVehicleEntry;
  serialNumber: number;
  date: string;
  shift: number;
}

export default function VehicleEntryRow({ entry, serialNumber, date, shift }: VehicleEntryRowProps) {
  const [isSaving, setIsSaving] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [showContextMenu, setShowContextMenu] = useState(false);

  const [formData, setFormData] = useState({
    e_challan_no: entry.e_challan_no || '',
    vehicle_no: entry.vehicle_no || '',
    gross_wt: entry.gross_wt?.toString() || '',
    tare_wt: entry.tare_wt?.toString() || '',
    wb_no: entry.wb_no || '',
    d_challan_no: entry.d_challan_no || '',
    challan_mode: entry.challan_mode || '',
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

  const save = useCallback(() => {
    const dataToSave = formDataRef.current; // always fresh, no stale closure

    setIsSaving(true);
    setShowSuccess(false);

    router.patch(`/road-dispatch/daily-vehicle-entries/${entry.id}`, dataToSave, {
      preserveScroll: true,
      preserveState: true, // prevents Inertia from re-rendering and resetting local state
      onSuccess: () => {
        setIsSaving(false);
        setShowSuccess(true);
        setTimeout(() => setShowSuccess(false), 2000);
      },
      onError: () => {
        setIsSaving(false);
      },
    });
  }, [entry.id]);

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
      !entry.gross_wt &&
      !entry.tare_wt &&
      entry.status === 'draft'
    );
  };

  const handleDelete = () => {
    setShowDetailModal(false);
    if (confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
      router.delete(`/road-dispatch/daily-vehicle-entries/${entry.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          // Entry will be removed from the list automatically
        },
        onError: () => {
          alert('Error deleting entry. Please try again.');
        },
      });
    }
  };

  const handleMarkCompleted = () => {
    setIsSaving(true);

    router.post(`/road-dispatch/daily-vehicle-entries/${entry.id}/complete`, {}, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        setIsSaving(false);
        setShowSuccess(true);
        setTimeout(() => setShowSuccess(false), 2000);
      },
      onError: () => {
        setIsSaving(false);
      },
    });
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
        className="group hover:bg-gray-50 relative"
        onMouseEnter={() => setShowContextMenu(true)}
        onMouseLeave={() => setShowContextMenu(false)}
      >
        <TableCell className="font-medium">
          <div className="flex items-center gap-2">
            {/* Context menu button - shows on hover */}
            <div className={`opacity-0 group-hover:opacity-100 transition-opacity duration-200 ${showContextMenu ? 'opacity-100' : ''}`}>
              <Button
                size="sm"
                variant="ghost"
                onClick={() => setShowDetailModal(true)}
                className="h-8 w-8 p-0"
                title="More options"
              >
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </div>
            {serialNumber}
          </div>
        </TableCell>

      <TableCell>
        <Input
          value={formData.e_challan_no}
          onChange={(e) => {
            updateField('e_challan_no', e.target.value);
            debouncedSave();
          }}
          placeholder="E Challan No"
          className="w-32"
        />
      </TableCell>

      <TableCell>
        <Input
          value={formData.vehicle_no}
          onChange={(e) => {
            updateField('vehicle_no', e.target.value);
            debouncedSave();
          }}
          placeholder="Vehicle No"
          className="w-32"
        />
      </TableCell>

      <TableCell>
        <Input
          type="number"
          step="0.01"
          value={formData.gross_wt}
          onChange={(e) => {
            updateField('gross_wt', e.target.value);
            debouncedSave();
          }}
          placeholder="Gross WT (G2)"
          className="w-24"
        />
      </TableCell>

      <TableCell>
        <Input
          type="number"
          step="0.01"
          value={formData.tare_wt}
          onChange={(e) => {
            updateField('tare_wt', e.target.value);
            debouncedSave();
          }}
          placeholder="Tare WT (T1)"
          className="w-24"
        />
      </TableCell>

      <TableCell className="font-medium text-blue-600">
        {calculateNetWeight()}
      </TableCell>

      <TableCell className="text-sm text-gray-600">
        {formatDateTime(entry.reached_at)}
      </TableCell>

      <TableCell>
        <Input
          value={formData.wb_no}
          onChange={(e) => {
            updateField('wb_no', e.target.value);
            debouncedSave();
          }}
          placeholder="WB No"
          className="w-24"
        />
      </TableCell>

      <TableCell>
        <Input
          value={formData.d_challan_no}
          onChange={(e) => {
            updateField('d_challan_no', e.target.value);
            debouncedSave();
          }}
          placeholder="D Challan No"
          className="w-32"
        />
      </TableCell>

      <TableCell>
        <Select
          value={formData.challan_mode}
          onValueChange={(value) => {
            updateField('challan_mode', value as 'offline' | 'online');
            // Save immediately on select change (no debounce needed)
            clearTimeout(debounceTimerRef.current);
            // Small tick to let state + ref update before saving
            setTimeout(save, 50);
          }}
        >
          <SelectTrigger className="w-28">
            <SelectValue placeholder="Mode" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="offline">Offline</SelectItem>
            <SelectItem value="online">Online</SelectItem>
          </SelectContent>
        </Select>
      </TableCell>

      <TableCell>
        <Badge variant={entry.status === 'completed' ? 'default' : 'secondary'}>
          {entry.status}
        </Badge>
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

            <div className="flex justify-between items-center">
              <div className="text-sm text-gray-600">
                <span className="font-medium">Status:</span> 
                <Badge variant={entry.status === 'completed' ? 'default' : 'secondary'} className="ml-2">
                  {entry.status}
                </Badge>
              </div>
              
              <div className="flex gap-2">
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