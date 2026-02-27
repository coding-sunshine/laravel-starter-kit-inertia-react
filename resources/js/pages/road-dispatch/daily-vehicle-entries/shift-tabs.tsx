import React from 'react';
import { Button } from '@/components/ui/button';

interface ShiftTabsProps {
  activeShift: number;
  onShiftChange: (shift: number) => void;
  shiftSummary: Record<number, number>;
}

export default function ShiftTabs({ activeShift, onShiftChange, shiftSummary }: ShiftTabsProps) {
  const shifts = [
    { id: 1, label: '1ST SHIFT' },
    { id: 2, label: '2ND SHIFT' },
    { id: 3, label: '3RD SHIFT' },
  ];

  return (
    <div className="flex gap-1 bg-gray-100 p-1 rounded-lg">
      {shifts.map((shift) => (
        <Button
          key={shift.id}
          variant={activeShift === shift.id ? "default" : "ghost"}
          onClick={() => onShiftChange(shift.id)}
          className="flex-1 justify-center"
        >
          {shift.label} ({shiftSummary[shift.id] || 0})
        </Button>
      ))}
    </div>
  );
}
