import type { VehicleDispatch, VehicleDispatchPagination, Filters, ImportBatchSummary } from './types';
import ImportPreviewCard from './ImportPreviewCard';
import VehicleDispatchTable from './VehicleDispatchTable';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Info } from 'lucide-react';

interface MainDataTabProps {
    vehicleDispatches: VehicleDispatchPagination;
    searchFilters: Filters;
    previewData: Record<string, unknown>[];
    importBatchSummary: ImportBatchSummary | null;
    importErrors: string[];
    isImporting: boolean;
    onEditDispatch: (dispatch: VehicleDispatch) => void;
    onClearPreview: () => void;
    onSaveImport: () => void;
}

export default function MainDataTab({
    vehicleDispatches,
    searchFilters,
    previewData,
    importBatchSummary,
    importErrors,
    isImporting,
    onEditDispatch,
    onClearPreview,
    onSaveImport,
}: MainDataTabProps) {
    const showAllSkippedBanner =
        importBatchSummary !== null &&
        importBatchSummary.newCount === 0 &&
        importBatchSummary.skipped > 0;

    return (
        <div className="space-y-6">
            {showAllSkippedBanner && (
                <Alert className="border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                    <Info className="text-amber-700 dark:text-amber-300" />
                    <AlertTitle>All rows skipped</AlertTitle>
                    <AlertDescription>
                        {importBatchSummary.skipped} of {importBatchSummary.totalRows} row(s) were skipped
                        because the pass number already exists in the database. No new records to preview.
                    </AlertDescription>
                </Alert>
            )}
            <ImportPreviewCard
                previewData={previewData}
                importBatchSummary={importBatchSummary}
                importErrors={importErrors}
                isImporting={isImporting}
                onClearPreview={onClearPreview}
                onSaveToDatabase={onSaveImport}
            />
            <VehicleDispatchTable
                vehicleDispatches={vehicleDispatches}
                searchFilters={searchFilters}
                onEditDispatch={onEditDispatch}
            />
        </div>
    );
}
