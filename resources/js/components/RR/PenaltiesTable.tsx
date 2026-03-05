import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { useState } from 'react';
import type { PenaltyRow } from './types';

const PAGE_SIZE = 10;

interface PenaltiesTableProps {
    data: PenaltyRow[];
}

export function PenaltiesTable({ data }: PenaltiesTableProps) {
    const [page, setPage] = useState(1);
    const totalPages = Math.ceil(data.length / PAGE_SIZE) || 1;
    const start = (page - 1) * PAGE_SIZE;
    const rows = data.slice(start, start + PAGE_SIZE);

    return (
        <div className="space-y-4">
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Penalty Code</TableHead>
                            <TableHead>Penalty Name</TableHead>
                            <TableHead>Calculation Type</TableHead>
                            <TableHead className="text-right">
                                Amount
                            </TableHead>
                            <TableHead>Wagon Reference</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row, i) => (
                            <TableRow key={`${row.penaltyCode}-${i}`}>
                                <TableCell className="font-medium">
                                    {row.penaltyCode}
                                </TableCell>
                                <TableCell>{row.penaltyName}</TableCell>
                                <TableCell>{row.calculationType}</TableCell>
                                <TableCell className="text-right">
                                    {row.amount}
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {row.wagonReference ?? '-'}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            {data.length > PAGE_SIZE && (
                <div className="flex items-center justify-between px-2">
                    <p className="text-sm text-muted-foreground">
                        {data.length} penalty{data.length !== 1 ? 'ies' : ''}
                    </p>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            className="h-8 w-8"
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                            disabled={page <= 1}
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <span className="text-sm">
                            Page {page} / {totalPages}
                        </span>
                        <Button
                            variant="outline"
                            size="icon"
                            className="h-8 w-8"
                            onClick={() =>
                                setPage((p) => Math.min(totalPages, p + 1))
                            }
                            disabled={page >= totalPages}
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
