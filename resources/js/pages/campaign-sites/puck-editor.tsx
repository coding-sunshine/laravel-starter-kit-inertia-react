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

// --- CRM Data Components ---

const ProjectHero = ({ projectId, title, tagline }: { projectId?: number; title?: string; tagline?: string }) => (
    <div className="relative flex min-h-[400px] items-center justify-center bg-gray-900 p-8 text-white">
        <div className="text-center">
            <h1 className="text-4xl font-bold">{title ?? 'Project Title'}</h1>
            {tagline && <p className="mt-2 text-xl text-gray-300">{tagline}</p>}
            {projectId && <p className="mt-1 text-xs text-gray-500">Project #{projectId}</p>}
        </div>
    </div>
);

const LotGrid = ({ projectId, heading }: { projectId?: number; heading?: string }) => (
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
        {projectId && <p className="mt-2 text-xs text-gray-400">Project #{projectId}</p>}
    </div>
);

const LotCard = ({ lotId, price, status }: { lotId?: number; price?: string; status?: string }) => (
    <div className="rounded-lg border p-6">
        <div className="mb-4 h-48 rounded bg-gray-100" />
        <div className="flex items-center justify-between">
            <p className="text-lg font-bold">{price ?? '$480,000'}</p>
            <span className="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">{status ?? 'Available'}</span>
        </div>
        <p className="mt-1 text-xs text-gray-400">Lot #{lotId ?? '—'}</p>
    </div>
);

const AgentProfile = ({
    agentId,
    name,
    phone,
    ctaLabel,
}: {
    agentId?: number;
    name?: string;
    phone?: string;
    ctaLabel?: string;
}) => (
    <div className="flex items-center gap-6 rounded-xl bg-gray-50 p-6">
        <div className="h-20 w-20 flex-shrink-0 rounded-full bg-gray-200" />
        <div>
            <p className="text-lg font-semibold">{name ?? 'Agent Name'}</p>
            <p className="text-sm text-gray-500">{phone ?? '0400 000 000'}</p>
            {agentId && <p className="text-xs text-gray-400">Agent #{agentId}</p>}
            <button className="mt-3 rounded bg-orange-500 px-4 py-1.5 text-sm text-white">{ctaLabel ?? 'Contact Agent'}</button>
        </div>
    </div>
);

const EnquiryForm = ({ heading, buttonLabel }: { heading?: string; buttonLabel?: string }) => (
    <div className="bg-gray-50 p-8">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Get in Touch'}</h2>
        <form className="max-w-md space-y-4" onSubmit={(e) => e.preventDefault()}>
            <input className="w-full rounded border p-2" placeholder="Full Name" />
            <input className="w-full rounded border p-2" placeholder="Email" type="email" />
            <input className="w-full rounded border p-2" placeholder="Phone" type="tel" />
            <textarea className="w-full rounded border p-2" placeholder="Message" rows={4} />
            <button className="rounded bg-orange-500 px-6 py-2 text-white">{buttonLabel ?? 'Send Enquiry'}</button>
        </form>
    </div>
);

const ProjectGallery = ({ projectId, heading }: { projectId?: number; heading?: string }) => (
    <div className="p-8">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Gallery'}</h2>
        <div className="grid grid-cols-3 gap-2">
            {[1, 2, 3, 4, 5, 6].map((i) => (
                <div key={i} className="h-40 rounded bg-gray-200" />
            ))}
        </div>
        {projectId && <p className="mt-2 text-xs text-gray-400">Project #{projectId}</p>}
    </div>
);

const FloorPlanViewer = ({ lotId, label }: { lotId?: number; label?: string }) => (
    <div className="p-8">
        <h2 className="mb-4 text-xl font-semibold">{label ?? 'Floor Plan'}</h2>
        <div className="flex h-64 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50">
            <p className="text-sm text-gray-400">Floor plan viewer{lotId ? ` — Lot #${lotId}` : ''}</p>
        </div>
    </div>
);

const PriceList = ({ projectId, heading }: { projectId?: number; heading?: string }) => (
    <div className="p-8">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Price List'}</h2>
        <table className="w-full text-sm">
            <thead>
                <tr className="border-b bg-gray-50">
                    <th className="p-3 text-left">Lot</th>
                    <th className="p-3 text-left">Beds</th>
                    <th className="p-3 text-left">Size</th>
                    <th className="p-3 text-left">Price</th>
                    <th className="p-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                {[1, 2, 3].map((i) => (
                    <tr key={i} className="border-b">
                        <td className="p-3">Lot {i}</td>
                        <td className="p-3">3</td>
                        <td className="p-3">450m²</td>
                        <td className="p-3">$480,000</td>
                        <td className="p-3">
                            <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">Available</span>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
        {projectId && <p className="mt-2 text-xs text-gray-400">Project #{projectId}</p>}
    </div>
);

const KeyFeatures = ({ features }: { features?: string }) => {
    const items = (features ?? 'City Views\nNBN Connected\nSMSF Eligible').split('\n').filter(Boolean);
    return (
        <div className="p-8">
            <h2 className="mb-4 text-2xl font-semibold">Key Features</h2>
            <ul className="grid grid-cols-2 gap-3">
                {items.map((f, i) => (
                    <li key={i} className="flex items-center gap-2 text-sm">
                        <span className="text-orange-500">✓</span> {f}
                    </li>
                ))}
            </ul>
        </div>
    );
};

const TextBlock = ({ heading, body }: { heading?: string; body?: string }) => (
    <div className="p-8">
        {heading && <h2 className="mb-3 text-2xl font-semibold">{heading}</h2>}
        {body && <p className="text-gray-600">{body}</p>}
    </div>
);

const ImageBlock = ({ alt, caption }: { alt?: string; caption?: string }) => (
    <div className="p-4">
        <div className="flex h-64 items-center justify-center rounded-lg bg-gray-200">
            <span className="text-sm text-gray-400">{alt ?? 'Image'}</span>
        </div>
        {caption && <p className="mt-2 text-center text-sm text-gray-500">{caption}</p>}
    </div>
);

const VideoEmbed = ({ url, caption }: { url?: string; caption?: string }) => (
    <div className="p-8">
        <div className="aspect-video w-full overflow-hidden rounded-lg bg-gray-900">
            {url ? (
                <iframe src={url} className="h-full w-full" allowFullScreen title={caption ?? 'Video'} />
            ) : (
                <div className="flex h-full items-center justify-center text-gray-400">Video embed — paste URL in inspector</div>
            )}
        </div>
        {caption && <p className="mt-2 text-center text-sm text-gray-500">{caption}</p>}
    </div>
);

const CallToAction = ({ heading, buttonLabel, buttonUrl }: { heading?: string; buttonLabel?: string; buttonUrl?: string }) => (
    <div className="bg-orange-50 p-8 text-center">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Ready to Get Started?'}</h2>
        <a href={buttonUrl ?? '#'} className="inline-block rounded bg-orange-500 px-8 py-3 font-medium text-white hover:bg-orange-600">
            {buttonLabel ?? 'Contact Us'}
        </a>
    </div>
);

const SurveyBlock = ({ heading }: { heading?: string }) => (
    <div className="bg-blue-50 p-8">
        <h2 className="mb-4 text-2xl font-semibold">{heading ?? 'Quick Survey'}</h2>
        <form className="max-w-md space-y-4" onSubmit={(e) => e.preventDefault()}>
            <input className="w-full rounded border p-2" placeholder="Your Name" />
            <input className="w-full rounded border p-2" placeholder="Email" type="email" />
            <select className="w-full rounded border p-2">
                <option>How did you hear about us?</option>
                <option>Google</option>
                <option>Social Media</option>
                <option>Referral</option>
            </select>
            <button className="rounded bg-blue-600 px-6 py-2 text-white">Submit</button>
        </form>
    </div>
);

// --- Flyer Components (for fixed-canvas flyer editing) ---

const FlyerHero = ({ title, subtitle }: { title?: string; subtitle?: string }) => (
    <div className="relative flex h-64 items-center justify-center bg-gray-800 text-white">
        <div className="text-center">
            <h1 className="text-3xl font-bold">{title ?? 'Property Title'}</h1>
            {subtitle && <p className="mt-1 text-gray-300">{subtitle}</p>}
        </div>
    </div>
);

const FlyerLotSpecs = ({
    beds,
    baths,
    cars,
    area,
    price,
}: {
    beds?: string;
    baths?: string;
    cars?: string;
    area?: string;
    price?: string;
}) => (
    <div className="grid grid-cols-5 gap-2 p-4 text-center">
        {[
            { label: 'Beds', val: beds ?? '3' },
            { label: 'Baths', val: baths ?? '2' },
            { label: 'Cars', val: cars ?? '2' },
            { label: 'Area', val: area ?? '450m²' },
            { label: 'Price', val: price ?? '$480,000' },
        ].map((s) => (
            <div key={s.label} className="rounded border p-2">
                <p className="text-lg font-bold">{s.val}</p>
                <p className="text-xs text-gray-500">{s.label}</p>
            </div>
        ))}
    </div>
);

const FlyerAgentFooter = ({ agentName, agentPhone, disclaimer }: { agentName?: string; agentPhone?: string; disclaimer?: string }) => (
    <div className="border-t bg-gray-50 p-4">
        <div className="flex items-center justify-between">
            <div>
                <p className="font-semibold">{agentName ?? 'Agent Name'}</p>
                <p className="text-sm text-gray-500">{agentPhone ?? '0400 000 000'}</p>
            </div>
            <div className="h-10 w-20 rounded bg-gray-200" />
        </div>
        {disclaimer && <p className="mt-2 text-xs text-gray-400">{disclaimer}</p>}
    </div>
);

const FlyerTextBlock = ({ headline, body }: { headline?: string; body?: string }) => (
    <div className="p-4">
        {headline && <h3 className="mb-1 text-xl font-bold">{headline}</h3>}
        {body && <p className="text-sm text-gray-600">{body}</p>}
    </div>
);

const FlyerQRCode = ({ url, label }: { url?: string; label?: string }) => (
    <div className="flex flex-col items-center p-4">
        <div className="flex h-24 w-24 items-center justify-center rounded border bg-white">
            <span className="text-xs text-gray-400">QR</span>
        </div>
        {label && <p className="mt-1 text-xs text-gray-500">{label}</p>}
        {url && <p className="text-xs text-gray-400">{url}</p>}
    </div>
);

// --- Puck Config ---

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const puckConfig: any = {
    categories: {
        'CRM Data': {
            components: ['ProjectHero', 'LotGrid', 'LotCard', 'AgentProfile', 'ProjectGallery', 'FloorPlanViewer', 'PriceList'],
        },
        Forms: {
            components: ['EnquiryForm', 'SurveyBlock'],
        },
        Content: {
            components: ['KeyFeatures', 'TextBlock', 'ImageBlock', 'VideoEmbed', 'CallToAction'],
        },
        'Flyer Components': {
            components: ['FlyerHero', 'FlyerLotSpecs', 'FlyerAgentFooter', 'FlyerTextBlock', 'FlyerQRCode'],
        },
    },
    components: {
        ProjectHero: {
            label: 'Project Hero',
            fields: {
                projectId: { type: 'number', label: 'Project ID' },
                title: { type: 'text', label: 'Title' },
                tagline: { type: 'text', label: 'Tagline' },
            },
            defaultProps: { title: 'Your Project', tagline: 'Discover your dream property' },
            render: ProjectHero,
        },
        LotGrid: {
            label: 'Lot Grid',
            fields: {
                projectId: { type: 'number', label: 'Project ID' },
                heading: { type: 'text', label: 'Heading' },
            },
            defaultProps: { heading: 'Available Lots' },
            render: LotGrid,
        },
        LotCard: {
            label: 'Lot Card',
            fields: {
                lotId: { type: 'number', label: 'Lot ID' },
                price: { type: 'text', label: 'Price' },
                status: { type: 'text', label: 'Status' },
            },
            defaultProps: { price: '$480,000', status: 'Available' },
            render: LotCard,
        },
        AgentProfile: {
            label: 'Agent Profile',
            fields: {
                agentId: { type: 'number', label: 'Agent User ID' },
                name: { type: 'text', label: 'Agent Name' },
                phone: { type: 'text', label: 'Phone' },
                ctaLabel: { type: 'text', label: 'CTA Button Label' },
            },
            defaultProps: { name: 'Your Agent', ctaLabel: 'Contact Agent' },
            render: AgentProfile,
        },
        EnquiryForm: {
            label: 'Enquiry Form',
            fields: {
                heading: { type: 'text', label: 'Heading' },
                buttonLabel: { type: 'text', label: 'Button Label' },
            },
            defaultProps: { heading: 'Get in Touch', buttonLabel: 'Send Enquiry' },
            render: EnquiryForm,
        },
        ProjectGallery: {
            label: 'Project Gallery',
            fields: {
                projectId: { type: 'number', label: 'Project ID' },
                heading: { type: 'text', label: 'Heading' },
            },
            defaultProps: { heading: 'Gallery' },
            render: ProjectGallery,
        },
        FloorPlanViewer: {
            label: 'Floor Plan Viewer',
            fields: {
                lotId: { type: 'number', label: 'Lot ID' },
                label: { type: 'text', label: 'Label' },
            },
            defaultProps: { label: 'Floor Plan' },
            render: FloorPlanViewer,
        },
        PriceList: {
            label: 'Price List',
            fields: {
                projectId: { type: 'number', label: 'Project ID' },
                heading: { type: 'text', label: 'Heading' },
            },
            defaultProps: { heading: 'Price List' },
            render: PriceList,
        },
        KeyFeatures: {
            label: 'Key Features',
            fields: {
                features: { type: 'textarea', label: 'Features (one per line)' },
            },
            defaultProps: { features: 'City Views\nNBN Connected\nSMSF Eligible' },
            render: KeyFeatures,
        },
        TextBlock: {
            label: 'Text Block',
            fields: {
                heading: { type: 'text', label: 'Heading' },
                body: { type: 'textarea', label: 'Body' },
            },
            defaultProps: { heading: 'Section Heading', body: 'Add your content here.' },
            render: TextBlock,
        },
        ImageBlock: {
            label: 'Image Block',
            fields: {
                alt: { type: 'text', label: 'Alt Text' },
                caption: { type: 'text', label: 'Caption' },
            },
            defaultProps: { alt: 'Property Image' },
            render: ImageBlock,
        },
        VideoEmbed: {
            label: 'Video Embed',
            fields: {
                url: { type: 'text', label: 'Video URL (YouTube/Vimeo)' },
                caption: { type: 'text', label: 'Caption' },
            },
            defaultProps: {},
            render: VideoEmbed,
        },
        CallToAction: {
            label: 'Call To Action',
            fields: {
                heading: { type: 'text', label: 'Heading' },
                buttonLabel: { type: 'text', label: 'Button Label' },
                buttonUrl: { type: 'text', label: 'Button URL' },
            },
            defaultProps: { heading: 'Ready to Get Started?', buttonLabel: 'Contact Us' },
            render: CallToAction,
        },
        SurveyBlock: {
            label: 'Survey Block',
            fields: {
                heading: { type: 'text', label: 'Heading' },
            },
            defaultProps: { heading: 'Quick Survey' },
            render: SurveyBlock,
        },
        FlyerHero: {
            label: 'Flyer Hero',
            fields: {
                title: { type: 'text', label: 'Title' },
                subtitle: { type: 'text', label: 'Subtitle' },
            },
            defaultProps: { title: 'Property Title' },
            render: FlyerHero,
        },
        FlyerLotSpecs: {
            label: 'Flyer Lot Specs',
            fields: {
                beds: { type: 'text', label: 'Beds' },
                baths: { type: 'text', label: 'Baths' },
                cars: { type: 'text', label: 'Cars' },
                area: { type: 'text', label: 'Area' },
                price: { type: 'text', label: 'Price' },
            },
            defaultProps: { beds: '3', baths: '2', cars: '2', area: '450m²', price: '$480,000' },
            render: FlyerLotSpecs,
        },
        FlyerAgentFooter: {
            label: 'Flyer Agent Footer',
            fields: {
                agentName: { type: 'text', label: 'Agent Name' },
                agentPhone: { type: 'text', label: 'Agent Phone' },
                disclaimer: { type: 'textarea', label: 'Disclaimer' },
            },
            defaultProps: { agentName: 'Agent Name', agentPhone: '0400 000 000' },
            render: FlyerAgentFooter,
        },
        FlyerTextBlock: {
            label: 'Flyer Text Block',
            fields: {
                headline: { type: 'text', label: 'Headline' },
                body: { type: 'textarea', label: 'Body' },
            },
            defaultProps: { headline: 'Headline' },
            render: FlyerTextBlock,
        },
        FlyerQRCode: {
            label: 'Flyer QR Code',
            fields: {
                url: { type: 'text', label: 'URL' },
                label: { type: 'text', label: 'Label' },
            },
            defaultProps: { label: 'Scan to view' },
            render: FlyerQRCode,
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
                        <a href="/campaign-sites" className="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">
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
                    <Puck config={puckConfig} data={initialData} onPublish={handlePublish} />
                </div>
            </div>
        </>
    );
}
