export type Meridiem = 'AM' | 'PM';

export type ReachedAt12hParts = {
  hour: number;
  /** 0–59 */
  minute: number;
  meridiem: Meridiem;
};

/**
 * 24h hour in local wall time, for sheet date + 12h parts (no `toISOString` — avoids UTC day shift).
 */
function to24hHour(hour12: number, meridiem: Meridiem): number {
  if (meridiem === 'AM') {
    return hour12 === 12 ? 0 : hour12;
  }
  return hour12 === 12 ? 12 : hour12 + 12;
}

/**
 * `YYYY-MM-DD` + 12h clock → `Y-m-d\TH:i:s` (no `Z`); Laravel `date` rule parses in app timezone.
 */
export function sheetDateAnd12hToReachedAtLocalString(sheetYmd: string, parts: ReachedAt12hParts): string {
  const h24 = to24hHour(parts.hour, parts.meridiem);
  const mm = String(parts.minute).padStart(2, '0');
  return `${sheetYmd}T${String(h24).padStart(2, '0')}:${mm}:00`;
}

/**
 * Read local wall time from a server/ISO `reached_at` for 12h controls.
 */
export function reachedAtIsoTo12hParts(iso: string): ReachedAt12hParts {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) {
    return nowTo12hParts();
  }
  const h = d.getHours();
  const m = d.getMinutes();
  const meridiem: Meridiem = h >= 12 ? 'PM' : 'AM';
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  return { hour: hour12, minute: m, meridiem };
}

export function nowTo12hParts(): ReachedAt12hParts {
  const d = new Date();
  const h = d.getHours();
  const m = d.getMinutes();
  const meridiem: Meridiem = h >= 12 ? 'PM' : 'AM';
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  return { hour: hour12, minute: m, meridiem };
}
