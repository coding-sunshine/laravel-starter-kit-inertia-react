<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #fff; color: #333; }
.page { width: 297mm; min-height: 210mm; display: flex; flex-direction: column; padding: 20mm; }
.header { border-bottom: 4px solid #f28036; padding-bottom: 8mm; margin-bottom: 8mm; }
.logo-bar { display: flex; justify-content: space-between; align-items: center; }
.tagline { font-size: 22pt; font-weight: 700; color: #f28036; margin-top: 4mm; }
.body { flex: 1; display: grid; grid-template-columns: 2fr 1fr; gap: 8mm; }
.description { font-size: 11pt; line-height: 1.6; color: #444; }
.features { background: #f3f1ee; border-radius: 4mm; padding: 6mm; }
.features h3 { font-size: 10pt; font-weight: 700; color: #f28036; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3mm; }
.features ul { list-style: none; }
.features li { font-size: 10pt; padding: 1mm 0; border-bottom: 1px solid #DFE3E7; display: flex; align-items: center; gap: 2mm; }
.features li::before { content: '✓'; color: #f28036; font-weight: 700; }
.footer { margin-top: 8mm; padding-top: 4mm; border-top: 2px solid #DFE3E7; display: flex; justify-content: space-between; align-items: center; }
.cta { background: #f28036; color: white; padding: 3mm 6mm; border-radius: 2mm; font-weight: 700; font-size: 11pt; }
.lot-info { font-size: 10pt; color: #727E8C; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="logo-bar">
            <span style="font-size: 14pt; font-weight: 700; color: #475F7B;">Fusion CRM</span>
            @if($lot)
                <span class="lot-info">Lot {{ $lot->lot_number }} · ${{ number_format($lot->price ?? 0) }}</span>
            @endif
        </div>
        <div class="tagline">{{ $aiContent['tagline'] ?? ($project?->name ?? 'Property Brochure') }}</div>
    </div>

    <div class="body">
        <div>
            <p class="description">{{ $aiContent['description'] ?? $flyer->notes ?? 'Discover this exceptional property opportunity.' }}</p>

            @if($project)
                <div style="margin-top: 5mm; background: #fff3e0; border-left: 3px solid #f28036; padding: 3mm 4mm; border-radius: 1mm;">
                    <strong style="font-size: 10pt;">{{ $project->name }}</strong>
                </div>
            @endif
        </div>

        <div class="features">
            <h3>Key Features</h3>
            <ul>
                @foreach($aiContent['key_features'] ?? ['Premium location', 'Modern finishes', 'Quality construction', 'Strong investment'] as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="footer">
        <div class="lot-info">
            {{ $lot?->bedrooms ?? 0 }} bed · {{ $lot?->bathrooms ?? 0 }} bath · {{ $lot?->car_spaces ?? 0 }} car
        </div>
        <div class="cta">{{ $aiContent['call_to_action'] ?? 'Enquire Now' }}</div>
    </div>
</div>
</body>
</html>
