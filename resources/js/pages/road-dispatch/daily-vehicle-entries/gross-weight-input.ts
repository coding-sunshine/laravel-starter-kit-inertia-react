/**
 * Gross weight helpers: operators enter a kg-style digit string; UI inserts a dot after the first
 * two digits; stored/sent values are metric tonnes (MT).
 */

export function grossDigitsOnly(value: string): string {
  return value.replace(/\D/g, '');
}

/** Display string from a raw digit buffer (no dot until more than 2 digits). */
export function digitsToGrossDisplay(digits: string): string {
  const d = grossDigitsOnly(digits);
  if (d.length <= 2) {
    return d;
  }
  return `${d.slice(0, 2)}.${d.slice(2)}`;
}

/** Seed digit string from stored MT (via kg × 1000, pad to 5 chars when needed). */
export function mtToGrossDigitString(mt: number | null | undefined): string {
  if (mt == null || !Number.isFinite(Number(mt))) {
    return '';
  }
  const kgInt = Math.round(Number(mt) * 1000);
  let s = String(kgInt);
  if (s.length < 5) {
    s = s.padStart(5, '0');
  }
  return s;
}

export function grossDisplayFromMt(mt: number | null | undefined): string {
  return digitsToGrossDisplay(mtToGrossDigitString(mt));
}

/** Parse current field text to MT for API; empty → null. */
export function parseGrossInputToMt(display: string): number | null {
  const d = grossDigitsOnly(display);
  if (!d) {
    return null;
  }
  if (d.length <= 2) {
    const n = parseFloat(d);
    return Number.isFinite(n) ? n : null;
  }
  const n = parseFloat(digitsToGrossDisplay(d));
  return Number.isFinite(n) ? n : null;
}

/** True when gross digit buffer is long enough for dotted MT (net can be shown). */
export function isGrossMtReady(display: string): boolean {
  return grossDigitsOnly(display).length >= 3;
}

export function formatNetMtFromGrossTare(grossDisplay: string, tareStr: string): string {
  if (!isGrossMtReady(grossDisplay)) {
    return '—';
  }
  const d = grossDigitsOnly(grossDisplay);
  const grossMt = parseFloat(digitsToGrossDisplay(d));
  if (!Number.isFinite(grossMt)) {
    return '—';
  }
  const tare = parseFloat(tareStr) || 0;
  return (grossMt - tare).toFixed(2);
}
