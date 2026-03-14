import React from 'react';
import { Button } from '@/components/ui/button';

interface ShiftStatus {
  is_active: boolean;
  is_available: boolean;
  is_completed: boolean;
}

interface ShiftTabsProps {
  activeShift: number;
  onShiftChange: (shift: number) => void;
  shiftSummary: Record<number, number>;
  shiftStatus?: Record<number, ShiftStatus>;
}

export default function ShiftTabs({ activeShift, onShiftChange, shiftSummary, shiftStatus }: ShiftTabsProps) {
  const shifts = [
    { id: 1, label: '1ST SHIFT' },
    { id: 2, label: '2ND SHIFT' },
    { id: 3, label: '3RD SHIFT' },
  ];

  const getShiftVariant = (shiftId: number) => {
    if (activeShift === shiftId) return 'default';
    if (shiftStatus && !shiftStatus[shiftId]?.is_available) return 'secondary';
    return 'ghost';
  };

  const getShiftDisabled = (shiftId: number) => {
    return shiftStatus ? !shiftStatus[shiftId]?.is_available : false;
  };

  const getShiftTitle = (shiftId: number) => {
    if (!shiftStatus) return '';

    const status = shiftStatus[shiftId];
    if (status.is_active) return 'Current active shift';
    if (!status.is_available) {
      if (shiftId === 2) return '2nd shift will be available after 1st shift completion (after 11:00)';
      if (shiftId === 3) return '3rd shift will be available after 2nd shift completion (after 22:00)';
      return 'Shift not available';
    }
    if (status.is_completed) return 'Completed shift';
    return 'Available shift';
  };

  return (
    <div className="flex gap-1 bg-gray-100 p-1 rounded-lg">
      {shifts.map((shift) => (
        <Button
          key={shift.id}
          variant={getShiftVariant(shift.id)}
          onClick={() => onShiftChange(shift.id)}
          disabled={getShiftDisabled(shift.id)}
          title={getShiftTitle(shift.id)}
          className={`flex-1 justify-center ${
            getShiftDisabled(shift.id) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
          }`}
        >
          {shift.label} ({shiftSummary[shift.id] || 0})
          {shiftStatus && shiftStatus[shift.id]?.is_active && (
            <span className="ml-1 w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
          )}
        </Button>
      ))}
    </div>
  );
}
