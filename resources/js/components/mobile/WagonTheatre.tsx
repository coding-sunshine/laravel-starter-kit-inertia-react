/**
 * WagonTheatre Component - Visual wagon selector grid
 * Displays 60 wagons in a responsive grid with status indicators
 * Used for rake composition and wagon loading workflows
 */

import { useState } from 'react';

export interface Wagon {
    id: string;
    number: string;
    status: 'unfit' | 'pending' | 'loaded' | 'empty';
    weight?: number;
    notes?: string;
}

interface WagonTheatreProps {
    wagons: Wagon[];
    selectedWagons?: string[];
    onSelect?: (wagonId: string) => void;
    onMultiSelect?: (wagonIds: string[]) => void;
    maxWagons?: number;
    readOnly?: boolean;
}

const statusColors = {
    unfit: 'bg-red-500 hover:bg-red-600',
    pending: 'bg-blue-500 hover:bg-blue-600',
    loaded: 'bg-green-500 hover:bg-green-600',
    empty: 'bg-gray-300 hover:bg-gray-400',
};

const statusLabels = {
    unfit: 'Unfit',
    pending: 'Pending',
    loaded: 'Loaded',
    empty: 'Empty',
};

export function WagonTheatre({
    wagons,
    selectedWagons = [],
    onSelect,
    onMultiSelect,
    maxWagons = 60,
    readOnly = false,
}: WagonTheatreProps) {
    const [multiSelectMode, setMultiSelectMode] = useState(false);
    const [selections, setSelections] = useState<Set<string>>(
        () => new Set(selectedWagons),
    );

    const handleWagonClick = (wagonId: string) => {
        if (readOnly) return;

        if (multiSelectMode) {
            const newSelections = new Set(selections);
            if (newSelections.has(wagonId)) {
                newSelections.delete(wagonId);
            } else if (newSelections.size < maxWagons) {
                newSelections.add(wagonId);
            }
            setSelections(newSelections);
            onMultiSelect?.(Array.from(newSelections));
        } else {
            onSelect?.(wagonId);
        }
    };

    const clearSelections = () => {
        setSelections(new Set());
        onMultiSelect?.([]);
    };

    // Pad wagons array to 60 with empty placeholders
    const paddedWagons = Array.from(
        { length: maxWagons },
        (_, i) =>
            wagons[i] || {
                id: `empty-${i}`,
                number: `${i + 1}`,
                status: 'empty' as const,
            },
    );

    return (
        <div className="flex w-full flex-col gap-4 p-3">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-bold">Wagon Theatre</h3>
                    <p className="text-sm text-gray-600">
                        {selections.size}/{maxWagons} selected
                    </p>
                </div>
                <div className="flex gap-2">
                    {!readOnly && (
                        <>
                            <button
                                onClick={() =>
                                    setMultiSelectMode(!multiSelectMode)
                                }
                                className={`rounded px-3 py-2 text-sm font-medium transition-colors ${
                                    multiSelectMode
                                        ? 'bg-blue-500 text-white'
                                        : 'bg-gray-200 text-gray-800'
                                }`}
                            >
                                {multiSelectMode ? 'Multi On' : 'Single'}
                            </button>
                            {multiSelectMode && selections.size > 0 && (
                                <button
                                    onClick={clearSelections}
                                    className="rounded bg-red-500 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-red-600"
                                >
                                    Clear
                                </button>
                            )}
                        </>
                    )}
                </div>
            </div>

            {/* Legend */}
            <div className="grid grid-cols-4 gap-2 text-xs">
                {Object.entries(statusLabels).map(([status, label]) => (
                    <div key={status} className="flex items-center gap-1">
                        <div
                            className={`h-4 w-4 rounded ${statusColors[status as keyof typeof statusColors]}`}
                        />
                        <span>{label}</span>
                    </div>
                ))}
            </div>

            {/* Wagon Grid - 6 columns, 10 rows for 60 wagons */}
            <div className="grid grid-cols-6 gap-2">
                {paddedWagons.map((wagon) => (
                    <button
                        key={wagon.id}
                        onClick={() => handleWagonClick(wagon.id)}
                        disabled={readOnly}
                        className={`flex aspect-square flex-col items-center justify-center rounded border-2 text-xs font-bold transition-all duration-200 ${
                            selections.has(wagon.id)
                                ? 'scale-105 border-black'
                                : 'border-transparent'
                        } ${
                            readOnly
                                ? 'cursor-not-allowed opacity-75'
                                : 'cursor-pointer active:scale-95'
                        } ${statusColors[wagon.status as keyof typeof statusColors]} text-white`}
                        title={`${wagon.number}${wagon.notes ? ': ' + wagon.notes : ''}`}
                    >
                        <span className="px-1 text-center break-words">
                            {wagon.number}
                        </span>
                        {wagon.weight && (
                            <span className="text-xs opacity-90">
                                {wagon.weight}T
                            </span>
                        )}
                    </button>
                ))}
            </div>

            {/* Status Summary */}
            {!readOnly && multiSelectMode && selections.size > 0 && (
                <div className="rounded bg-blue-50 p-3 text-sm">
                    <p className="font-medium">
                        Selected:{' '}
                        {Array.from(selections)
                            .map((id) => {
                                const wagon = wagons.find((w) => w.id === id);
                                return wagon?.number;
                            })
                            .join(', ')}
                    </p>
                </div>
            )}
        </div>
    );
}

export default WagonTheatre;
