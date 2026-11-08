import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes, formatWhen } from '@/lib/format';
import { Head } from '@inertiajs/react';

export default function Index({ captures }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Tangkapan</h2>}>
            <Head title="Tangkapan" />
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {captures.data.length === 0 ? (
                    <p className="text-sm text-gray-600">Belum ada foto gerak.</p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {captures.data.map((capture) => (
                            <figure key={capture.id} className="overflow-hidden rounded-lg bg-white shadow-sm">
                                <img src={route('captures.stream', capture.id)} alt={capture.device_name} className="h-48 w-full object-cover" />
                                <figcaption className="space-y-1 p-3 text-sm">
                                    <div className="font-medium">{capture.device_name}</div>
                                    <div className="text-gray-500">
                                        {formatWhen(capture.captured_at)} · {capture.trigger} · {formatBytes(capture.bytes)}
                                    </div>
                                </figcaption>
                            </figure>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
