/** Weekday bitmask used by Balancirk lessons (matches PHP LessonModel::getLesdays). */
const WEEKDAY_BITS: Array<{ name: string; bit: number; jsDay: number }> = [
  { name: 'Sunday', bit: 1, jsDay: 0 },
  { name: 'Monday', bit: 64, jsDay: 1 },
  { name: 'Tuesday', bit: 32, jsDay: 2 },
  { name: 'Wednesday', bit: 16, jsDay: 3 },
  { name: 'Thursday', bit: 8, jsDay: 4 },
  { name: 'Friday', bit: 4, jsDay: 5 },
  { name: 'Saturday', bit: 2, jsDay: 6 },
];

const WEEKDAY_LABELS_NL: Record<number, string> = {
  0: 'zondag',
  1: 'maandag',
  2: 'dinsdag',
  3: 'woensdag',
  4: 'donderdag',
  5: 'vrijdag',
  6: 'zaterdag',
};

/** Parse lesdays bitmask into JS getDay() values that are active. */
export function activeWeekdays(lesdays: number | string | null | undefined): number[] {
  const mask = Number(lesdays ?? 0);
  if (!Number.isFinite(mask) || mask <= 0) {
    return [];
  }
  return WEEKDAY_BITS.filter((d) => (mask & d.bit) === d.bit).map((d) => d.jsDay);
}

/** Human-readable Dutch weekday list for a lesdays bitmask. */
export function formatLesdays(lesdays: number | string | null | undefined): string {
  const days = activeWeekdays(lesdays);
  if (days.length === 0) {
    return '—';
  }
  return days.map((d) => WEEKDAY_LABELS_NL[d] ?? '').filter(Boolean).join(', ');
}

function toIsoDate(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function parseDateOnly(value: string): Date | null {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value.trim());
  if (!match) {
    return null;
  }
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * Build ISO date strings for lesson days between start and end (inclusive),
 * matching PHP LessonModel::getDates.
 */
export function getLessonDates(
  start: string | null | undefined,
  end: string | null | undefined,
  lesdays: number | string | null | undefined,
): string[] {
  const startDate = parseDateOnly(String(start ?? ''));
  const endDate = parseDateOnly(String(end ?? ''));
  const weekdays = new Set(activeWeekdays(lesdays));
  if (!startDate || !endDate || weekdays.size === 0) {
    return [];
  }

  const dates: string[] = [];
  const cursor = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
  const last = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());

  while (cursor <= last) {
    if (weekdays.has(cursor.getDay())) {
      dates.push(toIsoDate(cursor));
    }
    cursor.setDate(cursor.getDate() + 1);
  }

  return dates;
}

/** Prefer today if it is a lesson day; otherwise nearest upcoming, else last past. */
export function pickDefaultLessonDate(dates: string[], preferred?: string | null): string {
  if (preferred && dates.includes(preferred)) {
    return preferred;
  }
  if (dates.length === 0) {
    return preferred || new Date().toISOString().slice(0, 10);
  }

  const today = toIsoDate(new Date());
  if (dates.includes(today)) {
    return today;
  }

  const upcoming = dates.find((d) => d >= today);
  return upcoming ?? dates[dates.length - 1];
}

export function formatLessonDateLabel(iso: string): string {
  const date = parseDateOnly(iso);
  if (!date) {
    return iso;
  }
  const weekday = WEEKDAY_LABELS_NL[date.getDay()] ?? '';
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  return `${weekday} ${day}/${month}/${date.getFullYear()}`;
}
