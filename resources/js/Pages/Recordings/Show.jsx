import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes, formatWhen } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';

export default function Show({ recording }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Rekaman</h2>}>
            <Head title="Rekaman" />
            <div className="mx-auto max-w-4xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                <div className="text-sm text-gray-600">
                    {recording.device_name} · {formatWhen(recording.started_at)} · {formatBytes(recording.bytes)} · {recording.status}
                </div>
                {recording.status === 'ready' ? (
                    <video className="w-full rounded-lg bg-black" controls src={route('recordings.stream', recording.id)} />
                ) : (
                    <p className="rounded-lg bg-white p-6 text-sm shadow-sm">Berkas belum siap diputar.</p>
                )}
                <div className="flex gap-3">
                    {recording.status === 'ready' && (
                        <a href={route('recordings.download', recording.id)}>
                            <SecondaryButton>Unduh</SecondaryButton>
                        </a>
                    )}
                    <Link href={route('recordings.index')} className="text-sm text-brand-700">
                        Kembali
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
