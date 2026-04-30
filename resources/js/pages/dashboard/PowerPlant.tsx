import { PowerPlantDispatchSection } from '../dashboard';
import type { PowerPlantDispatchItem } from './types';

interface Props {
    canWidget: (name: string) => boolean;
    powerPlantDispatch: PowerPlantDispatchItem[];
}

export function PowerPlant({ canWidget, powerPlantDispatch }: Props) {
    if (!canWidget('dashboard.widgets.power_plant_dispatch_section')) return null;
    return <PowerPlantDispatchSection data={powerPlantDispatch} />;
}
