import { Head } from '@inertiajs/react';

interface Campaign {
    id: number;
    title: string;
    site_id: string;
    puck_content: unknown;
    puck_enabled: boolean;
    header: string | null;
    banner: string | null;
}

interface Props {
    campaign: Campaign;
}

export default function PublicCampaignSitePage({ campaign }: Props) {
    return (
        <>
            <Head title={campaign.title} />
            <div className="min-h-screen bg-white text-gray-900">
                {/* Banner */}
                {campaign.banner && (
                    <div
                        className="relative h-64 w-full overflow-hidden bg-gray-900 sm:h-80 lg:h-96"
                        style={{
                            backgroundImage: `url(${campaign.banner})`,
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                        }}
                    >
                        <div className="absolute inset-0 bg-black/40" />
                        <div className="absolute inset-0 flex items-center justify-center px-4">
                            <h1 className="text-center text-4xl font-bold text-white drop-shadow-lg sm:text-5xl">
                                {campaign.title}
                            </h1>
                        </div>
                    </div>
                )}

                {/* No banner: simple header */}
                {!campaign.banner && (
                    <div className="bg-gray-900 px-4 py-16 text-center text-white">
                        <h1 className="text-4xl font-bold">{campaign.title}</h1>
                    </div>
                )}

                <div className="mx-auto max-w-5xl px-4 py-10">
                    {/* Header text */}
                    {campaign.header && (
                        <p className="mb-8 text-lg leading-relaxed text-gray-600">{campaign.header}</p>
                    )}

                    {/* Puck content area */}
                    {campaign.puck_enabled ? (
                        <div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500">
                            <p className="text-sm font-medium">Puck content rendered here</p>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-gray-200 p-8 text-center text-gray-400">
                            <p className="text-sm">This page has no published content yet.</p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
