import { format } from 'date-fns';

export function formatVehicleDispatchDate(dateString: string | null): string {
    if (!dateString) return 'N/A';
    try {
        let date: Date;

        if (/^\d+$/.test(dateString)) {
            date = new Date(parseInt(dateString, 10) * 1000);
        } else {
            date = new Date(dateString);
            if (date.getFullYear() === 1970 && dateString.includes('2026')) {
                const match = dateString.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
                if (match) {
                    const [, year, month, day, hour, minute, second] = match;
                    date = new Date(`${year}-${month}-${day}T${hour}:${minute}:${second}`);
                }
            }
        }

        return format(date, 'dd MMM yyyy HH:mm');
    } catch {
        return dateString;
    }
}

export function formatWeight(weight: number): string {
    return `${weight.toLocaleString()} MT`;
}

export function getShiftFromIssuedOn(issuedOn: string | null): string | null {
    if (!issuedOn) return null;
    try {
        let d: Date;

        if (/^\d+$/.test(issuedOn)) {
            d = new Date(parseInt(issuedOn, 10) * 1000);
        } else {
            d = new Date(issuedOn);
            if (d.getFullYear() === 1970 && issuedOn.includes('2026')) {
                const match = issuedOn.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
                if (match) {
                    const [, year, month, day, hour, minute, second] = match;
                    d = new Date(`${year}-${month}-${day}T${hour}:${minute}:${second}`);
                }
            }
        }

        const minutes = d.getHours() * 60 + d.getMinutes();
        if (minutes <= 480) return '1st';
        if (minutes <= 960) return '2nd';
        return '3rd';
    } catch {
        return null;
    }
}

export function toDatetimeLocal(dateString: string | null): string {
    if (!dateString) return '';
    try {
        const d = new Date(dateString);
        const pad = (n: number) => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    } catch {
        return '';
    }
}
