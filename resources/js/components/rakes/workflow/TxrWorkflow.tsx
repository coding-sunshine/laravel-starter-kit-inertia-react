import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Train, CheckCircle, Clock, AlertTriangle, Plus, Trash2 } from 'lucide-react';
import { useForm, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { TxrTable } from './TxrTable';
import { UnfitWagonTable } from './UnfitWagonTable';

interface Wagon {
    id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type?: string | null;
    is_unfit?: boolean;
}

interface WagonUnfitLog {
    id?: number;
    wagon_id: number;
    wagon?: { wagon_number: string; wagon_sequence: number; wagon_type?: string | null };
    reason?: string | null;
    marking_method?: string | null;
    marked_at?: string | null;
}

interface TxrRecord {
    id: number;
    rake_id: number;
    inspection_time: string;
    inspection_end_time?: string | null;
    status: string;
    remarks: string | null;
}

interface TxrWorkflowProps {
    rake: {
        id: number;
        rake_number: string;
        state: string;
        wagons: Wagon[];
        txr: (TxrRecord & { wagonUnfitLogs?: WagonUnfitLog[] }) | null;
        wagonUnfitLogs?: WagonUnfitLog[];
    };
    disabled: boolean;
    onUnfitLogsSaved?: (logs: WagonUnfitLog[]) => void;
}

export function TxrWorkflow({ rake, disabled, onUnfitLogsSaved }: TxrWorkflowProps) {
    const handleStartTxr = () => {
        router.post(`/rakes/${rake.id}/txr/start`, {}, { preserveScroll: true });
    };

    const handleEndTxr = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(`/rakes/${rake.id}/txr/end`, { remarks: '' }, { preserveScroll: true });
    };

    const getStatusIcon = () => {
        if (!rake.txr) return <Clock className="h-4 w-4" />;
        if (rake.txr.status === 'completed') return <CheckCircle className="h-4 w-4 text-green-600" />;
        if (rake.txr.status === 'in_progress') return <Clock className="h-4 w-4 text-blue-600" />;
        return <AlertTriangle className="h-4 w-4 text-orange-600" />;
    };

    const getStatusText = () => {
        if (!rake.txr) return 'Not Started';
        return rake.txr.status.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
    };

    const isCompleted = rake.txr?.status === 'completed';

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Train className="h-5 w-5" />
                        TXR - Train Examination Report
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={isCompleted ? 'default' : 'secondary'}>{getStatusText()}</Badge>
                    </div>
                </CardTitle>
                <CardDescription>Train examination and wagon fitness check</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!rake.txr ? (
                    <div className="text-center py-8">
                        <Button onClick={handleStartTxr} disabled={disabled} size="lg">
                            <Train className="mr-2 h-4 w-4" />
                            Start TXR Inspection
                        </Button>
                    </div>
                ) : (
                    <>
                        {/* TXR Header Table */}
                        <TxrTable rake={rake} disabled={disabled} />

                        {/* Unfit Wagon Details Table */}
                        <UnfitWagonTable
                            rake={rake}
                            disabled={disabled}
                            onUnfitLogsSaved={onUnfitLogsSaved}
                        />

                        {rake.txr.status === 'in_progress' && (
                            <form onSubmit={handleEndTxr}>
                                <div className="flex justify-end">
                                    <Button type="submit" variant="destructive">
                                        End TXR
                                    </Button>
                                </div>
                            </form>
                        )}

                        {isCompleted && (
                            <div className="flex items-center gap-2 text-sm text-green-600">
                                <CheckCircle className="h-4 w-4" />
                                TXR completed successfully
                            </div>
                        )}
                    </>
                )}
            </CardContent>
        </Card>
    );
}
