/**
 * RRMCS domain glossary — plain-English definitions for railway/coal jargon.
 * Used by the GlossaryTerm component to render tooltips on table headers.
 */
export const rrmcsGlossary: Record<string, string> = {
    FNR: 'Freight Note Reference — unique ID printed on the Railway Receipt identifying the consignment.',
    RR: 'Railway Receipt — official document issued by railways confirming goods dispatched, with weights and charges.',
    TXR: 'Train Examination Report — document confirming rake positioning and fitness for loading.',
    MT: 'Metric Tonnes — standard unit of weight (1 MT = 1,000 kg).',
    Demurrage: 'Penalty charged when loading/unloading exceeds the allowed free time (hours × MT × rate).',
    'Free time': 'Hours allowed by railways to load/unload a rake before demurrage charges begin.',
    'e-Demand': 'Electronic indent booking system used by Indian Railways for rake placement requests.',
    IMWB: 'In-Motion Weighbridge — weighs wagons while the rake moves through, providing per-wagon weights.',
    Indent: 'Formal request to railways for a rake to be placed at a siding for loading.',
    Rake: 'A set of wagons coupled together as one train unit, typically 40–59 wagons.',
    Wagon: 'Individual rail car within a rake; identified by a unique wagon number and type code.',
    Siding: 'Private railway line connected to the main network, used for loading/unloading at a mine or plant.',
    Weighment: 'The act of weighing wagons (or vehicles) at a weighbridge to record gross, tare, and net weights.',
    Overload: 'When wagon weight exceeds the permissible carrying capacity, attracting penalties from railways.',
    Challan: 'Transport document accompanying a vehicle shipment with quantity, origin, and destination details.',
    Reconciliation: 'Comparing weights at different points (mine, siding, RR, power plant) to detect variance or loss.',
    Penalty: 'Financial charge imposed for demurrage, overloading, or other rule violations during rake operations.',
    'Power Plant Receipt': 'Weight record from the destination power plant, used as the final reconciliation point.',
    Variance: 'Difference in weight between two measurement points (e.g., siding weighment vs RR weight).',
    'Stock Ledger': 'Running record of coal inventory at a siding — updated by arrivals, dispatches, and adjustments.',
};
