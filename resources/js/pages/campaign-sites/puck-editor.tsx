import { Head, router } from '@inertiajs/react';
// eslint-disable-next-line @typescript-eslint/no-explicit-any
import { Puck, type Data } from '@measured/puck';
import '@measured/puck/puck.css';
import { ArrowLeft } from 'lucide-react';
import { useCallback } from 'react';

interface Campaign {
    id: number;
    title: string;
    site_id: string;
    puck_content: Data | null;
    puck_enabled: boolean;
}

interface Props {
    campaign: Campaign;
}

// ProjectHero component - renders a project hero section
const ProjectHero = ({
    projectId,
    title,
    tagline,
}: {
    projectId?: number;
    title?: string;
    tagline?: string;
}) => (
    <div className="relative flex min-h-[400px] items-center justify-center bg-gray-900 p-8 text-white">
        <div className="text-center">
            <h1 className="text-4xl font-bold">{title ?? 'Project Title'}</h1>
            {tagline && <p className="mt-2 text-xl text-gray-300">{tagline}</p>}
            {projectId && (
                <p className="mt-1 text-xs text-gray-500">Project #{projectId}</p>
            )}
        </div>
    </div>
);

// LotGrid component - renders available lots
const LotGrid = ({
    projectId,
    heading,
}: {
    projectId?: number;
    heading?: string;
}) => (
    <div className="p-8">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Available Lots'}</h2>
        <div className="grid grid-cols-3 gap-4">
            {[1, 2, 3].map((i) => (
                <div key={i} className="rounded-lg border p-4">
                    <div className="mb-2 h-32 rounded bg-gray-100" />
                    <p className="font-medium">Lot {i}</p>
                    <p className="text-sm text-gray-500">3 bed · 2 bath · From $450,000</p>
                </div>
            ))}
        </div>
        {projectId && (
            <p className="mt-2 text-xs text-gray-400">Project #{projectId}</p>
        )}
    </div>
);

// EnquiryForm component
const EnquiryForm = ({
    heading,
    buttonLabel,
}: {
    heading?: string;
    buttonLabel?: string;
}) => (
    <div className="bg-gray-50 p-8">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Get in Touch'}</h2>
        <form className="max-w-md space-y-4" onSubmit={(e) => e.preventDefault()}>
            <input className="w-full rounded border p-2" placeholder="Full Name" />
            <input className="w-full rounded border p-2" placeholder="Email" type="email" />
            <input className="w-full rounded border p-2" placeholder="Phone" type="tel" />
            <textarea className="w-full rounded border p-2" placeholder="Message" rows={4} />
            <button className="rounded bg-primary px-6 py-2 text-white">
                {buttonLabel ?? 'Send Enquiry'}
            </button>
        </form>
    </div>
);

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

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const puckConfig: any = {
    components: {
        ProjectHero: {
            label: 'Project Hero',
            fields: {
                projectId: { type: 'number', label: 'Project ID' },
                title: { type: 'text', label: 'Title' },
                tagline: { type: 'text', label: 'Tagline' },
            },
            defaultProps: {
                title: 'Your Project',
                tagline: 'Discover your dream property',
            },
            render: ProjectHero,
        },
        LotGrid: {
            label: 'Lot Grid',
            fields: {
                projectId: { type: 'number', label: 'Project ID' },
                heading: { type: 'text', label: 'Heading' },
            },
            defaultProps: {
                heading: 'Available Lots',
            },
            render: LotGrid,
        },
        EnquiryForm: {
            label: 'Enquiry Form',
            fields: {
                heading: { type: 'text', label: 'Heading' },
                buttonLabel: { type: 'text', label: 'Button Label' },
            },
            defaultProps: {
                heading: 'Get in Touch',
                buttonLabel: 'Send Enquiry',
            },
            render: EnquiryForm,
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
    },
};

const emptyData: Data = {
    content: [],
    root: { props: {} },
    zones: {},
};

export default function PuckEditorPage({ campaign }: Props) {
    const initialData = campaign.puck_content ?? emptyData;

    const handlePublish = useCallback(
        (data: Data) => {
            router.post(
                `/campaign-sites/${campaign.id}/puck-save`,
                { puck_content: data, publish: true },
                { preserveScroll: true },
            );
        },
        [campaign.id],
    );

    return (
        <>
            <Head title={`Edit: ${campaign.title}`} />
            <div className="flex h-screen flex-col">
                {/* Top bar */}
                <div className="flex items-center justify-between border-b bg-white px-4 py-3">
                    <div className="flex items-center gap-3">
                        <a
                            href="/campaign-sites"
                            className="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back
                        </a>
                        <span className="text-sm font-medium">{campaign.title}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <a
                            href={`/w/${campaign.site_id}`}
                            target="_blank"
                            rel="noreferrer"
                            className="rounded border px-3 py-1.5 text-sm hover:bg-gray-50"
                        >
                            Preview
                        </a>
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
