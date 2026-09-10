import React, { useEffect, useMemo, useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';

type ShiftLock = {
  isLocked: boolean;
  message: string;
  nextShiftStartAt: string | null;
  now: string;
};

function formatDuration(totalSeconds: number): string {
  const clamped = Math.max(0, Math.floor(totalSeconds));
  const hours = Math.floor(clamped / 3600);
  const minutes = Math.floor((clamped % 3600) / 60);
  const seconds = clamped % 60;

  const pad = (n: number) => String(n).padStart(2, '0');

  return hours > 0 ? `${hours}:${pad(minutes)}:${pad(seconds)}` : `${minutes}:${pad(seconds)}`;
}

export default function ShiftLockOverlay({
  shiftLock,
  canBypass,
  onUnlock,
}: {
  shiftLock?: ShiftLock | null;
  canBypass?: boolean;
  onUnlock?: () => void;
}) {
  const isLocked = !!shiftLock?.isLocked && !canBypass;
  const nextStart = useMemo(
    () => (shiftLock?.nextShiftStartAt ? new Date(shiftLock.nextShiftStartAt) : null),
    [shiftLock?.nextShiftStartAt]
  );

  const [now, setNow] = useState(() => new Date());

  useEffect(() => {
    if (!isLocked) return;
    const id = window.setInterval(() => setNow(new Date()), 250);
    return () => window.clearInterval(id);
  }, [isLocked]);

  const remainingSeconds =
    nextStart == null ? null : Math.floor((nextStart.getTime() - now.getTime()) / 1000);

  useEffect(() => {
    if (!isLocked) return;
    if (remainingSeconds == null) return;
    if (remainingSeconds > 0) return;
    onUnlock?.();
  }, [isLocked, remainingSeconds, onUnlock]);

  if (!isLocked) {
    return null;
  }

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      data-pan="shift-lock-overlay"
    >
      <Card className="w-[min(92vw,520px)] shadow-xl">
        <CardContent className="p-6 space-y-3">
          <div className="text-lg font-semibold">Shift access locked</div>

          <div className="text-sm text-muted-foreground">
            {shiftLock?.message || 'You cannot update data right now.'}
          </div>

          {nextStart && remainingSeconds != null ? (
            <div className="pt-2">
              <div className="text-sm font-medium">Next shift starts in</div>
              <div className="text-3xl font-bold tabular-nums">{formatDuration(remainingSeconds)}</div>
              <div className="text-xs text-muted-foreground pt-1">
                Starts at {nextStart.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
              </div>
            </div>
          ) : (
            <div className="pt-2 text-sm text-muted-foreground">Please wait for your next shift to start.</div>
          )}

          <div className="pt-3 flex items-center justify-end">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                router.post('/logout');
              }}
            >
              Logout
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

