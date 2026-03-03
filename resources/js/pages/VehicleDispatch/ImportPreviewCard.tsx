import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { AlertCircle } from 'lucide-react';
import { formatVehicleDispatchDate } from './utils';

interface ImportPreviewCardProps {
    previewData: Record<string, unknown>[];
    importErrors: string[];
    isImporting: boolean;
    onClearPreview: () => void;
    onSaveToDatabase: () => void;
}

export default function ImportPreviewCard({
    previewData,
    importErrors,
    isImporting,
    onClearPreview,
    onSaveToDatabase,
}: ImportPreviewCardProps) {
    if (previewData.length === 0) return null;

    return (
        <Card className="mt-6">
            <CardHeader>
                <CardTitle>Import Preview - {previewData.length} Records</CardTitle>
                <CardDescription>
                    Review the parsed data below. Click "Save to Database" to store these records or
                    "Clear Preview" to remove them.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">
                    <table className="min-w-full border border-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Serial
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Permit No
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pass No
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stack DO No
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Issued On
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Truck Regd No
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mineral
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mineral Type
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Weight
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Source
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Destination
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Consignee
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Check Gate
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Distance
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Siding
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Shift
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {previewData.map((row, index) => (
                                <tr key={index} className="hover:bg-gray-50">
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.serial_no ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.permit_no ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.pass_no ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.stack_do_no ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {formatVehicleDispatchDate(
                                            row.issued_on != null ? String(row.issued_on) : null,
                                        )}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.truck_regd_no ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.mineral ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.mineral_type ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.mineral_weight ?? '-')}
                                    </td>
                                    <td
                                        className="px-4 py-2 text-sm text-gray-900 max-w-xs truncate"
                                        title={String(row.source ?? '')}
                                    >
                                        {String(row.source ?? '-')}
                                    </td>
                                    <td
                                        className="px-4 py-2 text-sm text-gray-900 max-w-xs truncate"
                                        title={String(row.destination ?? '')}
                                    >
                                        {String(row.destination ?? '-')}
                                    </td>
                                    <td
                                        className="px-4 py-2 text-sm text-gray-900 max-w-xs truncate"
                                        title={String(row.consignee ?? '')}
                                    >
                                        {String(row.consignee ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.check_gate ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.distance_km ?? '-')}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {row.siding && typeof row.siding === 'object' && row.siding.name 
                                            ? `${row.siding.name} (${row.siding.code})`
                                            : '-'
                                        }
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-900">
                                        {String(row.shift ?? '-')}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {importErrors.length > 0 && (
                    <div className="mt-4 bg-red-50 border border-red-200 rounded p-3">
                        <div className="flex items-center gap-2 text-red-800">
                            <AlertCircle className="h-4 w-4" />
                            <span className="font-medium">Save Errors:</span>
                        </div>
                        <div className="mt-2 space-y-1">
                            {importErrors.map((error, index) => (
                                <div key={index} className="text-sm text-red-700">
                                    {error}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <div className="flex justify-end gap-2 mt-4">
                    <Button variant="outline" onClick={onClearPreview} disabled={isImporting}>
                        Clear Preview
                    </Button>
                    <Button onClick={onSaveToDatabase} disabled={isImporting}>
                        {isImporting ? 'Saving...' : 'Save to Database'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
