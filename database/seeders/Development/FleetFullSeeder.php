<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Fleet data for Phases 1–11 with 4–5 records per entity
 * so every Fleet page has data to play with.
 *
 * Run after: php artisan db:seed (Development) or
 * php artisan db:seed --class=Database\\Seeders\\Development\\FleetFullSeeder
 *
 * Prerequisites: migrations run, at least one user exists (e.g. from UsersSeeder).
 */
final class FleetFullSeeder extends Seeder
{
    /** @var list<string> */
    public array $dependencies = ['UsersSeeder'];

    private ?Organization $org = null;

    private ?User $user = null;

    private const COUNT = 5;

    public function run(): void
    {
        $this->ensureOrgAndUser();
        TenantContext::set($this->org);

        $this->seedPhase1Core();

        TenantContext::set(null);
        $this->command?->info('Fleet full seed completed.');
    }

    private function ensureOrgAndUser(): void
    {
        $this->user = User::query()->first();
        if (! $this->user) {
            $this->user = User::factory()->create([
                'name' => 'Fleet Demo User',
                'email' => 'fleet@example.com',
                'email_verified_at' => now(),
            ]);
            $this->command?->info('Created user fleet@example.com.');
        }

        // Seed into the first user's default organization so the dashboard shows data when they log in
        $this->org = $this->user->defaultOrganization();
        if (! $this->org) {
            $this->org = Organization::query()->first();
        }
        if (! $this->org) {
            $this->org = Organization::create([
                'name' => 'Fleet Demo',
                'slug' => 'fleet-demo',
                'owner_id' => $this->user->id,
            ]);
            $this->command?->info('Created organization Fleet Demo.');
        }

        if (! DB::table('organization_user')->where('organization_id', $this->org->id)->where('user_id', $this->user->id)->exists()) {
            $alreadyHasDefault = DB::table('organization_user')
                ->where('user_id', $this->user->id)
                ->where('is_default', true)
                ->exists();
            DB::table('organization_user')->insert([
                'organization_id' => $this->org->id,
                'user_id' => $this->user->id,
                'is_default' => ! $alreadyHasDefault,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command?->info('Attached user to organization.');
        }
    }

    private function seedPhase1Core(): void
    {
        $locIds = $this->seedRecords(\App\Models\Fleet\Location::class, self::COUNT, [
            ['name' => 'HQ Depot', 'type' => 'depot', 'address' => '1 Fleet Way', 'city' => 'London', 'country' => 'UK', 'lat' => 51.5074, 'lng' => -0.1278, 'is_active' => true],
            ['name' => 'North Yard', 'type' => 'yard', 'address' => '2 North Rd', 'city' => 'Manchester', 'country' => 'UK', 'lat' => 53.4808, 'lng' => -2.2426, 'is_active' => true],
            ['name' => 'South Hub', 'type' => 'depot', 'address' => '3 South St', 'city' => 'Birmingham', 'country' => 'UK', 'lat' => 52.4862, 'lng' => -1.8904, 'is_active' => true],
            ['name' => 'East Office', 'type' => 'office', 'address' => '4 East Ave', 'city' => 'Leeds', 'country' => 'UK', 'lat' => 53.8008, 'lng' => -1.5491, 'is_active' => true],
            ['name' => 'West Depot', 'type' => 'depot', 'address' => '5 West Lane', 'city' => 'Liverpool', 'country' => 'UK', 'lat' => 53.4084, 'lng' => -2.9916, 'is_active' => true],
        ]);
        $this->backfillLocationCoordinates();

        $this->seedRecords(\App\Models\Fleet\CostCenter::class, self::COUNT, [
            ['code' => 'CC001', 'name' => 'Operations', 'cost_center_type' => 'department', 'is_active' => true],
            ['code' => 'CC002', 'name' => 'Logistics', 'cost_center_type' => 'department', 'is_active' => true],
            ['code' => 'CC003', 'name' => 'Fleet North', 'cost_center_type' => 'region', 'is_active' => true],
            ['code' => 'CC004', 'name' => 'Fleet South', 'cost_center_type' => 'region', 'is_active' => true],
            ['code' => 'CC005', 'name' => 'Maintenance', 'cost_center_type' => 'department', 'is_active' => true],
        ]);

        $driverIds = $this->seedRecords(\App\Models\Fleet\Driver::class, self::COUNT, [
            ['first_name' => 'James', 'last_name' => 'Smith', 'employee_id' => 'E001', 'status' => 'active', 'license_number' => 'SMITH80JAMES', 'license_expiry_date' => now()->addYear(), 'license_status' => 'valid', 'safety_score' => 85, 'risk_category' => 'low', 'compliance_status' => 'compliant'],
            ['first_name' => 'Emma', 'last_name' => 'Jones', 'employee_id' => 'E002', 'status' => 'active', 'license_number' => 'JONES90EMMA', 'license_expiry_date' => now()->addYear(), 'license_status' => 'valid', 'safety_score' => 90, 'risk_category' => 'low', 'compliance_status' => 'compliant'],
            ['first_name' => 'Oliver', 'last_name' => 'Brown', 'employee_id' => 'E003', 'status' => 'active', 'license_number' => 'BROWN85OLIVER', 'license_expiry_date' => now()->addMonths(8), 'license_status' => 'valid', 'safety_score' => 78, 'risk_category' => 'medium', 'compliance_status' => 'compliant'],
            ['first_name' => 'Sophie', 'last_name' => 'Wilson', 'employee_id' => 'E004', 'status' => 'active', 'license_number' => 'WILSON92SOPHIE', 'license_expiry_date' => now()->addYear(), 'license_status' => 'valid', 'safety_score' => 88, 'risk_category' => 'low', 'compliance_status' => 'compliant'],
            ['first_name' => 'William', 'last_name' => 'Taylor', 'employee_id' => 'E005', 'status' => 'active', 'license_number' => 'TAYLOR88WILLIAM', 'license_expiry_date' => now()->addMonths(6), 'license_status' => 'valid', 'safety_score' => 82, 'risk_category' => 'low', 'compliance_status' => 'compliant'],
        ]);

        $this->seedRecords(\App\Models\Fleet\Trailer::class, 4, [
            ['registration' => 'TRL001', 'make' => 'SDC', 'model' => 'Tipping', 'type' => 'tipper', 'status' => 'active', 'compliance_status' => 'compliant'],
            ['registration' => 'TRL002', 'make' => 'Bockmann', 'model' => 'Flatbed', 'type' => 'flatbed', 'status' => 'active', 'compliance_status' => 'compliant'],
            ['registration' => 'TRL003', 'make' => 'Lawrence', 'model' => 'Box', 'type' => 'box', 'status' => 'active', 'compliance_status' => 'compliant'],
            ['registration' => 'TRL004', 'make' => 'SDC', 'model' => 'Curtain', 'type' => 'curtain', 'status' => 'active', 'compliance_status' => 'compliant'],
        ]);

        $vehicleIds = $this->seedRecords(\App\Models\Fleet\Vehicle::class, self::COUNT, [
            ['registration' => 'AB12 CDE', 'make' => 'Ford', 'model' => 'Transit', 'year' => 2022, 'fuel_type' => 'diesel', 'vehicle_type' => 'van', 'status' => 'active', 'odometer_reading' => 45000, 'compliance_status' => 'compliant', 'home_location_id' => $locIds[0] ?? null],
            ['registration' => 'FG34 HIJ', 'make' => 'Mercedes', 'model' => 'Sprinter', 'year' => 2023, 'fuel_type' => 'diesel', 'vehicle_type' => 'van', 'status' => 'active', 'odometer_reading' => 12000, 'compliance_status' => 'compliant', 'home_location_id' => $locIds[0] ?? null],
            ['registration' => 'KL56 MNO', 'make' => 'Volvo', 'model' => 'FH', 'year' => 2021, 'fuel_type' => 'diesel', 'vehicle_type' => 'truck', 'status' => 'active', 'odometer_reading' => 180000, 'compliance_status' => 'compliant', 'home_location_id' => $locIds[1] ?? null],
            ['registration' => 'PQ78 RST', 'make' => 'Vauxhall', 'model' => 'Vivaro', 'year' => 2023, 'fuel_type' => 'diesel', 'vehicle_type' => 'van', 'status' => 'active', 'odometer_reading' => 8000, 'compliance_status' => 'compliant', 'home_location_id' => $locIds[0] ?? null],
            ['registration' => 'UV90 WXY', 'make' => 'Tesla', 'model' => 'Model 3', 'year' => 2024, 'fuel_type' => 'electric', 'vehicle_type' => 'car', 'status' => 'active', 'odometer_reading' => 3000, 'compliance_status' => 'compliant', 'home_location_id' => $locIds[0] ?? null],
        ]);

        $this->seedVehicleLiveTrackingPositions($vehicleIds);

        $this->seedRecords(\App\Models\Fleet\Geofence::class, 4, [
            ['name' => 'HQ Zone', 'geofence_type' => 'circle', 'radius_meters' => 500, 'is_active' => true],
            ['name' => 'North Yard Zone', 'geofence_type' => 'circle', 'radius_meters' => 300, 'is_active' => true],
            ['name' => 'Depot Boundary', 'geofence_type' => 'polygon', 'is_active' => true],
            ['name' => 'No-Go Area', 'geofence_type' => 'circle', 'radius_meters' => 200, 'is_active' => true],
        ]);

        $garageIds = $this->seedRecords(\App\Models\Fleet\Garage::class, self::COUNT, [
            ['name' => 'Fleet Services HQ', 'type' => 'internal', 'city' => 'London', 'capacity' => 6, 'is_active' => true],
            ['name' => 'QuickFix Motors', 'type' => 'external', 'city' => 'Manchester', 'capacity' => 4, 'is_active' => true],
            ['name' => 'North Repairs', 'type' => 'external', 'city' => 'Leeds', 'capacity' => 4, 'is_active' => true],
            ['name' => 'South Garage', 'type' => 'internal', 'city' => 'Birmingham', 'capacity' => 5, 'is_active' => true],
            ['name' => 'Mobile Unit 1', 'type' => 'mobile', 'city' => 'Liverpool', 'capacity' => 1, 'is_active' => true],
        ]);

        $this->seedRecords(\App\Models\Fleet\FuelStation::class, self::COUNT, [
            ['name' => 'BP Fleet London', 'brand' => 'BP', 'address' => '10 Fuel Rd', 'city' => 'London', 'country' => 'UK', 'is_active' => true],
            ['name' => 'Shell Manchester', 'brand' => 'Shell', 'address' => '20 Motorway M1', 'city' => 'Manchester', 'country' => 'UK', 'is_active' => true],
            ['name' => 'Esso Birmingham', 'brand' => 'Esso', 'address' => '30 Highway', 'city' => 'Birmingham', 'country' => 'UK', 'is_active' => true],
            ['name' => 'Texaco Leeds', 'brand' => 'Texaco', 'address' => '40 Station St', 'city' => 'Leeds', 'country' => 'UK', 'is_active' => true],
            ['name' => 'BP Liverpool', 'brand' => 'BP', 'address' => '50 Dock Rd', 'city' => 'Liverpool', 'country' => 'UK', 'is_active' => true],
        ]);

        $evStationIds = $this->seedRecords(\App\Models\Fleet\EvChargingStation::class, self::COUNT, [
            ['name' => 'HQ Chargers', 'operator' => 'Pod Point', 'address' => '1 Fleet Way London', 'access_type' => 'public', 'status' => 'active', 'total_connectors' => 4, 'available_connectors' => 3],
            ['name' => 'North EV Hub', 'operator' => 'ChargePoint', 'address' => '2 North Rd Manchester', 'access_type' => 'public', 'status' => 'active', 'total_connectors' => 6, 'available_connectors' => 4],
            ['name' => 'South Charging', 'operator' => 'InstaVolt', 'address' => '3 South St Birmingham', 'access_type' => 'public', 'status' => 'active', 'total_connectors' => 2, 'available_connectors' => 2],
            ['name' => 'East Chargers', 'operator' => 'Pod Point', 'address' => '4 East Ave Leeds', 'access_type' => 'fleet', 'status' => 'active', 'total_connectors' => 4, 'available_connectors' => 4],
            ['name' => 'West EV', 'operator' => 'Osprey', 'address' => '5 West Lane Liverpool', 'access_type' => 'public', 'status' => 'active', 'total_connectors' => 4, 'available_connectors' => 1],
        ]);

        $operatingCentre = [
            ['name' => 'HQ Depot', 'address' => '1 Fleet Way, London', 'max_vehicles' => 25, 'max_trailers' => 10],
        ];
        $this->seedRecords(\App\Models\Fleet\OperatorLicence::class, 4, [
            ['license_number' => 'OL001', 'license_type' => 'standard_national', 'traffic_commissioner_area' => 'eastern', 'issue_date' => now()->subYears(2), 'effective_date' => now()->subYears(2), 'expiry_date' => now()->addYears(3), 'authorized_vehicles' => 25, 'operating_centres' => $operatingCentre, 'status' => 'active'],
            ['license_number' => 'OL002', 'license_type' => 'standard_international', 'traffic_commissioner_area' => 'western', 'issue_date' => now()->subYear(), 'effective_date' => now()->subYear(), 'expiry_date' => now()->addYears(4), 'authorized_vehicles' => 50, 'operating_centres' => $operatingCentre, 'status' => 'active'],
            ['license_number' => 'OL003', 'license_type' => 'standard_national', 'traffic_commissioner_area' => 'north_eastern', 'issue_date' => now()->subMonths(18), 'effective_date' => now()->subMonths(18), 'expiry_date' => now()->addYears(2), 'authorized_vehicles' => 15, 'operating_centres' => $operatingCentre, 'status' => 'active'],
            ['license_number' => 'OL004', 'license_type' => 'restricted', 'traffic_commissioner_area' => 'southern', 'issue_date' => now()->subMonths(6), 'effective_date' => now()->subMonths(6), 'expiry_date' => now()->addYears(5), 'authorized_vehicles' => 10, 'operating_centres' => $operatingCentre, 'status' => 'active'],
        ]);

        $opLicenceIds = \App\Models\Fleet\OperatorLicence::query()->where('organization_id', $this->org->id)->pluck('id')->all();
        for ($i = 0; $i < min(4, count($vehicleIds), count($driverIds)); $i++) {
            \App\Models\Fleet\DriverVehicleAssignment::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                ['organization_id' => $this->org->id, 'driver_id' => $driverIds[$i], 'vehicle_id' => $vehicleIds[$i]],
                ['assignment_type' => 'primary', 'assigned_date' => now(), 'is_current' => true, 'assigned_by' => $this->user->id]
            );
        }

        $this->seedPhase2TripsFuel($vehicleIds, $driverIds, $garageIds, $locIds);
        $this->seedPhase3MaintenanceCompliance($vehicleIds, $driverIds, $garageIds);
        $this->seedPhase4Telematics($vehicleIds);
        $this->seedPhase5CarbonInsurance($vehicleIds);
        $this->seedPhase6EvTrainingAlerts($vehicleIds, $driverIds, $evStationIds);
        $this->seedPhase7WorkshopContractors($garageIds);
        $this->seedPhase8WellnessCoaching($driverIds);
        $this->seedPhase9ComplianceHs($vehicleIds, $driverIds, $locIds, $garageIds, $opLicenceIds);
        $this->seedPhase10FinesLeaseWarranty($vehicleIds, $driverIds);
        $this->seedPhase11ExtrasAudit($vehicleIds, $locIds);

        $this->seedFromLegacyDump($this->org);

        // Seed legacy data into every other org that already has fleet data,
        // so the dashboard shows updated counts in any org (e.g. Test Organization).
        $orgIdsWithVehicles = \App\Models\Fleet\Vehicle::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->distinct()
            ->pluck('organization_id');
        foreach ($orgIdsWithVehicles as $oid) {
            if ($oid === $this->org->id) {
                continue;
            }
            $otherOrg = Organization::query()->find($oid);
            if ($otherOrg instanceof Organization) {
                $this->seedFromLegacyDump($otherOrg);
            }
        }
    }

    /**
     * Seed additional data from the legacy (old) database dump so the app feels
     * like the old fleet app: more vehicles, locations (companies), defects,
     * and maintenance-style records. All data is created in the given org.
     */
    private function seedFromLegacyDump(Organization $org): void
    {
        $scope = \App\Models\Scopes\OrganizationScope::class;

        // Legacy companies → Locations (depots/offices)
        $legacyCompanyNames = [
            'Caldicot', 'Commercial Motors - Man', 'Coryton', 'Cruselys', 'Ebor Trucks',
            'GCA', 'ICL - Hebburn', 'ICL - West thurrock', 'ICL - Widnes', 'Industrial Chemicals',
            'Lynch Transport', 'MAN - Gateshead', 'Pelican', 'Pullman - Ellesmere Port', 'Purfleet Commercials',
            'Rema Tip Top', 'Renault - West Thurrock', 'S&B Commercials', 'Suttons', 'Thinktank',
            'Thompsons Commercials - Renault', 'Woodwards - Wigan (Renault)', 'Yorkshire Rubber Linings',
        ];
        $legacyLocIds = [];
        foreach (array_slice($legacyCompanyNames, 0, 18) as $name) {
            $loc = \App\Models\Fleet\Location::withoutGlobalScope($scope)->firstOrCreate(
                ['organization_id' => $org->id, 'name' => $name],
                [
                    'type' => 'depot',
                    'address' => $name . ', UK',
                    'city' => 'UK',
                    'country' => 'GB',
                    'is_active' => true,
                ]
            );
            $legacyLocIds[] = $loc->id;
        }

        // Legacy vehicle types: id => [make, model, vehicle_type (our enum), fuel_type]
        $legacyVehicleTypes = [
            1 => ['make' => 'MAN', 'model' => 'TGX', 'vehicle_type' => 'truck', 'fuel_type' => 'diesel'],
            2 => ['make' => 'MAN', 'model' => 'Rigid TGM', 'vehicle_type' => 'truck', 'fuel_type' => 'diesel'],
            3 => ['make' => 'MAN', 'model' => 'T480', 'vehicle_type' => 'truck', 'fuel_type' => 'diesel'],
            4 => ['make' => 'Mercedes', 'model' => 'Sprinter', 'vehicle_type' => 'van', 'fuel_type' => 'diesel'],
            5 => ['make' => 'DAF', 'model' => 'Rigid', 'vehicle_type' => 'truck', 'fuel_type' => 'diesel'],
        ];

        // Legacy vehicles from dump: id => [registration, type_id, status, odometer, mot_expiry]
        $legacyVehicles = [
            1 => ['EU15ZPR', 1, 'Roadworthy', 391091, '2024-01-31'],
            2 => ['EU15ZPT', 1, 'Roadworthy', 0, '2024-03-31'],
            3 => ['EX68BXG', 1, 'Roadworthy', 0, null],
            4 => ['EX68BXH', 1, 'Roadworthy', 0, null],
            5 => ['EX68BXJ', 1, 'Roadworthy', 0, null],
            6 => ['EX68BXK', 1, 'Roadworthy', 0, null],
            7 => ['EX68BXL', 1, 'Roadworthy', 0, null],
            8 => ['EX68BXM', 1, 'Roadworthy', 0, null],
            9 => ['EX68BXN', 1, 'Roadworthy', 0, null],
            10 => ['EX68BXO', 1, 'Roadworthy', 0, null],
            11 => ['EX68BXP', 1, 'Roadworthy', 0, null],
            12 => ['EX68BXR', 1, 'Roadworthy', 0, null],
            13 => ['EY16FHV', 2, 'Roadworthy', 0, null],
            14 => ['EY21TDX', 1, 'Roadworthy', 0, null],
            15 => ['EY21TDZ', 1, 'Roadworthy', 0, null],
            16 => ['EY21TFA', 1, 'Roadworthy', 0, null],
            17 => ['EY21TFE', 1, 'Roadworthy', 0, null],
            18 => ['EY21TFF', 1, 'Roadworthy', 0, null],
            19 => ['EY21TFJ', 1, 'Roadworthy', 0, null],
            20 => ['EY21TFK', 1, 'Roadworthy', 0, null],
            21 => ['EY21TFN', 1, 'Roadworthy', 0, null],
            22 => ['EY66MZP', 2, 'Roadworthy', 0, null],
            23 => ['EY66MZT', 2, 'Roadworthy', 0, null],
            24 => ['EY66MZU', 2, 'Roadworthy', 0, null],
            25 => ['EY70LNK', 1, 'Roadworthy', 0, null],
            26 => ['EY70LNN', 1, 'Roadworthy', 0, null],
            27 => ['LV68XDG', 3, 'Roadworthy', 0, null],
            28 => ['LV68XDH', 3, 'Archived', 0, null],
            29 => ['LV68XDJ', 3, 'Archived', 0, null],
            30 => ['LV68XDK', 3, 'Roadworthy', 0, null],
            31 => ['LV68XDL', 3, 'Archived', 0, null],
            32 => ['LV68XDM', 3, 'Archived', 0, null],
            33 => ['LV68XDN', 3, 'Archived', 0, null],
            34 => ['LX70MLJ', 3, 'Roadworthy', 0, null],
            35 => ['LX70MLL', 3, 'Roadworthy', 0, null],
            36 => ['LX70MLN', 3, 'Roadworthy', 0, null],
            37 => ['LX70MLO', 3, 'Roadworthy', 0, null],
            38 => ['LX70MLU', 3, 'Roadworthy', 0, null],
            39 => ['LX70MLV', 3, 'Roadworthy', 0, null],
            40 => ['LX70MLY', 3, 'Roadworthy', 0, null],
        ];

        $oldToNewVehicleId = [];
        $homeLocId = $legacyLocIds[0] ?? null;
        foreach ($legacyVehicles as $oldId => $row) {
            [$reg, $typeId, $status, $odometer, $motExpiry] = $row;
            $type = $legacyVehicleTypes[$typeId] ?? $legacyVehicleTypes[1];
            $ourStatus = $status === 'Archived' ? 'disposed' : 'active';
            $v = \App\Models\Fleet\Vehicle::withoutGlobalScope($scope)->firstOrCreate(
                ['organization_id' => $org->id, 'registration' => $reg],
                [
                    'make' => $type['make'],
                    'model' => $type['model'],
                    'year' => $this->yearFromRegistration($reg),
                    'fuel_type' => $type['fuel_type'],
                    'vehicle_type' => $type['vehicle_type'],
                    'status' => $ourStatus,
                    'odometer_reading' => $odometer ?: rand(50000, 250000),
                    'mot_expiry_date' => $motExpiry ? \Carbon\Carbon::parse($motExpiry) : null,
                    'compliance_status' => 'compliant',
                    'home_location_id' => $homeLocId,
                ]
            );
            $oldToNewVehicleId[$oldId] = $v->id;
        }

        $this->seedVehicleLiveTrackingPositions(array_values($oldToNewVehicleId));

        // Legacy defects (from dump): only for vehicles we just created (old_id 1, 22, etc.)
        $legacyDefects = [
            ['old_vehicle_id' => 1, 'defect_number' => '1669797052883', 'status' => 'resolved', 'reported_at' => '2022-12-06 11:03:38', 'resolved_at' => '2022-12-16 12:45:35'],
            ['old_vehicle_id' => 1, 'defect_number' => '1669878342798', 'status' => 'resolved', 'reported_at' => '2022-12-06 11:03:38', 'resolved_at' => '2022-12-16 12:36:17'],
            ['old_vehicle_id' => 1, 'defect_number' => '1669878357296', 'status' => 'resolved', 'reported_at' => '2022-12-06 11:03:38', 'resolved_at' => '2022-12-16 12:37:47'],
            ['old_vehicle_id' => 22, 'defect_number' => '1670318056566', 'status' => 'resolved', 'reported_at' => '2022-12-08 07:33:40', 'resolved_at' => '2022-12-16 12:39:47'],
            ['old_vehicle_id' => 22, 'defect_number' => '1670318164276', 'status' => 'resolved', 'reported_at' => '2022-12-08 07:33:41', 'resolved_at' => '2022-12-16 12:40:39'],
            ['old_vehicle_id' => 22, 'defect_number' => '1670318190151', 'status' => 'resolved', 'reported_at' => '2022-12-08 07:33:41', 'resolved_at' => '2022-12-16 12:40:59'],
            ['old_vehicle_id' => 22, 'defect_number' => '1670484646901', 'status' => 'resolved', 'reported_at' => '2022-12-08 07:33:41', 'resolved_at' => '2022-12-16 12:41:29'],
            ['old_vehicle_id' => 22, 'defect_number' => '1670484656287', 'status' => 'resolved', 'reported_at' => '2022-12-19 07:07:50', 'resolved_at' => '2022-12-27 08:51:14'],
            ['old_vehicle_id' => 22, 'defect_number' => '1670484666375', 'status' => 'resolved', 'reported_at' => '2022-12-19 07:07:51', 'resolved_at' => '2022-12-27 08:49:43'],
        ];
        foreach ($legacyDefects as $d) {
            $newVehicleId = $oldToNewVehicleId[$d['old_vehicle_id']] ?? null;
            if ($newVehicleId === null) {
                continue;
            }
            \App\Models\Fleet\Defect::withoutGlobalScope($scope)->firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'vehicle_id' => $newVehicleId,
                    'defect_number' => $d['defect_number'],
                ],
                [
                    'title' => 'Defect from pre-trip check',
                    'description' => 'Defect reported from walk-around / pre-trip check.',
                    'category' => 'safety',
                    'severity' => 'minor',
                    'status' => $d['status'],
                    'reported_at' => $d['reported_at'],
                    'resolution_date' => $d['resolved_at'] ?? null,
                ]
            );
        }

        // Extra defects across legacy vehicles so lists look full
        $titles = ['Dashboard warning light', 'Brake wear indicator', 'Tyre pressure low', 'Windscreen chip', 'Mirror adjustment'];
        foreach (array_slice($legacyVehicles, 0, 15, true) as $oldId => $row) {
            $newVehicleId = $oldToNewVehicleId[$oldId] ?? null;
            if ($newVehicleId === null) {
                continue;
            }
            $title = $titles[array_rand($titles)];
            \App\Models\Fleet\Defect::withoutGlobalScope($scope)->firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'vehicle_id' => $newVehicleId,
                    'defect_number' => 'LEG-' . $oldId . '-' . rand(100, 999),
                ],
                [
                    'title' => $title,
                    'description' => 'Reported from legacy system.',
                    'category' => 'safety',
                    'severity' => 'minor',
                    'status' => rand(0, 2) === 0 ? 'resolved' : 'reported',
                    'reported_at' => now()->subDays(rand(1, 60)),
                ]
            );
        }

        // Service schedules for legacy vehicles (maintenance_events from dump: MOT, Annual service, etc.)
        $serviceTypes = ['mot', 'annual_service_inspection', 'next_service_inspection', 'preventative_maintenance_inspection'];
        foreach (array_slice($oldToNewVehicleId, 0, 25, true) as $oldId => $newVehicleId) {
            $type = $serviceTypes[$oldId % count($serviceTypes)];
            \App\Models\Fleet\ServiceSchedule::withoutGlobalScope($scope)->firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'vehicle_id' => $newVehicleId,
                    'service_type' => $type,
                ],
                [
                    'interval_type' => 'time',
                    'interval_value' => 12,
                    'interval_unit' => 'months',
                    'last_service_date' => now()->subMonths(rand(3, 8)),
                    'alert_days_before' => 14,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Legacy dump seed: ' . count($legacyVehicles) . ' vehicles, ' . count($legacyLocIds) . ' locations (from legacy companies), defects and service schedules.');
    }

    /** Derive approximate year from UK-style registration (e.g. EU15 → 2015, EY21 → 2021, LX70 → 2020). */
    private function yearFromRegistration(string $reg): int
    {
        if (preg_match('/\d{2}/', $reg, $m)) {
            $yy = (int) $m[0];
            return $yy <= (int) date('y') + 1 ? 2000 + $yy : 1900 + $yy;
        }
        return (int) date('Y') - rand(2, 6);
    }

    private function seedPhase2TripsFuel(array $vehicleIds, array $driverIds, array $garageIds, array $locIds): void
    {
        $routeIds = $this->seedRecords(\App\Models\Fleet\Route::class, 4, [
            ['name' => 'London-Manchester', 'route_type' => 'delivery', 'description' => 'A1 route', 'is_active' => true, 'start_location_id' => $locIds[0] ?? null, 'end_location_id' => $locIds[1] ?? null],
            ['name' => 'Birmingham-Leeds', 'route_type' => 'delivery', 'description' => 'M1 route', 'is_active' => true, 'start_location_id' => $locIds[2] ?? null, 'end_location_id' => $locIds[3] ?? null],
            ['name' => 'HQ Local', 'route_type' => 'local', 'description' => 'Local deliveries', 'is_active' => true],
            ['name' => 'North Loop', 'route_type' => 'regional', 'description' => 'North region loop', 'is_active' => true],
        ]);

        $this->seedRouteStopsForMap($routeIds[0] ?? null, $locIds);

        for ($i = 0; $i < 4; $i++) {
            $started = now()->subDays(rand(0, 14))->setTime(8, 0);
            \App\Models\Fleet\Trip::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'started_at' => $started,
                ],
                [
                    'driver_id' => $driverIds[$i % count($driverIds)] ?? null,
                    'route_id' => $routeIds[$i % count($routeIds)] ?? null,
                    'ended_at' => $started->copy()->addHours(2),
                    'distance_km' => 100,
                    'duration_minutes' => 120,
                    'status' => 'completed',
                ]
            );
        }
        $this->seedTripWaypointsForMap($locIds);

        $cardPrefix = 'FC****' . $this->org->id . '-';
        $fuelCardIds = $this->seedRecords(\App\Models\Fleet\FuelCard::class, self::COUNT, [
            ['card_number' => $cardPrefix . '0001', 'provider' => 'Allstar', 'card_type' => 'fleet', 'status' => 'active'],
            ['card_number' => $cardPrefix . '0002', 'provider' => 'Allstar', 'card_type' => 'fleet', 'status' => 'active'],
            ['card_number' => $cardPrefix . '0003', 'provider' => 'KeyFuels', 'card_type' => 'fleet', 'status' => 'active'],
            ['card_number' => $cardPrefix . '0004', 'provider' => 'Allstar', 'card_type' => 'fleet', 'status' => 'active'],
            ['card_number' => $cardPrefix . '0005', 'provider' => 'KeyFuels', 'card_type' => 'fleet', 'status' => 'active'],
        ]);

        for ($i = 0; $i < self::COUNT; $i++) {
            $ts = now()->subDays($i);
            \App\Models\Fleet\FuelTransaction::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'transaction_timestamp' => $ts,
                ],
                [
                    'fuel_card_id' => $fuelCardIds[$i] ?? $fuelCardIds[0],
                    'litres' => 50 + $i * 5,
                    'price_per_litre' => 1.45,
                    'total_cost' => 75,
                    'fuel_type' => 'diesel',
                    'validation_status' => 'validated',
                ]
            );
        }
    }

    private function seedPhase3MaintenanceCompliance(array $vehicleIds, array $driverIds, array $garageIds): void
    {
        $schedIds = [];
        for ($i = 0; $i < self::COUNT; $i++) {
            $sched = \App\Models\Fleet\ServiceSchedule::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'service_type' => $i % 2 === 0 ? 'oil_change' : 'inspection',
                ],
                [
                    'interval_type' => 'distance',
                    'interval_value' => 15000,
                    'interval_unit' => 'km',
                    'last_service_mileage' => 30000,
                    'last_service_date' => now()->subMonths(6),
                    'alert_days_before' => 14,
                    'alert_km_before' => 500,
                    'is_active' => true,
                ]
            );
            $schedIds[] = $sched->id;
        }

        $woIds = [];
        for ($i = 0; $i < self::COUNT; $i++) {
            $wo = \App\Models\Fleet\WorkOrder::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'work_order_number' => 'WO-' . (2000 + $i),
                ],
                [
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'title' => 'Service ' . ($i + 1),
                    'work_type' => 'service',
                    'status' => $i === 0 ? 'completed' : 'in_progress',
                    'scheduled_date' => now()->addDays($i),
                ]
            );
            $woIds[] = $wo->id;
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\Defect::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'defect_number' => 'DEF-' . (100 + $i),
                ],
                [
                    'title' => 'Minor defect ' . ($i + 1),
                    'description' => 'Minor defect description ' . ($i + 1),
                    'category' => 'safety',
                    'severity' => 'minor',
                    'status' => 'reported',
                    'reported_at' => now()->subDays($i),
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\ComplianceItem::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'entity_type' => 'vehicle',
                    'entity_id' => $vehicleIds[$i % count($vehicleIds)],
                    'compliance_type' => 'mot',
                ],
                [
                    'title' => 'MOT Vehicle ' . ($i + 1),
                    'status' => 'valid',
                    'expiry_date' => now()->addYear(),
                ]
            );
        }
    }

    private function seedPhase4Telematics(array $vehicleIds): void
    {
        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\TelematicsDevice::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'device_id' => 'TEL-' . $this->org->id . '-' . (1000 + $i),
                ],
                [
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'provider' => 'Samsara',
                    'status' => 'active',
                ]
            );
        }
    }

    private function seedPhase5CarbonInsurance(array $vehicleIds): void
    {
        $this->seedRecords(\App\Models\Fleet\EmissionsRecord::class, 4, array_map(fn ($i) => [
            'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
            'record_date' => now()->subDays($i * 7),
            'scope' => 'vehicle',
            'emissions_type' => 'fuel_combustion',
            'co2_kg' => 100 + $i * 10,
            'distance_km' => 200,
        ], range(0, 3)));

        $this->seedRecords(\App\Models\Fleet\CarbonTarget::class, 4, [
            ['name' => 'Q1 2025', 'period' => 'quarterly', 'target_year' => (int) now()->format('Y'), 'target_co2_kg' => 5000, 'is_active' => true],
            ['name' => 'Q2 2025', 'period' => 'quarterly', 'target_year' => (int) now()->format('Y'), 'target_co2_kg' => 4800, 'is_active' => true],
            ['name' => 'Annual', 'period' => 'annual', 'target_year' => (int) now()->format('Y'), 'target_co2_kg' => 20000, 'is_active' => true],
            ['name' => 'Fleet reduction', 'period' => 'annual', 'target_year' => (int) now()->format('Y'), 'target_co2_kg' => 18000, 'is_active' => true],
        ]);

        $this->seedRecords(\App\Models\Fleet\SustainabilityGoal::class, 4, [
            ['title' => 'Reduce emissions', 'target_value' => 10, 'target_unit' => 'percent', 'status' => 'active'],
            ['title' => 'EV uptake', 'target_value' => 20, 'target_unit' => 'percent', 'status' => 'active'],
            ['title' => 'Fuel efficiency', 'target_value' => 8, 'target_unit' => 'mpg', 'status' => 'active'],
            ['title' => 'Waste recycling', 'target_value' => 90, 'target_unit' => 'percent', 'status' => 'active'],
        ]);

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\InsurancePolicy::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'policy_number' => 'INS-' . $this->org->id . '-' . (5000 + $i),
                ],
                [
                    'insurer_name' => 'Fleet Insurer ' . ($i + 1),
                    'policy_type' => $i % 2 === 0 ? 'comprehensive' : 'fleet',
                    'coverage_type' => $i === 0 ? 'fleet' : 'any_driver',
                    'status' => 'active',
                    'start_date' => now()->subYear(),
                    'end_date' => now()->addYear(),
                    'covered_vehicles' => array_slice($vehicleIds, 0, 2),
                ]
            );
        }

        for ($i = 0; $i < 3; $i++) {
            \App\Models\Fleet\Incident::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'incident_number' => 'INC-' . (100 + $i),
                ],
                [
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'incident_date' => now()->subDays($i * 30),
                    'incident_time' => now()->toTimeString(),
                    'incident_timestamp' => now()->subDays($i * 30),
                    'incident_type' => 'collision',
                    'severity' => 'minor',
                    'description' => 'Minor incident ' . ($i + 1),
                    'status' => 'closed',
                ]
            );
        }
    }

    private function seedPhase6EvTrainingAlerts(array $vehicleIds, array $driverIds, array $evStationIds): void
    {
        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\EvChargingSession::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'session_id' => 'EVS-' . uniqid((string) $i),
                ],
                [
                    'vehicle_id' => $vehicleIds[min($i, count($vehicleIds) - 1)],
                    'charging_station_id' => $evStationIds[$i % count($evStationIds)],
                    'start_timestamp' => now()->subDays($i),
                    'session_type' => 'ac_fast',
                ]
            );
        }

        $courseIds = $this->seedRecords(\App\Models\Fleet\TrainingCourse::class, 4, [
            ['course_name' => 'Driver Safety', 'category' => 'safety', 'delivery_method' => 'classroom', 'duration_hours' => 4],
            ['course_name' => 'CPC Module', 'category' => 'compliance', 'delivery_method' => 'classroom', 'duration_hours' => 7],
            ['course_name' => 'Defensive Driving', 'category' => 'skills', 'delivery_method' => 'blended', 'duration_hours' => 8],
            ['course_name' => 'EV Awareness', 'category' => 'induction', 'delivery_method' => 'online', 'duration_hours' => 2],
        ]);

        $sessionIds = [];
        $schedDate = now()->addWeeks(1)->startOfDay();
        foreach ($courseIds as $idx => $cid) {
            $s = \App\Models\Fleet\TrainingSession::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'training_course_id' => $cid,
                    'scheduled_date' => $schedDate->copy()->addDays($idx),
                ],
                [
                    'session_name' => 'Session ' . ($idx + 1),
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'status' => 'scheduled',
                ]
            );
            $sessionIds[] = $s->id;
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\DriverQualification::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'driver_id' => $driverIds[$i % count($driverIds)],
                    'qualification_type' => 'license',
                ],
                [
                    'qualification_name' => 'Category C',
                    'qualification_number' => 'QL-' . (1000 + $i),
                    'status' => 'valid',
                    'expiry_date' => now()->addYear(),
                ]
            );
        }

        $costCenterIds = \App\Models\Fleet\CostCenter::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->where('organization_id', $this->org->id)->pluck('id')->all();
        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\CostAllocation::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'cost_center_id' => $costCenterIds[$i % max(1, count($costCenterIds))],
                    'allocation_date' => now()->subDays($i),
                ],
                [
                    'cost_type' => 'fuel',
                    'source_type' => 'manual_entry',
                    'amount' => 500 + $i * 100,
                    'vat_amount' => 0,
                    'approval_status' => 'approved',
                    'allocated_by' => $this->user->id,
                ]
            );
        }

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\Alert::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'alert_type' => $i % 2 === 0 ? 'maintenance_due' : 'compliance_expiry',
                    'title' => 'Alert ' . ($i + 1),
                ],
                [
                    'description' => 'Alert description ' . ($i + 1),
                    'severity' => 'warning',
                    'status' => 'active',
                    'triggered_at' => now()->subDays($i),
                ]
            );
        }

        $reportIds = $this->seedRecords(\App\Models\Fleet\Report::class, 4, [
            ['name' => 'Fleet Utilization', 'report_type' => 'fleet_utilization', 'schedule_frequency' => 'weekly', 'format' => 'pdf'],
            ['name' => 'Fuel Report', 'report_type' => 'fuel_efficiency', 'schedule_frequency' => 'monthly', 'format' => 'excel'],
            ['name' => 'Compliance Status', 'report_type' => 'compliance_status', 'schedule_frequency' => 'monthly', 'format' => 'pdf'],
            ['name' => 'Cost Analysis', 'report_type' => 'cost_analysis', 'schedule_frequency' => 'quarterly', 'format' => 'excel'],
        ]);
    }

    private function seedPhase7WorkshopContractors(array $garageIds): void
    {
        $supplierIds = $this->seedRecords(\App\Models\Fleet\PartsSupplier::class, 4, [
            ['name' => 'Parts Direct', 'contact_name' => 'John', 'is_active' => true],
            ['name' => 'Fleet Parts Co', 'contact_name' => 'Jane', 'is_active' => true],
            ['name' => 'Euro Car Parts', 'contact_name' => 'Bob', 'is_active' => true],
            ['name' => 'Allparts', 'contact_name' => 'Alice', 'is_active' => true],
        ]);

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\WorkshopBay::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'garage_id' => $garageIds[$i % count($garageIds)],
                    'name' => 'Bay ' . ($i + 1),
                ],
                ['status' => 'available']
            );
        }

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\PartsInventory::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'part_number' => 'P-' . (1000 + $i),
                ],
                [
                    'garage_id' => $garageIds[$i % count($garageIds)],
                    'description' => 'Part ' . ($i + 1),
                    'quantity' => 20,
                    'supplier_id' => $supplierIds[$i % count($supplierIds)] ?? null,
                ]
            );
        }

        $this->seedRecords(\App\Models\Fleet\TyreInventory::class, 4, [
            ['size' => '205/55 R16', 'quantity' => 20, 'is_active' => true],
            ['size' => '225/65 R17', 'quantity' => 15, 'is_active' => true],
            ['size' => '265/70 R19.5', 'quantity' => 8, 'is_active' => true],
            ['size' => '195/65 R15', 'quantity' => 25, 'is_active' => true],
        ]);

        $vehicleIds = \App\Models\Fleet\Vehicle::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->where('organization_id', $this->org->id)->pluck('id')->all();
        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\GreyFleetVehicle::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'registration' => 'GF' . $i . ' XXX',
                ],
                [
                    'user_id' => $this->user->id,
                    'make' => 'Private Car ' . ($i + 1),
                    'is_approved' => true,
                ]
            );
        }

        $greyIds = \App\Models\Fleet\GreyFleetVehicle::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->where('organization_id', $this->org->id)->pluck('id')->all();
        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\MileageClaim::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'grey_fleet_vehicle_id' => $greyIds[$i % count($greyIds)],
                    'user_id' => $this->user->id,
                    'claim_date' => now()->subDays($i),
                ],
                ['distance_km' => 100, 'status' => 'approved']
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\PoolVehicleBooking::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'user_id' => $this->user->id,
                    'booking_start' => now()->addDays($i),
                ],
                [
                    'booking_end' => now()->addDays($i)->addHours(8),
                    'status' => 'booked',
                ]
            );
        }

        $contractorIds = $this->seedRecords(\App\Models\Fleet\Contractor::class, 4, [
            ['name' => 'Recovery Plus', 'contractor_type' => 'recovery', 'status' => 'active'],
            ['name' => 'Mobile Mechanics', 'contractor_type' => 'maintenance', 'status' => 'active'],
            ['name' => 'Transport Solutions', 'contractor_type' => 'transport', 'status' => 'active'],
            ['name' => 'Fleet Support', 'contractor_type' => 'maintenance', 'status' => 'active'],
        ]);

        foreach ($contractorIds as $cid) {
            \App\Models\Fleet\ContractorCompliance::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'contractor_id' => $cid,
                    'compliance_type' => 'insurance',
                ],
                ['status' => 'valid', 'expiry_date' => now()->addYear()]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\ContractorInvoice::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'contractor_id' => $contractorIds[$i % count($contractorIds)],
                    'invoice_number' => 'INV-' . (3000 + $i),
                ],
                [
                    'invoice_date' => now()->subDays($i * 10),
                    'total_amount' => 500 + $i * 100,
                    'status' => 'paid',
                ]
            );
        }
    }

    private function seedPhase8WellnessCoaching(array $driverIds): void
    {
        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\DriverWellnessRecord::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'driver_id' => $driverIds[$i % count($driverIds)],
                    'record_date' => now()->subDays($i)->toDateString(),
                ],
                ['fatigue_level' => 2, 'rest_hours' => 7, 'sleep_quality' => 'good']
            );
        }

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\DriverCoachingPlan::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'driver_id' => $driverIds[$i % count($driverIds)],
                    'plan_type' => 'safety',
                    'title' => 'Coaching plan ' . ($i + 1),
                ],
                ['status' => $i % 2 === 0 ? 'active' : 'completed', 'due_date' => now()->addMonth()]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\WorkflowDefinition::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'name' => 'Workflow ' . ($i + 1),
                ],
                [
                    'trigger_type' => 'event',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedPhase9ComplianceHs(array $vehicleIds, array $driverIds, array $locIds, array $garageIds, array $opLicenceIds): void
    {
        $templateIds = $this->seedRecords(\App\Models\Fleet\VehicleCheckTemplate::class, 4, [
            ['name' => 'Daily Check', 'check_type' => 'daily', 'is_active' => true],
            ['name' => 'Weekly Inspection', 'check_type' => 'weekly', 'is_active' => true],
            ['name' => 'Pre-Trip', 'check_type' => 'pre_trip', 'is_active' => true],
            ['name' => 'Post-Trip', 'check_type' => 'post_trip', 'is_active' => true],
        ]);

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\VehicleCheck::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'vehicle_check_template_id' => $templateIds[$i % count($templateIds)],
                    'check_date' => now()->subDays($i)->toDateString(),
                ],
                ['status' => 'completed']
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\RiskAssessment::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'subject_type' => 'vehicle',
                    'subject_id' => $vehicleIds[$i % count($vehicleIds)],
                    'title' => 'Risk assessment ' . ($i + 1),
                ],
                [
                    'type' => 'driving',
                    'status' => 'approved',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\VehicleDisc::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'operator_licence_id' => $opLicenceIds[$i % count($opLicenceIds)],
                    'disc_number' => 'DISC-' . (100 + $i),
                ],
                [
                    'valid_from' => now()->subMonth(),
                    'valid_to' => now()->addYear(),
                    'status' => 'active',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\TachographCalibration::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                ],
                [
                    'calibration_date' => now()->subMonths(6),
                    'due_date' => now()->addMonths(6),
                    'status' => 'valid',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\SafetyPolicyAcknowledgment::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'user_id' => $this->user->id,
                    'policy_type' => 'Safety Policy v' . ($i + 1),
                ],
                [
                    'policy_reference' => 'POL-' . ($i + 1),
                    'acknowledged_at' => now()->subDays($i),
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\PermitToWork::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'permit_number' => 'PTW-' . (200 + $i),
                ],
                [
                    'issued_by' => $this->user->id,
                    'title' => 'Permit ' . ($i + 1),
                    'valid_from' => now(),
                    'valid_to' => now()->addDay(),
                    'status' => 'active',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\PpeAssignment::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'driver_id' => $driverIds[$i % count($driverIds)] ?? null,
                ],
                [
                    'ppe_type' => 'high_vis',
                    'item_reference' => 'HV-' . ($i + 1),
                    'issued_date' => now()->subMonth(),
                    'status' => 'active',
                ]
            );
        }

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\SafetyObservation::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'reported_by' => $this->user->id,
                    'title' => 'Observation ' . ($i + 1),
                ],
                [
                    'category' => 'near_miss',
                    'status' => 'closed',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\ToolboxTalk::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'topic' => 'Toolbox talk ' . ($i + 1),
                ],
                [
                    'scheduled_date' => now()->addDays($i * 7),
                    'status' => 'scheduled',
                ]
            );
        }
    }

    private function seedPhase10FinesLeaseWarranty(array $vehicleIds, array $driverIds): void
    {
        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\Fine::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'offence_date' => now()->subDays($i * 30)->toDateString(),
                ],
                [
                    'fine_type' => $i % 2 === 0 ? 'speeding' : 'parking',
                    'amount' => 100,
                    'status' => 'pending',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\VehicleLease::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                ],
                [
                    'lessor_name' => 'Lease Co ' . ($i + 1),
                    'start_date' => now()->subYear(),
                    'end_date' => now()->addYears(2),
                    'status' => 'active',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\VehicleRecall::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'recall_reference' => 'REC-' . (500 + $i),
                ],
                [
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'title' => 'Recall ' . ($i + 1),
                    'status' => 'completed',
                ]
            );
        }

        $woIds = \App\Models\Fleet\WorkOrder::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->where('organization_id', $this->org->id)->pluck('id')->take(4)->all();
        $partsInvIds = \App\Models\Fleet\PartsInventory::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->where('organization_id', $this->org->id)->pluck('id')->all();
        foreach ($woIds as $idx => $woId) {
            \App\Models\Fleet\WorkOrderLine::firstOrCreate(
                ['work_order_id' => $woId, 'line_type' => 'labour', 'sort_order' => 0],
                ['description' => 'Labour', 'quantity' => 1, 'unit_price' => 75, 'total' => 75]
            );
            if (count($partsInvIds) > 0) {
                \App\Models\Fleet\WorkOrderPart::firstOrCreate(
                    [
                        'work_order_id' => $woId,
                        'parts_inventory_id' => $partsInvIds[$idx % count($partsInvIds)],
                    ],
                    ['quantity_used' => 1, 'unit_cost' => 50, 'total_cost' => 50]
                );
            }
        }

        foreach ($woIds as $idx => $woId) {
            \App\Models\Fleet\WarrantyClaim::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'work_order_id' => $woId,
                    'claim_number' => 'WCL-' . (1000 + $idx),
                ],
                [
                    'status' => 'submitted',
                    'claim_amount' => 200,
                    'submitted_date' => now()->subDays($idx),
                ]
            );
        }
    }

    private function seedPhase11ExtrasAudit(array $vehicleIds, array $locIds): void
    {
        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\ParkingAllocation::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'location_id' => $locIds[$i % count($locIds)],
                    'allocated_from' => now()->subDays($i),
                ],
                [
                    'spot_identifier' => 'A-' . ($i + 1),
                ]
            );
        }

        for ($i = 0; $i < 5; $i++) {
            \App\Models\Fleet\ElockEvent::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'event_timestamp' => now()->subHours($i * 2),
                ],
                [
                    'event_type' => $i % 2 === 0 ? 'lock' : 'unlock',
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\AxleLoadReading::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'vehicle_id' => $vehicleIds[$i % count($vehicleIds)],
                    'recorded_at' => now()->subDays($i),
                ],
                [
                    'total_weight_kg' => 3000 + $i * 500,
                    'overload_flag' => false,
                ]
            );
        }

        for ($i = 0; $i < 4; $i++) {
            \App\Models\Fleet\DataMigrationRun::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'organization_id' => $this->org->id,
                    'batch_id' => 'BAT-' . (100 + $i),
                ],
                [
                    'migration_type' => 'import',
                    'status' => 'completed',
                    'started_at' => now()->subDays($i),
                    'completed_at' => now()->subDays($i),
                    'total_records' => 100,
                    'processed_records' => 100,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $rows
     * @return array<int>
     */
    private function seedRecords(string $modelClass, int $count, array $rows): array
    {
        $ids = [];
        $scope = \App\Models\Scopes\OrganizationScope::class;
        for ($i = 0; $i < min($count, count($rows)); $i++) {
            $attrs = $rows[$i];
            if (! isset($attrs['organization_id'])) {
                $attrs['organization_id'] = $this->org->id;
            }
            $model = $modelClass::withoutGlobalScope($scope)->firstOrCreate(
                $this->uniqueKeyFor($modelClass, $attrs),
                $attrs
            );
            $ids[] = $model->id;
        }
        return $ids;
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private function uniqueKeyFor(string $modelClass, array $attrs): array
    {
        $key = ['organization_id' => $this->org->id];
        if (isset($attrs['name'])) {
            $key['name'] = $attrs['name'];
        }
        if (isset($attrs['code'])) {
            $key['code'] = $attrs['code'];
        }
        if (isset($attrs['registration'])) {
            $key['registration'] = $attrs['registration'];
        }
        if (isset($attrs['work_order_number'])) {
            $key['work_order_number'] = $attrs['work_order_number'];
        }
        if (isset($attrs['card_number'])) {
            $key['card_number'] = $attrs['card_number'];
        }
        if (isset($attrs['device_id'])) {
            $key['device_id'] = $attrs['device_id'];
        }
        if (isset($attrs['slug'])) {
            $key['slug'] = $attrs['slug'];
        }
        if (isset($attrs['course_name'])) {
            $key['course_name'] = $attrs['course_name'];
        }
        if (isset($attrs['title'])) {
            $key['title'] = $attrs['title'];
        }
        return $key;
    }

    /**
     * Simulate live tracking from devices: set current_lat, current_lng, location_updated_at
     * for seeded vehicles and for any other vehicles in the DB that have no position yet,
     * so the Fleet dashboard map shows vehicle positions in every organization.
     */
    private function seedVehicleLiveTrackingPositions(array $vehicleIds): void
    {
        $positions = [
            ['lat' => 51.5074, 'lng' => -0.1278],
            ['lat' => 51.5150, 'lng' => -0.1420],
            ['lat' => 53.4808, 'lng' => -2.2426],
            ['lat' => 52.4862, 'lng' => -1.8904],
            ['lat' => 53.8008, 'lng' => -1.5491],
        ];
        foreach ($vehicleIds as $i => $vid) {
            $pos = $positions[$i % count($positions)];
            \App\Models\Fleet\Vehicle::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
                ->where('id', $vid)
                ->update([
                    'current_lat' => $pos['lat'],
                    'current_lng' => $pos['lng'],
                    'location_updated_at' => now()->subMinutes(rand(1, 60)),
                ]);
        }
        // Backfill all other vehicles (e.g. in other orgs like "Test Organization") that have no position
        $withoutPosition = \App\Models\Fleet\Vehicle::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->whereNull('current_lat')
            ->whereNull('current_lng')
            ->get(['id']);
        $updated = 0;
        foreach ($withoutPosition as $idx => $v) {
            $pos = $positions[$idx % count($positions)];
            \App\Models\Fleet\Vehicle::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
                ->where('id', $v->id)
                ->update([
                    'current_lat' => $pos['lat'],
                    'current_lng' => $pos['lng'],
                    'location_updated_at' => now()->subMinutes(rand(1, 60)),
                ]);
            $updated++;
        }
        $this->command?->info('Seeded simulated live tracking positions for ' . count($vehicleIds) . ' vehicles' . ($updated > 0 ? " and backfilled {$updated} more without position." : '.'));
    }

    /** Backfill lat/lng for existing locations (by name) so Route/Trip maps have coordinates. */
    private function backfillLocationCoordinates(): void
    {
        $updates = [
            'HQ Depot' => ['lat' => 51.5074, 'lng' => -0.1278],
            'North Yard' => ['lat' => 53.4808, 'lng' => -2.2426],
            'South Hub' => ['lat' => 52.4862, 'lng' => -1.8904],
            'East Office' => ['lat' => 53.8008, 'lng' => -1.5491],
            'West Depot' => ['lat' => 53.4084, 'lng' => -2.9916],
        ];
        foreach ($updates as $name => $coords) {
            \App\Models\Fleet\Location::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
                ->where('organization_id', $this->org->id)
                ->where('name', $name)
                ->update($coords);
        }
    }

    /** Seed route_stops for the London-Manchester route so the Route show page map has markers. */
    private function seedRouteStopsForMap(?int $routeId, array $locIds): void
    {
        if ($routeId === null || count($locIds) < 2) {
            return;
        }
        $stops = [
            ['location_id' => $locIds[0], 'name' => 'HQ Depot (Start)', 'sort_order' => 1],
            ['location_id' => $locIds[2], 'name' => 'South Hub', 'sort_order' => 2],
            ['location_id' => $locIds[1], 'name' => 'North Yard (End)', 'sort_order' => 3],
        ];
        foreach ($stops as $stop) {
            \App\Models\Fleet\RouteStop::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->firstOrCreate(
                [
                    'route_id' => $routeId,
                    'sort_order' => $stop['sort_order'],
                ],
                [
                    'location_id' => $stop['location_id'],
                    'name' => $stop['name'],
                ]
            );
        }
    }

    /** Seed trip_waypoints so the Trip show page map has a path. Prefer Trip #1 so /fleet/trips/1 always shows the path. */
    private function seedTripWaypointsForMap(array $locIds): void
    {
        $path = [
            [51.5074, -0.1278],
            [51.75, -0.45],
            [52.0, -0.9],
            [52.25, -1.35],
            [52.4862, -1.8904],
            [52.8, -2.0],
            [53.1, -2.12],
            [53.4808, -2.2426],
        ];

        // Prefer trip id=1 so /fleet/trips/1 always shows the path; otherwise first trip of this org
        $trip = \App\Models\Fleet\Trip::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->where('id', 1)
            ->first();
        if ($trip === null) {
            $trip = \App\Models\Fleet\Trip::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
                ->where('organization_id', $this->org->id)
                ->orderBy('id')
                ->first();
        }
        if ($trip === null) {
            return;
        }
        if (\App\Models\Fleet\TripWaypoint::where('trip_id', $trip->id)->exists()) {
            return;
        }
        $recorded = $trip->started_at ?? now()->subHours(2);
        foreach ($path as $seq => $point) {
            \App\Models\Fleet\TripWaypoint::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->create([
                'trip_id' => $trip->id,
                'lat' => $point[0],
                'lng' => $point[1],
                'sequence' => $seq + 1,
                'recorded_at' => $recorded->copy()->addMinutes($seq * 18),
            ]);
        }
    }
}
