import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useState } from 'react';

const REASONS = [
    { value: 'reduced_load',         label: 'Reduced load (remediated)' },
    { value: 'equipment_constraint', label: 'Equipment constraint' },
    { value: 'railway_instruction',  label: 'Railway instruction' },
    { value: 'other',                label: 'Other' },
] as const;

interface ManagerClearanceModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    rakeId: number;
    overloadMt: number;
    estimatedPenaltyRs: number;
    onConfirmed: () => void;
}

export function ManagerClearanceModal({
    open,
    onOpenChange,
    rakeId,
    overloadMt,
    estimatedPenaltyRs,
    onConfirmed,
}: ManagerClearanceModalProps) {
    const [reason, setReason] = useState<string>('');
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(false);

    const inr = (v: number) =>
        new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);

    function handleConfirm() {
        if (!reason) return;
        setLoading(true);

        const token = (document.cookie.match(/XSRF-TOKEN=([^;]+)/) ?? [])[1];
        const csrfToken = token ? decodeURIComponent(token) : '';

        fetch(`/rake-loader/rakes/${rakeId}/override`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                reason,
                notes: notes || null,
                overload_mt: overloadMt,
                estimated_penalty_rs: estimatedPenaltyRs,
            }),
        })
            .then(() => {
                setLoading(false);
                onOpenChange(false);
                setReason('');
                setNotes('');
                onConfirmed();
            })
            .catch(() => setLoading(false));
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Request Manager Clearance</DialogTitle>
                    <DialogDescription>
                        {overloadMt > 0
                            ? `${overloadMt.toFixed(3)} MT excess load detected. Estimated penalty: ${inr(estimatedPenaltyRs)}. Provide a reason to proceed.`
                            : 'Provide a reason to request manager clearance.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-4 py-2">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="reason">Reason *</Label>
                        <Select value={reason} onValueChange={setReason}>
                            <SelectTrigger id="reason">
                                <SelectValue placeholder="Select a reason..." />
                            </SelectTrigger>
                            <SelectContent>
                                {REASONS.map((r) => (
                                    <SelectItem key={r.value} value={r.value}>
                                        {r.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="notes">Notes (optional)</Label>
                        <Textarea
                            id="notes"
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            placeholder="Additional context..."
                            rows={3}
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
                        Cancel
                    </Button>
                    <Button
                        onClick={handleConfirm}
                        disabled={!reason || loading}
                        className="btn-bgr-gold"
                    >
                        {loading ? 'Logging...' : 'Confirm & Proceed'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
