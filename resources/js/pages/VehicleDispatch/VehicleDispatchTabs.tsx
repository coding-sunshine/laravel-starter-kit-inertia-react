import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import { Database, FileBarChart } from 'lucide-react';

export type VehicleDispatchTabValue = 'main-data' | 'dpr';

interface VehicleDispatchTabsProps {
    activeTab: VehicleDispatchTabValue;
    onTabChange: (value: VehicleDispatchTabValue) => void;
    children: React.ReactNode;
}

const tabs: { value: VehicleDispatchTabValue; icon: typeof Database; label: string }[] = [
    { value: 'main-data', icon: Database, label: 'JIMMS Data' },
    { value: 'dpr', icon: FileBarChart, label: 'DPR' },
];

export default function VehicleDispatchTabs({
    activeTab,
    onTabChange,
    children,
}: VehicleDispatchTabsProps) {
    return (
        <div className="space-y-4">
            <ToggleGroup
                type="single"
                value={activeTab}
                onValueChange={(value) => {
                    if (value) onTabChange(value as VehicleDispatchTabValue);
                }}
                className={cn(
                    'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                )}
            >
                {tabs.map(({ value, icon: Icon, label }) => (
                    <ToggleGroupItem
                        key={value}
                        value={value}
                        aria-label={label}
                        data-pan={`vehicle-dispatch-tab-${value}`}
                        className={cn(
                            'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                            activeTab === value
                                ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                        )}
                    >
                        <Icon className="-ml-1 h-4 w-4" />
                        <span className="ml-1.5 text-sm">{label}</span>
                    </ToggleGroupItem>
                ))}
            </ToggleGroup>
            {children}
        </div>
    );
}
