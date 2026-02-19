/**
 * Timer Component - Real-time countdown display
 * Used for demurrage timers with color-coded urgency
 * Updates every 1 second, supports HH:MM:SS format
 */

import { useEffect, useState } from 'react';

interface TimerProps {
  startTime: number; // Unix timestamp in milliseconds
  durationMs: number; // Total duration in milliseconds
  onExpire?: () => void;
  onWarning?: (secondsRemaining: number) => void;
  warningThresholdMs?: number; // Default: 1 hour = 3600000ms
  showLabel?: boolean;
  label?: string;
  size?: 'sm' | 'md' | 'lg';
}

export function Timer({
  startTime,
  durationMs,
  onExpire,
  onWarning,
  warningThresholdMs = 3600000, // 1 hour
  showLabel = true,
  label = 'Time Remaining',
  size = 'md',
}: TimerProps) {
  const [remaining, setRemaining] = useState<number>(0);
  const [isExpired, setIsExpired] = useState(false);
  const [hasWarned, setHasWarned] = useState(false);

  useEffect(() => {
    const endTime = startTime + durationMs;

    const interval = setInterval(() => {
      const now = Date.now();
      const diff = endTime - now;

      if (diff <= 0) {
        setRemaining(0);
        setIsExpired(true);
        if (onExpire) {
          onExpire();
        }
        clearInterval(interval);
      } else {
        setRemaining(diff);

        // Trigger warning when threshold is crossed
        if (diff <= warningThresholdMs && !hasWarned) {
          setHasWarned(true);
          if (onWarning) {
            onWarning(Math.floor(diff / 1000));
          }
        }
      }
    }, 1000);

    return () => clearInterval(interval);
  }, [startTime, durationMs, warningThresholdMs, onExpire, onWarning, hasWarned]);

  const formatTime = (ms: number): string => {
    const totalSeconds = Math.floor(ms / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  };

  const getUrgencyColor = (): string => {
    if (isExpired) {
      return 'text-red-600 bg-red-50';
    }

    const percentageRemaining = (remaining / durationMs) * 100;

    if (percentageRemaining <= 10) {
      return 'text-red-600 bg-red-50'; // Critical
    } else if (percentageRemaining <= 25) {
      return 'text-orange-600 bg-orange-50'; // Warning
    } else if (percentageRemaining <= 50) {
      return 'text-yellow-600 bg-yellow-50'; // Caution
    }

    return 'text-green-600 bg-green-50'; // Safe
  };

  const sizeClasses = {
    sm: 'text-lg font-mono',
    md: 'text-2xl font-mono font-bold',
    lg: 'text-4xl font-mono font-bold',
  };

  return (
    <div className={`flex flex-col items-center gap-2 p-3 rounded-lg ${getUrgencyColor()}`}>
      {showLabel && <span className="text-xs font-semibold uppercase tracking-wider">{label}</span>}

      <div className={sizeClasses[size]}>
        {isExpired ? (
          <span>EXPIRED</span>
        ) : (
          <span>{formatTime(remaining)}</span>
        )}
      </div>

      {/* Progress bar */}
      <div className="w-full bg-gray-300 rounded-full h-2 overflow-hidden">
        <div
          className={`h-full transition-all duration-1000 ${
            isExpired
              ? 'bg-red-600'
              : remaining / durationMs <= 0.1
                ? 'bg-red-600'
                : remaining / durationMs <= 0.25
                  ? 'bg-orange-600'
                  : remaining / durationMs <= 0.5
                    ? 'bg-yellow-600'
                    : 'bg-green-600'
          }`}
          style={{ width: `${Math.min((remaining / durationMs) * 100, 100)}%` }}
        />
      </div>

      {/* Status indicator dot */}
      <div className="flex items-center gap-1 text-xs">
        <div
          className={`w-2 h-2 rounded-full ${
            isExpired
              ? 'bg-red-600'
              : remaining / durationMs <= 0.1
                ? 'bg-red-600 animate-pulse'
                : remaining / durationMs <= 0.25
                  ? 'bg-orange-600 animate-pulse'
                  : remaining / durationMs <= 0.5
                    ? 'bg-yellow-600'
                    : 'bg-green-600'
          }`}
        />
        <span className="font-medium">
          {isExpired
            ? 'Expired'
            : remaining / durationMs <= 0.1
              ? 'Critical'
              : remaining / durationMs <= 0.25
                ? 'Warning'
                : remaining / durationMs <= 0.5
                  ? 'Caution'
                  : 'On Track'}
        </span>
      </div>
    </div>
  );
}

export default Timer;
