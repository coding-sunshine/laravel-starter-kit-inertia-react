import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Eye } from 'lucide-react';

export interface WagonOverviewWagon {
    id: number;
    wagon_sequence: number;
    wagon_number: string;
    wagon_type: string | null;
    tare_weight_mt: string | number | null;
    pcc_weight_mt: string | number | null;
    is_unfit: boolean;
    state: string | null;
}

interface WagonOverviewDialogProps {
    wagons: WagonOverviewWagon[];
}

export function WagonOverviewDialog({
    wagons,
}: WagonOverviewDialogProps) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Eye className="mr-2 h-4 w-4" />
                    View Wagons
                </Button>
            </DialogTrigger>
            <DialogContent className="!max-w-[92vw] w-[92vw] max-h-[90vh] flex flex-col p-4 sm:p-6">
                <DialogHeader>
                    <DialogTitle>Wagon Overview</DialogTitle>
                </DialogHeader>
                <div
                    className="flex-1 min-h-0 overflow-auto border rounded-md"
                    style={{ height: 'min(70vh, 600px)' }}
                >
                    <div className="inline-block min-w-[900px] align-top">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Wagon No</TableHead>
                                <TableHead>Seq</TableHead>
                                <TableHead>Wagon Type</TableHead>
                                <TableHead>Tare (MT)</TableHead>
                                <TableHead>PCC (MT)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {wagons.map((wagon) => {
                                return (
                                <TableRow key={wagon.id}>
                                        <TableCell>
                                            {wagon.wagon_number || '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {wagon.wagon_sequence}
                                        </TableCell>
                                        <TableCell>
                                            {wagon.wagon_type || '—'}
                                        </TableCell>
                                        <TableCell>
                                            {wagon.tare_weight_mt ?? '—'}
                                        </TableCell>
                                    <TableCell>
                                            {wagon.pcc_weight_mt ?? '—'}
                                    </TableCell>
                                </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
