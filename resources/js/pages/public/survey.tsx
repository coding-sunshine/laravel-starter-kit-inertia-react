import { Head, useForm } from '@inertiajs/react';

interface Campaign {
    id: number;
    title: string;
    site_id: string;
}

interface Props {
    campaign: Campaign;
}

interface SurveyFormData {
    name: string;
    email: string;
    phone: string;
    campaign_id: number;
}

export default function PublicSurveyPage({ campaign }: Props) {
    const { data, setData, post, processing, errors, wasSuccessful } = useForm<SurveyFormData>({
        name: '',
        email: '',
        phone: '',
        campaign_id: campaign.id,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(`/survey/${campaign.site_id}`);
    }

    return (
        <>
            <Head title={`Survey — ${campaign.title}`} />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <h1 className="text-2xl font-bold text-gray-900">{campaign.title}</h1>
                        <p className="mt-2 text-gray-500">Fill in your details below and we'll be in touch.</p>
                    </div>

                    {wasSuccessful ? (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-8 text-center">
                            <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                                <svg
                                    className="h-6 w-6 text-green-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                            <h2 className="text-lg font-semibold text-green-800">Thanks for your response!</h2>
                            <p className="mt-1 text-sm text-green-700">We'll get back to you shortly.</p>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
                            <form onSubmit={handleSubmit} className="space-y-5">
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-sm font-medium text-gray-700">
                                        Full Name <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Jane Smith"
                                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {errors.name && (
                                        <p className="text-xs text-red-600">{errors.name}</p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <label className="text-sm font-medium text-gray-700">
                                        Email Address <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="jane@example.com"
                                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {errors.email && (
                                        <p className="text-xs text-red-600">{errors.email}</p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <label className="text-sm font-medium text-gray-700">Phone Number</label>
                                    <input
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        placeholder="+61 400 000 000"
                                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {errors.phone && (
                                        <p className="text-xs text-red-600">{errors.phone}</p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing ? 'Submitting...' : 'Submit'}
                                </button>
                            </form>
                        </div>
                    )}

                    <p className="mt-6 text-center text-xs text-gray-400">
                        Your information is kept private and will not be shared.
                    </p>
                </div>
            </div>
        </>
    );
}
