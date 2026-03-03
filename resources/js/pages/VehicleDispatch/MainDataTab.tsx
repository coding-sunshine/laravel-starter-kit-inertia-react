import type { VehicleDispatch, VehicleDispatchPagination, Filters } from './types';
import ImportPreviewCard from './ImportPreviewCard';
import VehicleDispatchTable from './VehicleDispatchTable';

interface MainDataTabProps {
    vehicleDispatches: VehicleDispatchPagination;
    searchFilters: Filters;
    previewData: Record<string, unknown>[];
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
    importErrors,
    isImporting,
    onEditDispatch,
    onClearPreview,
    onSaveImport,
}: MainDataTabProps) {
    return (
        <div className="space-y-6">
            <ImportPreviewCard
                previewData={previewData}
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
