<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $project->title }} — Flyer</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            background: #fff;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 16mm;
            position: relative;
        }
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #f28036;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header .brand { font-size: 22pt; font-weight: 800; color: #f28036; }
        .header .tagline { font-size: 8pt; color: #666; text-align: right; }
        /* Project title */
        .project-title {
            font-size: 22pt;
            font-weight: 700;
            color: #222;
            margin-bottom: 4px;
        }
        .project-subtitle {
            font-size: 11pt;
            color: #666;
            margin-bottom: 16px;
        }
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f9f8f7;
            border: 1px solid #dfe3e7;
            border-radius: 6px;
            padding: 10px 12px;
        }
        .stat-label { font-size: 7.5pt; color: #727e8c; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 14pt; font-weight: 700; color: #222; }
        /* Description */
        .section-title {
            font-size: 10pt;
            font-weight: 700;
            color: #475f7b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-left: 3px solid #f28036;
            padding-left: 8px;
        }
        .description {
            font-size: 9.5pt;
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            margin-bottom: 20px;
        }
        .feature-item {
            font-size: 9pt;
            color: #333;
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-label { color: #727e8c; font-size: 8pt; }
        /* Lot table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }
        table th {
            background: #f28036;
            color: #fff;
            font-weight: 600;
            padding: 6px 8px;
            text-align: left;
            font-size: 8pt;
        }
        table td {
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        table tr:nth-child(even) td { background: #fafafa; }
        /* Notes */
        .notes {
            font-size: 9pt;
            color: #555;
            font-style: italic;
            margin-bottom: 20px;
        }
        /* Footer */
        .footer {
            position: absolute;
            bottom: 16mm;
            left: 16mm;
            right: 16mm;
            border-top: 1px solid #dfe3e7;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-text { font-size: 7.5pt; color: #999; }
        .disclaimer {
            font-size: 7pt;
            color: #bbb;
            margin-top: 6px;
            font-style: italic;
        }
        /* Hot badge */
        .badge {
            display: inline-block;
            background: #f28036;
            color: #fff;
            font-size: 7.5pt;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 8px;
            vertical-align: middle;
        }
        /* Custom HTML override */
        .custom-content {
            margin-bottom: 20px;
        }
    </style>
    @if ($flyer->is_custom && $flyer->custom_css)
    <style>
        {{ $flyer->custom_css }}
    </style>
    @endif
    @if ($template && $template->css_content)
    <style>
        {{ $template->css_content }}
    </style>
    @endif
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="brand">Fusion CRM</div>
        <div class="tagline">Property Investment Platform<br>{{ now()->format('d M Y') }}</div>
    </div>

    @if ($flyer->is_custom && $flyer->custom_html)
        {{-- Custom HTML content overrides the default template --}}
        <div class="custom-content">
            {!! $flyer->custom_html !!}
        </div>
    @elseif ($template && $template->html_content)
        {{-- Template HTML with token replacement --}}
        <div class="custom-content">
            {!! str_replace(
                ['{project_title}', '{suburb}', '{state}', '{developer}', '{min_price}', '{max_price}', '{description}'],
                [$project->title, $project->suburb ?? '', $project->state ?? '', $developer_name, $min_price_formatted, $max_price_formatted, strip_tags($project->description ?? '')],
                $template->html_content
            ) !!}
        </div>
    @else
        {{-- Default Fusion flyer layout --}}

        {{-- Project title --}}
        <div>
            <div class="project-title">
                {{ $project->title }}
                @if ($project->is_hot_property)
                    <span class="badge">🔥 Hot Property</span>
                @endif
                @if ($project->is_featured)
                    <span class="badge" style="background:#475f7b;">Featured</span>
                @endif
            </div>
            <div class="project-subtitle">
                {{ implode(', ', array_filter([$project->suburb, $project->state, $project->postcode])) }}
                @if ($developer_name)
                    — Developed by {{ $developer_name }}
                @endif
            </div>
        </div>

        {{-- Stats grid --}}
        <div class="stats-grid">
            @if ($project->min_price || $project->max_price)
            <div class="stat-box">
                <div class="stat-label">Price Range</div>
                <div class="stat-value">{{ $min_price_formatted }}
                    @if ($project->max_price)
                        – {{ $max_price_formatted }}
                    @endif
                </div>
            </div>
            @endif

            @if ($project->total_lots)
            <div class="stat-box">
                <div class="stat-label">Total Lots</div>
                <div class="stat-value">{{ $project->total_lots }}</div>
            </div>
            @endif

            @if ($project->min_rent || $project->avg_rent)
            <div class="stat-box">
                <div class="stat-label">Est. Weekly Rent</div>
                <div class="stat-value">${{ number_format((float)($project->avg_rent ?? $project->min_rent), 0) }}</div>
            </div>
            @endif

            @if ($project->rent_yield)
            <div class="stat-box">
                <div class="stat-label">Rental Yield</div>
                <div class="stat-value">{{ $project->rent_yield }}%</div>
            </div>
            @endif

            @if ($project->bedrooms)
            <div class="stat-box">
                <div class="stat-label">Bedrooms</div>
                <div class="stat-value">{{ $project->bedrooms }}</div>
            </div>
            @endif

            @if ($project->stage)
            <div class="stat-box">
                <div class="stat-label">Stage</div>
                <div class="stat-value" style="font-size:11pt;">{{ ucfirst(str_replace('_', ' ', $project->stage)) }}</div>
            </div>
            @endif
        </div>

        {{-- Description --}}
        @if ($project->description_summary || $project->description)
        <div class="section-title">About This Property</div>
        <div class="description">
            {{ $project->description_summary ?: Str::limit(strip_tags($project->description ?? ''), 400) }}
        </div>
        @endif

        {{-- Investment flags --}}
        @php
        $flags = [];
        if ($project->is_smsf) $flags[] = 'SMSF Eligible';
        if ($project->is_firb) $flags[] = 'FIRB Approved';
        if ($project->is_ndis) $flags[] = 'NDIS Eligible';
        if ($project->is_cashflow_positive) $flags[] = 'Cash Flow Positive';
        if ($project->is_co_living) $flags[] = 'Co-Living';
        if ($project->is_high_cap_growth) $flags[] = 'High Capital Growth';
        if ($project->is_rooming) $flags[] = 'Rooming House';
        if ($project->is_rent_to_sell) $flags[] = 'Rent to Sell';
        if ($project->is_exclusive) $flags[] = 'Exclusive';
        @endphp
        @if (count($flags))
        <div class="section-title">Investment Features</div>
        <div class="features-grid">
            @foreach ($flags as $flag)
            <div class="feature-item">✓ {{ $flag }}</div>
            @endforeach
        </div>
        @endif

        {{-- Lot details (if single lot) --}}
        @if ($lot)
        <div class="section-title">Lot Details</div>
        <div class="stats-grid">
            @if ($lot->price)
            <div class="stat-box">
                <div class="stat-label">Price</div>
                <div class="stat-value">${{ number_format((float)$lot->price, 0) }}</div>
            </div>
            @endif
            @if ($lot->land_size)
            <div class="stat-box">
                <div class="stat-label">Land Size</div>
                <div class="stat-value">{{ $lot->land_size }}m²</div>
            </div>
            @endif
            @if ($lot->bedrooms)
            <div class="stat-box">
                <div class="stat-label">Bedrooms</div>
                <div class="stat-value">{{ $lot->bedrooms }}</div>
            </div>
            @endif
            @if ($lot->bathrooms)
            <div class="stat-box">
                <div class="stat-label">Bathrooms</div>
                <div class="stat-value">{{ $lot->bathrooms }}</div>
            </div>
            @endif
            @if ($lot->garage)
            <div class="stat-box">
                <div class="stat-label">Garage</div>
                <div class="stat-value">{{ $lot->garage }}</div>
            </div>
            @endif
            @if ($lot->title_status)
            <div class="stat-box">
                <div class="stat-label">Status</div>
                <div class="stat-value" style="font-size:11pt;">{{ ucfirst($lot->title_status) }}</div>
            </div>
            @endif
        </div>
        @endif

        {{-- Notes --}}
        @if ($flyer->notes)
        <div class="notes">{{ $flyer->notes }}</div>
        @endif

    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-text">
            Fusion CRM &mdash; Property Investment Platform<br>
            Generated {{ now()->format('d M Y, g:ia') }}
        </div>
        <div class="footer-text" style="text-align:right;">
            {{ $project->title }}<br>
            @if ($developer_name){{ $developer_name }}@endif
        </div>
    </div>
    <div class="disclaimer" style="position:absolute;bottom:8mm;left:16mm;right:16mm;">
        This document is for informational purposes only. Prices, availability, and features are subject to change without notice. Please verify all details independently before making any investment decisions.
    </div>

</div>
</body>
</html>
