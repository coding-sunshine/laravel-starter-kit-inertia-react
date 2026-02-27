import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye } from 'lucide-react';

interface Wagon {
    id: number;
    wagon_sequence: number;
    wagon_number: string;
    wagon_type: string | null;
    pcc_weight_mt: string | null;
    is_unfit: boolean;
    state: string | null;
}

interface WagonOverviewDialogProps {
    wagons: Wagon[];
}

export function WagonOverviewDialog({ wagons }: WagonOverviewDialogProps) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Eye className="mr-2 h-4 w-4" />
                    View Wagons
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-4xl max-h-[80vh]">
                <DialogHeader>
                    <DialogTitle>Wagon Overview</DialogTitle>
                </DialogHeader>
                <ScrollArea className="max-h-[60vh]">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Wagon No</TableHead>
                                <TableHead>Sequence</TableHead>
                                <TableHead>Wagon Type</TableHead>
                                <TableHead>PCC Capacity</TableHead>
                                <TableHead>Is Fit / Unfit</TableHead>
                                <TableHead>Current State</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {wagons.map((wagon) => (
                                <TableRow key={wagon.id}>
                                    <TableCell className="font-medium">{wagon.wagon_number}</TableCell>
                                    <TableCell>{wagon.wagon_sequence}</TableCell>
                                    <TableCell>{wagon.wagon_type || '-'}</TableCell>
                                    <TableCell>{wagon.pcc_weight_mt ? `${wagon.pcc_weight_mt} MT` : '-'}</TableCell>
                                    <TableCell>
                                        <Badge variant={wagon.is_unfit ? "destructive" : "default"}>
                                            {wagon.is_unfit ? "Unfit" : "Fit"}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{wagon.state || '-'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </ScrollArea>
            </DialogContent>
        </Dialog>
    );
}
