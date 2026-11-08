import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes } from '@/lib/format';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    const organization = usePage().props.organization;
    const used = stats.quota.used_bytes;
    const max = stats.quota.max_bytes || 1;
    const percent = Math.min(100, Math.round((used / max) * 100));

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Dasbor</h2>}>
            <Head title="Dasbor" />
            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <p className="text-sm text-gray-600">
                    {organization ? `${organization.name} · paket ${stats.plan || '—'}` : 'Organisasi belum tersedia.'}
                </p>
                <div className="grid gap-4 sm:grid-cols-3">
                    {[
                        ['Perangkat', stats.devices],
                        ['Rekaman', stats.recordings],
                        ['Tangkapan', stats.captures],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-lg bg-white p-5 shadow-sm">
                            <div className="text-sm text-gray-500">{label}</div>
                            <div className="mt-2 text-2xl font-semibold">{value}</div>
                        </div>
                    ))}
                </div>
                <div className="rounded-lg bg-white p-5 shadow-sm">
                    <div className="flex justify-between text-sm">
                        <span>Pemakaian penyimpanan</span>
                        <span>
                            {formatBytes(used)} / {formatBytes(stats.quota.max_bytes)}
                        </span>
                    </div>
                    <div className="mt-3 h-2 rounded-full bg-gray-100">
                        <div className="h-2 rounded-full bg-brand-600" style={{ width: `${percent}%` }} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
