import { Head, router } from '@inertiajs/react';
// eslint-disable-next-line @typescript-eslint/no-explicit-any
import { Puck, type Data } from '@measured/puck';
import '@measured/puck/puck.css';
import { ArrowLeft } from 'lucide-react';
import { useCallback } from 'react';

interface Template {
    id: number;
    name: string;
    type: string;
    puck_content: Data | null;
}

interface Props {
    template: Template;
}

// TextBlock component
const TextBlock = ({
    heading,
    body,
}: {
    heading?: string;
    body?: string;
}) => (
    <div className="p-8">
        {heading && <h2 className="mb-3 text-2xl font-semibold">{heading}</h2>}
        {body && <p className="text-gray-600">{body}</p>}
    </div>
);

// HeroSection component
const HeroSection = ({
    title,
    subtitle,
    background_color,
}: {
    title?: string;
    subtitle?: string;
    background_color?: string;
}) => (
    <div
        className="flex min-h-[300px] items-center justify-center p-12 text-white"
        style={{ backgroundColor: background_color ?? '#1e40af' }}
    >
        <div className="text-center">
            <h1 className="text-4xl font-bold">{title ?? 'Hero Title'}</h1>
            {subtitle && <p className="mt-3 text-xl opacity-80">{subtitle}</p>}
        </div>
    </div>
);

// FeatureGrid component
const FeatureGrid = ({
    heading,
    columns,
}: {
    heading?: string;
    columns?: number;
}) => (
    <div className="p-8">
        {heading && <h2 className="mb-6 text-center text-2xl font-semibold">{heading}</h2>}
        <div
            className="grid gap-4"
            style={{ gridTemplateColumns: `repeat(${columns ?? 3}, minmax(0, 1fr))` }}
        >
            {Array.from({ length: columns ?? 3 }).map((_, i) => (
                <div key={i} className="rounded-lg border p-4">
                    <div className="mb-3 h-8 w-8 rounded-full bg-primary/20" />
                    <p className="font-medium">Feature {i + 1}</p>
                    <p className="mt-1 text-sm text-gray-500">Feature description goes here.</p>
                </div>
            ))}
        </div>
    </div>
);

// CallToAction component
const CallToAction = ({
    heading,
    button_label,
    button_url,
}: {
    heading?: string;
    button_label?: string;
    button_url?: string;
}) => (
    <div className="bg-gray-50 p-12 text-center">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Ready to get started?'}</h2>
        <a
            href={button_url ?? '#'}
            className="inline-block rounded-lg bg-primary px-6 py-3 font-medium text-white hover:bg-primary/90"
        >
            {button_label ?? 'Get Started'}
        </a>
    </div>
);

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const puckConfig: any = {
    components: {
        HeroSection: {
            label: 'Hero Section',
            fields: {
                title: { type: 'text', label: 'Title' },
                subtitle: { type: 'text', label: 'Subtitle' },
                background_color: { type: 'text', label: 'Background Colour' },
            },
            defaultProps: {
                title: 'Page Title',
                subtitle: 'A short description goes here.',
                background_color: '#1e40af',
            },
            render: HeroSection,
        },
        TextBlock: {
            label: 'Text Block',
            fields: {
                heading: { type: 'text', label: 'Heading' },
                body: { type: 'textarea', label: 'Body' },
            },
            defaultProps: {
                heading: 'Section Heading',
                body: 'Add your content here.',
            },
            render: TextBlock,
        },
        FeatureGrid: {
            label: 'Feature Grid',
            fields: {
                heading: { type: 'text', label: 'Heading' },
                columns: { type: 'number', label: 'Columns', min: 1, max: 4 },
            },
            defaultProps: {
                heading: 'Our Features',
                columns: 3,
            },
            render: FeatureGrid,
        },
        CallToAction: {
            label: 'Call to Action',
            fields: {
                heading: { type: 'text', label: 'Heading' },
                button_label: { type: 'text', label: 'Button Label' },
                button_url: { type: 'text', label: 'Button URL' },
            },
            defaultProps: {
                heading: 'Ready to get started?',
                button_label: 'Get Started',
                button_url: '#',
            },
            render: CallToAction,
        },
    },
};

const emptyData: Data = {
    content: [],
    root: { props: {} },
    zones: {},
};

export default function PuckTemplateEditPage({ template }: Props) {
    const initialData = template.puck_content ?? emptyData;

    const handlePublish = useCallback(
        (data: Data) => {
            router.post(
                `/puck-templates/${template.id}/edit`,
                { puck_content: data },
                { preserveScroll: true },
            );
        },
        [template.id],
    );

    return (
        <>
            <Head title={`Edit Template: ${template.name}`} />
            <div className="flex h-screen flex-col">
                {/* Top bar */}
                <div className="flex items-center justify-between border-b bg-white px-4 py-3">
                    <div className="flex items-center gap-3">
                        <a
                            href="/puck-templates"
                            className="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back
                        </a>
                        <span className="text-gray-300">/</span>
                        <span className="text-sm font-medium">{template.name}</span>
                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs capitalize text-gray-600">
                            {template.type}
                        </span>
                    </div>
                </div>

                {/* Puck editor */}
                <div className="flex-1 overflow-hidden">
                    <Puck
                        config={puckConfig}
                        data={initialData}
                        onPublish={handlePublish}
                    />
                </div>
            </div>
        </>
    );
}
