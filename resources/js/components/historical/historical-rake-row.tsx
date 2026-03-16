import React, { useCallback, useEffect, useRef, useState } from 'react';
import { TableCell, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-react';
import { Input } from '@/components/ui/input';

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

interface HistoricalRakeRowProps {
  rake: HistoricalRake;
  index: number;
  onRakeUpdated?: (rake: HistoricalRake) => void;
  onRakeDeleted?: (id: number) => void;
}

export default function HistoricalRakeRow({
  rake,
  index,
  onRakeUpdated,
  onRakeDeleted,
}: HistoricalRakeRowProps) {
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
  });

  const debounceRef = useRef<number | undefined>(undefined);
  const formRef = useRef(formData);

  useEffect(() => {
    formRef.current = formData;
  }, [formData]);

  const updateField = (field: keyof typeof formData, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const save = useCallback(async () => {
    const payload = formRef.current;
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
          return;
        }
        return;
      }
      const updated = (data as { rake?: HistoricalRake }).rake;
      if (updated) {
        onRakeUpdated?.(updated);
      }
    } catch {
      // ignore for now; user can retry by editing again
    }
  }, [rake.id, onRakeUpdated, onRakeDeleted]);

  const debouncedSave = useCallback(() => {
    window.clearTimeout(debounceRef.current);
    debounceRef.current = window.setTimeout(save, 800);
  }, [save]);

  useEffect(() => {
    return () => {
      window.clearTimeout(debounceRef.current);
    };
  }, []);

  return (
    <TableRow>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-xs">
        {index + 1}
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem] text-xs">
        {rake.siding_name ?? '—'}
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.loading_date}
          type="date"
          onChange={(e) => {
            updateField('loading_date', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.rake_number}
          onChange={(e) => {
            updateField('rake_number', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs"
          placeholder="Rake No"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.priority_number}
          onChange={(e) => {
            updateField('priority_number', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs"
          placeholder="Priority"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.rr_number}
          onChange={(e) => {
            updateField('rr_number', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs"
          placeholder="RR No"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.wagon_count}
          onChange={(e) => {
            updateField('wagon_count', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="Wagons"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.loaded_weight_mt}
          onChange={(e) => {
            updateField('loaded_weight_mt', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="Loaded WT"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.under_load_mt}
          onChange={(e) => {
            updateField('under_load_mt', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="Under"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.over_load_mt}
          onChange={(e) => {
            updateField('over_load_mt', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="Over"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.overload_wagon_count}
          onChange={(e) => {
            updateField('overload_wagon_count', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="O/L Wgns"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.detention_hours}
          onChange={(e) => {
            updateField('detention_hours', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="Detention"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.shunting_hours}
          onChange={(e) => {
            updateField('shunting_hours', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="Shunting"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.total_amount_rs}
          onChange={(e) => {
            updateField('total_amount_rs', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs text-right"
          placeholder="Total Rs"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.destination}
          onChange={(e) => {
            updateField('destination', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs"
          placeholder="Destination"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-r border-gray-300 min-h-[4rem]">
        <Input
          value={formData.pakur_imwb_period}
          onChange={(e) => {
            updateField('pakur_imwb_period', e.target.value);
            debouncedSave();
          }}
          className="h-8 text-xs"
          placeholder="IMWB Period"
        />
      </TableCell>
      <TableCell className="px-2 py-3 border-t border-gray-300 min-h-[4rem] text-center">
        {rake.data_source !== 'historical_excel' && (
          <Button
            type="button"
            variant="destructive"
            size="xs"
            onClick={async () => {
              if (!window.confirm('Delete this row?')) {
                return;
              }
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
                if (res.ok) {
                  onRakeDeleted?.(rake.id);
                }
              } catch {
                // ignore for now
              }
            }}
          >
            <Trash2 className="w-3 h-3" />
          </Button>
        )}
      </TableCell>
    </TableRow>
  );
}

