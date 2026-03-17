import React, { useCallback, useEffect, useState } from 'react';
import { TableCell, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Save, Pencil, Trash2 } from 'lucide-react';
import { Input } from '@/components/ui/input';
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

const cellClass = 'px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-xs';

function formatCell(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return '—';
  }
  return String(value);
}

interface HistoricalRakeRowProps {
  rake: HistoricalRake;
  index: number;
  isEditing: boolean;
  onEditClick: () => void;
  onSaveSuccess: () => void;
  onRakeUpdated?: (rake: HistoricalRake) => void;
  onRakeDeleted?: (id: number) => void;
}

export default function HistoricalRakeRow({
  rake,
  index,
  isEditing,
  onEditClick,
  onSaveSuccess,
  onRakeUpdated,
  onRakeDeleted,
}: HistoricalRakeRowProps) {
  const canUpdate = useCan('sections.historical_railway_siding.update');
  const canDeletePermission = useCan('sections.historical_railway_siding.delete');
  const [formData, setFormData] = useState({
    rake_number: rake.rake_number?.toString() ?? '',
    priority_number: rake.priority_number?.toString() ?? '',
    rr_number: rake.rr_number?.toString() ?? '',
    wagon_count: rake.wagon_count?.toString() ?? '',
    loaded_weight_mt: rake.loaded_weight_mt?.toString() ?? '',
    under_load_mt: rake.under_load_mt?.toString() ?? '',
    over_load_mt: rake.over_load_mt?.toString() ?? '',
    overload_wagon_count: rake.overload_wagon_count?.toString() ?? '',
    detention_hours: rake.detention_hours?.toString() ?? '',
    shunting_hours: rake.shunting_hours?.toString() ?? '',
    total_amount_rs: rake.total_amount_rs?.toString() ?? '',
    destination: rake.destination ?? '',
    pakur_imwb_period: rake.pakur_imwb_period ?? '',
    loading_date: rake.loading_date ?? '',
    remarks: rake.remarks ?? '',
  });

  useEffect(() => {
    if (isEditing) {
      setFormData({
        rake_number: rake.rake_number?.toString() ?? '',
        priority_number: rake.priority_number?.toString() ?? '',
        rr_number: rake.rr_number?.toString() ?? '',
        wagon_count: rake.wagon_count?.toString() ?? '',
        loaded_weight_mt: rake.loaded_weight_mt?.toString() ?? '',
        under_load_mt: rake.under_load_mt?.toString() ?? '',
        over_load_mt: rake.over_load_mt?.toString() ?? '',
        overload_wagon_count: rake.overload_wagon_count?.toString() ?? '',
        detention_hours: rake.detention_hours?.toString() ?? '',
        shunting_hours: rake.shunting_hours?.toString() ?? '',
        total_amount_rs: rake.total_amount_rs?.toString() ?? '',
        destination: rake.destination ?? '',
        pakur_imwb_period: rake.pakur_imwb_period ?? '',
        loading_date: rake.loading_date ?? '',
        remarks: rake.remarks ?? '',
      });
    }
  }, [isEditing, rake]);

  const updateField = (field: keyof typeof formData, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const save = useCallback(async () => {
    const payload = {
      rake_number: formData.rake_number === '' ? null : Number(formData.rake_number),
      priority_number: formData.priority_number === '' ? null : Number(formData.priority_number),
      rr_number: formData.rr_number === '' ? null : Number(formData.rr_number),
      wagon_count: formData.wagon_count === '' ? null : Number(formData.wagon_count),
      loaded_weight_mt: formData.loaded_weight_mt === '' ? null : formData.loaded_weight_mt,
      under_load_mt: formData.under_load_mt === '' ? null : formData.under_load_mt,
      over_load_mt: formData.over_load_mt === '' ? null : formData.over_load_mt,
      overload_wagon_count:
        formData.overload_wagon_count === '' ? null : Number(formData.overload_wagon_count),
      detention_hours: formData.detention_hours === '' ? null : formData.detention_hours,
      shunting_hours: formData.shunting_hours === '' ? null : formData.shunting_hours,
      total_amount_rs: formData.total_amount_rs === '' ? null : formData.total_amount_rs,
      destination: formData.destination || null,
      pakur_imwb_period: formData.pakur_imwb_period || null,
      loading_date: formData.loading_date || null,
      remarks: formData.remarks?.trim() || null,
    };

    try {
      const res = await fetch(`/historical/railway-siding/${rake.id}`, {
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
        if (res.status === 404) {
          onRakeDeleted?.(rake.id);
        }
        return;
      }
      const updated = (data as { rake?: HistoricalRake }).rake;
      if (updated) {
        onRakeUpdated?.(updated);
        onSaveSuccess();
      }
    } catch {
      // ignore
    }
  }, [rake.id, formData, onRakeUpdated, onRakeDeleted, onSaveSuccess]);

  const handleDelete = useCallback(async () => {
    if (!window.confirm('Delete this row?')) return;
    try {
      const res = await fetch(`/historical/railway-siding/${rake.id}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...getCsrfHeaders(),
        },
        credentials: 'include',
      });
      if (res.ok) onRakeDeleted?.(rake.id);
    } catch {
      // ignore
    }
  }, [rake.id, onRakeDeleted]);

  const canDelete = canDeletePermission && rake.data_source !== 'historical_excel';

  if (isEditing) {
    return (
      <TableRow>
        <TableCell className={`${cellClass} text-center`}>{index + 1}</TableCell>
        <TableCell className={cellClass}>{rake.siding_name ?? '—'}</TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.loading_date}
            type="date"
            onChange={(e) => updateField('loading_date', e.target.value)}
            className="h-8 text-xs"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.rake_number}
            onChange={(e) => updateField('rake_number', e.target.value)}
            className="h-8 text-xs"
            placeholder="Rake No"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.priority_number}
            onChange={(e) => updateField('priority_number', e.target.value)}
            className="h-8 text-xs"
            placeholder="Priority"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.rr_number}
            onChange={(e) => updateField('rr_number', e.target.value)}
            className="h-8 text-xs"
            placeholder="RR No"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.wagon_count}
            onChange={(e) => updateField('wagon_count', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="Wagons"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.loaded_weight_mt}
            onChange={(e) => updateField('loaded_weight_mt', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="Loaded WT"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.under_load_mt}
            onChange={(e) => updateField('under_load_mt', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="Under"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.over_load_mt}
            onChange={(e) => updateField('over_load_mt', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="Over"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.overload_wagon_count}
            onChange={(e) => updateField('overload_wagon_count', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="O/L Wgns"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.detention_hours}
            onChange={(e) => updateField('detention_hours', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="Detention"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.shunting_hours}
            onChange={(e) => updateField('shunting_hours', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="Shunting"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.total_amount_rs}
            onChange={(e) => updateField('total_amount_rs', e.target.value)}
            className="h-8 text-xs text-right"
            placeholder="Total Rs"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.destination}
            onChange={(e) => updateField('destination', e.target.value)}
            className="h-8 text-xs"
            placeholder="Destination"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.pakur_imwb_period}
            onChange={(e) => updateField('pakur_imwb_period', e.target.value)}
            className="h-8 text-xs"
            placeholder="IMWB Period"
          />
        </TableCell>
        <TableCell className={cellClass}>
          <Input
            value={formData.remarks}
            onChange={(e) => updateField('remarks', e.target.value)}
            className="h-8 text-xs"
            placeholder="Remarks"
          />
        </TableCell>
        <TableCell className={`${cellClass} border-r-0 text-center`}>
          <div className="flex items-center justify-center gap-1">
            {canUpdate && (
              <Button
                size="icon"
                variant="outline"
                className="h-7 w-7"
                onClick={save}
                title="Save"
                aria-label="Save"
              >
                <Save className="h-3.5 w-3.5" />
              </Button>
            )}
            {canDelete && (
              <Button
                size="icon"
                variant="outline"
                className="h-7 w-7 text-destructive hover:text-destructive"
                onClick={handleDelete}
                title="Delete"
                aria-label="Delete"
              >
                <Trash2 className="h-3.5 w-3.5" />
              </Button>
            )}
          </div>
        </TableCell>
      </TableRow>
    );
  }

  return (
    <TableRow>
      <TableCell className={`${cellClass} text-center`}>{index + 1}</TableCell>
      <TableCell className={cellClass}>{rake.siding_name ?? '—'}</TableCell>
      <TableCell className={cellClass}>{formatCell(rake.loading_date)}</TableCell>
      <TableCell className={cellClass}>{formatCell(rake.rake_number)}</TableCell>
      <TableCell className={cellClass}>{formatCell(rake.priority_number)}</TableCell>
      <TableCell className={cellClass}>{formatCell(rake.rr_number)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.wagon_count)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.loaded_weight_mt)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.under_load_mt)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.over_load_mt)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.overload_wagon_count)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.detention_hours)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.shunting_hours)}</TableCell>
      <TableCell className={`${cellClass} text-right`}>{formatCell(rake.total_amount_rs)}</TableCell>
      <TableCell className={cellClass}>{formatCell(rake.destination)}</TableCell>
      <TableCell className={cellClass}>{formatCell(rake.pakur_imwb_period)}</TableCell>
      <TableCell className={cellClass}>{formatCell(rake.remarks)}</TableCell>
      <TableCell className={`${cellClass} border-r-0 text-center`}>
        <div className="flex items-center justify-center gap-1">
          {canUpdate && (
            <Button
              size="icon"
              variant="outline"
              className="h-7 w-7"
              onClick={onEditClick}
              title="Edit"
              aria-label="Edit"
            >
              <Pencil className="h-3.5 w-3.5" />
            </Button>
          )}
          {canDelete && (
            <Button
              size="icon"
              variant="outline"
              className="h-7 w-7 text-destructive hover:text-destructive"
              onClick={handleDelete}
              title="Delete"
              aria-label="Delete"
            >
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          )}
        </div>
      </TableCell>
    </TableRow>
  );
}

