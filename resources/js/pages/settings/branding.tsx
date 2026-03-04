import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { ExplainSettingLink } from '@/components/explain-setting-link';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import {
    edit as editBranding,
    update as updateBranding,
} from '@/routes/settings/branding';
import { type BreadcrumbItem } from '@/types';

interface BrandingProps {
    logoUrl: string | null;
    themePreset: string | null;
    themeRadius: string | null;
    themeFont: string | null;
    allowUserCustomization: boolean;
}

interface Props {
    branding: BrandingProps;
    presetOptions: Record<string, string>;
    radiusOptions: Record<string, string>;
    fontOptions: Record<string, string>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organization branding', href: editBranding().url },
];

export default function Branding({
    branding,
    presetOptions,
    radiusOptions,
    fontOptions,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        logo: null as File | null,
        theme_preset: branding.themePreset ?? '',
        theme_radius: branding.themeRadius ?? '',
        theme_font: branding.themeFont ?? '',
        allow_user_ui_customization: branding.allowUserCustomization,
    });

    const [suggestLoading, setSuggestLoading] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(updateBranding().url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const handleSuggestFromLogo = async () => {
        setSuggestLoading(true);
        try {
            const formData = new FormData();
            if (data.logo) formData.append('logo', data.logo);
            const res = await fetch('/settings/branding/suggest', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector<HTMLMetaElement>(
                            'meta[name="csrf-token"]',
                        )?.content ?? '',
                    Accept: 'application/json',
                },
            });
            const json = await res.json();
            if (json.theme_preset != null)
                setData('theme_preset', json.theme_preset);
            if (json.theme_radius != null)
                setData('theme_radius', json.theme_radius);
            if (json.theme_font != null) setData('theme_font', json.theme_font);
        } finally {
            setSuggestLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization branding" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <HeadingSmall
                            title="Organization branding"
                            description="Logo and theme overrides for your organization"
                        />
                        <ExplainSettingLink
                            settingName="Organization branding"
                            context="Logo, theme preset, radius, and font for your organization."
                        />
                    </div>

                    <form
                        onSubmit={handleSubmit}
                        encType="multipart/form-data"
                        className="space-y-6"
                    >
                        <div className="space-y-2">
                            <Label htmlFor="logo">Logo</Label>
                            {branding.logoUrl && (
                                <div className="mb-2 flex items-center gap-4">
                                    <img
                                        src={branding.logoUrl}
                                        alt="Current logo"
                                        className="h-16 w-auto object-contain"
                                    />
                                </div>
                            )}
                            <input
                                id="logo"
                                name="logo"
                                type="file"
                                accept="image/*"
                                className="block w-full max-w-xs text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                                onChange={(e) =>
                                    setData('logo', e.target.files?.[0] ?? null)
                                }
                            />
                            <p className="text-xs text-muted-foreground">
                                Image file. Max 2 MB. Leave empty to keep
                                current.
                            </p>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={handleSuggestFromLogo}
                                disabled={suggestLoading}
                                className="mt-2"
                            >
                                {suggestLoading
                                    ? 'Suggesting…'
                                    : 'Suggest branding from logo'}
                            </Button>
                            <InputError message={errors.logo} />
                        </div>

                        <div className="space-y-2">
                            <Label>Theme preset</Label>
                            <Select
                                value={data.theme_preset || undefined}
                                onValueChange={(v) =>
                                    setData('theme_preset', v ?? '')
                                }
                            >
                                <SelectTrigger className="w-full max-w-xs">
                                    <SelectValue placeholder="Use app default" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(presetOptions).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.theme_preset} />
                        </div>

                        <div className="space-y-2">
                            <Label>Radius</Label>
                            <Select
                                value={data.theme_radius || undefined}
                                onValueChange={(v) =>
                                    setData('theme_radius', v ?? '')
                                }
                            >
                                <SelectTrigger className="w-full max-w-xs">
                                    <SelectValue placeholder="Use app default" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(radiusOptions).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.theme_radius} />
                        </div>

                        <div className="space-y-2">
                            <Label>Font</Label>
                            <Select
                                value={data.theme_font || undefined}
                                onValueChange={(v) =>
                                    setData('theme_font', v ?? '')
                                }
                            >
                                <SelectTrigger className="w-full max-w-xs">
                                    <SelectValue placeholder="Use app default" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(fontOptions).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.theme_font} />
                        </div>

                        <div className="flex items-center space-x-2">
                            <Switch
                                id="allow_user_ui_customization"
                                checked={data.allow_user_ui_customization}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'allow_user_ui_customization',
                                        checked,
                                    )
                                }
                            />
                            <Label
                                htmlFor="allow_user_ui_customization"
                                className="font-normal"
                            >
                                Allow members to change appearance (light/dark)
                            </Label>
                        </div>
                        <InputError
                            message={errors.allow_user_ui_customization}
                        />

                        <div className="space-y-2">
                            <Label>Live preview</Label>
                            <div
                                className="flex flex-wrap items-start gap-4 rounded-lg border border-border bg-muted/30 p-4"
                                aria-live="polite"
                                aria-label="Branding preview"
                            >
                                {branding.logoUrl && (
                                    <div className="flex flex-col gap-1">
                                        <span className="text-xs text-muted-foreground">
                                            Logo
                                        </span>
                                        <img
                                            src={branding.logoUrl}
                                            alt=""
                                            className="h-12 w-auto object-contain"
                                            role="presentation"
                                        />
                                    </div>
                                )}
                                <div className="flex flex-col gap-1">
                                    <span className="text-xs text-muted-foreground">
                                        Sample card
                                    </span>
                                    <div
                                        className="border border-border bg-card px-4 py-3 shadow-sm"
                                        style={{
                                            borderRadius:
                                                data.theme_radius === 'sm'
                                                    ? '4px'
                                                    : data.theme_radius === 'lg'
                                                      ? '8px'
                                                      : '6px',
                                        }}
                                    >
                                        <p
                                            className="text-sm font-medium text-foreground"
                                            style={{
                                                fontFamily:
                                                    data.theme_font ===
                                                    'instrument-sans'
                                                        ? 'Instrument Sans, sans-serif'
                                                        : data.theme_font ===
                                                            'geist'
                                                          ? 'Geist, sans-serif'
                                                          : undefined,
                                            }}
                                        >
                                            {presetOptions[data.theme_preset] ??
                                                'Theme'}{' '}
                                            ·{' '}
                                            {radiusOptions[data.theme_radius] ??
                                                'Radius'}{' '}
                                            ·{' '}
                                            {fontOptions[data.theme_font] ??
                                                'Font'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving…' : 'Save branding'}
                        </Button>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
