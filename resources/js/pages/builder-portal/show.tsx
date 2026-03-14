import { Head } from '@inertiajs/react';

interface Portal {
    id: number;
    name: string;
    slug: string;
    primary_color: string | null;
    show_prices: boolean;
    show_agent_details: boolean;
    contact_email: string | null;
    contact_phone: string | null;
    disclaimer: string | null;
}

interface Project {
    id: number;
    name: string;
    status: string;
}

interface Props {
    portal: Portal;
    projects: Project[];
}

const STATUS_COLORS: Record<string, string> = {
    active: 'bg-green-100 text-green-700',
    coming_soon: 'bg-blue-100 text-blue-700',
    sold_out: 'bg-gray-100 text-gray-600',
    archived: 'bg-yellow-100 text-yellow-700',
};

function StatusBadge({ status }: { status: string }) {
    const colorClass = STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-600';
    const label = status.replace(/_/g, ' ');
    return (
        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${colorClass}`}>
            {label}
        </span>
    );
}

export default function BuilderPortalShowPage({ portal, projects }: Props) {
    const primaryColor = portal.primary_color ?? '#1e40af';

    return (
        <>
            <Head title={portal.name} />
            <div className="min-h-screen bg-gray-50">
                {/* Header / Branding Bar */}
                <header
                    className="px-6 py-5 text-white shadow-md"
                    style={{ backgroundColor: primaryColor }}
                >
                    <div className="mx-auto flex max-w-6xl items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">{portal.name}</h1>
                            <p className="mt-0.5 text-sm opacity-80">Builder Portal</p>
                        </div>
                        <div className="hidden text-right sm:block">
                            {portal.contact_email && (
                                <a
                                    href={`mailto:${portal.contact_email}`}
                                    className="block text-sm opacity-90 hover:opacity-100"
                                >
                                    {portal.contact_email}
                                </a>
                            )}
                            {portal.contact_phone && (
                                <a
                                    href={`tel:${portal.contact_phone}`}
                                    className="block text-sm opacity-90 hover:opacity-100"
                                >
                                    {portal.contact_phone}
                                </a>
                            )}
                        </div>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-4 py-10">
                    {/* Projects Section */}
                    <div className="mb-8">
                        <h2 className="text-xl font-bold text-gray-900">
                            Projects
                            <span className="ml-2 text-base font-normal text-gray-500">
                                ({projects.length})
                            </span>
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Browse available developments below.
                        </p>
                    </div>

                    {projects.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 py-16 text-center">
                            <p className="text-gray-400">No projects available at this time.</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {projects.map((project) => (
                                <div
                                    key={project.id}
                                    className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    {/* Placeholder image area */}
                                    <div
                                        className="mb-4 flex h-32 items-center justify-center rounded-lg"
                                        style={{ backgroundColor: `${primaryColor}18` }}
                                    >
                                        <span
                                            className="text-sm font-semibold"
                                            style={{ color: primaryColor }}
                                        >
                                            {project.name.charAt(0).toUpperCase()}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-2">
                                        <h3 className="font-semibold text-gray-900">{project.name}</h3>
                                        <StatusBadge status={project.status} />
                                    </div>

                                    <div className="mt-4">
                                        <a
                                            href={`/builder-portal/${portal.slug}/projects/${project.id}`}
                                            className="block w-full rounded-lg py-2 text-center text-sm font-medium text-white transition-opacity hover:opacity-90"
                                            style={{ backgroundColor: primaryColor }}
                                        >
                                            View Lots
                                        </a>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Disclaimer */}
                    {portal.disclaimer && (
                        <div className="mt-12 rounded-lg border border-gray-200 bg-gray-100 px-5 py-4">
                            <p className="text-xs leading-relaxed text-gray-500">{portal.disclaimer}</p>
                        </div>
                    )}
                </main>

                {/* Footer */}
                <footer className="mt-12 border-t bg-white px-6 py-5">
                    <div className="mx-auto flex max-w-6xl flex-col items-center gap-2 text-center sm:flex-row sm:justify-between sm:text-left">
                        <p className="text-sm text-gray-400">{portal.name}</p>
                        <div className="flex gap-4 text-sm text-gray-400">
                            {portal.contact_email && (
                                <a href={`mailto:${portal.contact_email}`} className="hover:text-gray-600">
                                    {portal.contact_email}
                                </a>
                            )}
                            {portal.contact_phone && (
                                <a href={`tel:${portal.contact_phone}`} className="hover:text-gray-600">
                                    {portal.contact_phone}
                                </a>
                            )}
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
