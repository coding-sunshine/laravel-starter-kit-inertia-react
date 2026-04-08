import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { AlertCircle } from 'lucide-react';
import type { ImportBatchSummary } from './types';
import { formatVehicleDispatchDate } from './utils';

interface ImportPreviewCardProps {
    previewData: Record<string, unknown>[];
    importBatchSummary: ImportBatchSummary | null;
    importErrors: string[];
    isImporting: boolean;
    onClearPreview: () => void;
    onSaveToDatabase: () => void;
}

export default function ImportPreviewCard({
    previewData,
    importBatchSummary,
    importErrors,
    isImporting,
    onClearPreview,
    onSaveToDatabase,
}: ImportPreviewCardProps) {
    if (previewData.length === 0) return null;

    const summaryLine =
        importBatchSummary && importBatchSummary.skipped > 0
            ? `${previewData.length} new record(s) ready to save; ${importBatchSummary.skipped} skipped (pass number already in database) out of ${importBatchSummary.totalRows} pasted row(s).`
            : null;

    return (
        <Card className="mt-6">
            <CardHeader>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                    <div className="min-w-0 flex-1 space-y-1.5">
                        <CardTitle>
                            Import Preview — {previewData.length} new record(s)
                        </CardTitle>
                        <CardDescription>
                            {summaryLine ??
                                'Review the parsed data below. Click "Save to Database" to store these records or "Clear Preview" to remove them.'}
                        </CardDescription>
                    </div>
                    <div className="flex shrink-0 flex-wrap items-center justify-end gap-2 self-start">
                        <Button
                            variant="outline"
                            onClick={onClearPreview}
                            disabled={isImporting}
                        >
                            Clear Preview
                        </Button>
                        <Button
                            onClick={onSaveToDatabase}
                            disabled={isImporting}
                        >
                            {isImporting ? 'Saving...' : 'Save to Database'}
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="pt-0">
                <Accordion type="single" collapsible className="w-full">
                    <AccordionItem value="preview-table" className="border-b-0">
                        <AccordionTrigger
                            data-pan="vehicle-dispatch-import-preview-accordion"
                            className="justify-between py-3 text-sm font-medium hover:no-underline [&[data-state=open]]:pb-2"
                        >
                            <span className="text-left text-muted-foreground">
                                Preview table — {previewData.length} row
                                {previewData.length === 1 ? '' : 's'} (click to
                                expand)
                            </span>
                        </AccordionTrigger>
                        <AccordionContent className="pb-0">
                            <div className="overflow-x-auto pb-4">
                                <table className="min-w-full border border-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Serial
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Permit No
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Pass No
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Stack DO No
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Issued On
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Truck Regd No
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Mineral
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Mineral Type
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Weight
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Source
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Destination
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Consignee
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Check Gate
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Distance
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Siding
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Shift
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {previewData.map((row, index) => (
                                            <tr
                                                key={index}
                                                className="hover:bg-gray-50"
                                            >
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.serial_no ?? '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.permit_no ?? '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(row.pass_no ?? '-')}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.stack_do_no ?? '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {formatVehicleDispatchDate(
                                                        row.issued_on != null
                                                            ? String(
                                                                  row.issued_on,
                                                              )
                                                            : null,
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.truck_regd_no ??
                                                            '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(row.mineral ?? '-')}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.mineral_type ?? '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.mineral_weight ??
                                                            '-',
                                                    )}
                                                </td>
                                                <td
                                                    className="max-w-xs truncate px-4 py-2 text-sm text-gray-900"
                                                    title={String(
                                                        row.source ?? '',
                                                    )}
                                                >
                                                    {String(row.source ?? '-')}
                                                </td>
                                                <td
                                                    className="max-w-xs truncate px-4 py-2 text-sm text-gray-900"
                                                    title={String(
                                                        row.destination ?? '',
                                                    )}
                                                >
                                                    {String(
                                                        row.destination ?? '-',
                                                    )}
                                                </td>
                                                <td
                                                    className="max-w-xs truncate px-4 py-2 text-sm text-gray-900"
                                                    title={String(
                                                        row.consignee ?? '',
                                                    )}
                                                >
                                                    {String(
                                                        row.consignee ?? '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.check_gate ?? '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(
                                                        row.distance_km ?? '-',
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {row.siding &&
                                                    typeof row.siding ===
                                                        'object' &&
                                                    row.siding.name
                                                        ? `${row.siding.name} (${row.siding.code})`
                                                        : '-'}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {String(row.shift ?? '-')}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                {importErrors.length > 0 && (
                    <div className="mt-4 rounded border border-red-200 bg-red-50 p-3">
                        <div className="flex items-center gap-2 text-red-800">
                            <AlertCircle className="h-4 w-4" />
                            <span className="font-medium">Save Errors:</span>
                        </div>
                        <div className="mt-2 space-y-1">
                            {importErrors.map((error, index) => (
                                <div
                                    key={index}
                                    className="text-sm text-red-700"
                                >
                                    {error}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
