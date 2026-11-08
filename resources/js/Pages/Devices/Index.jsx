import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatWhen } from '@/lib/format';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Index({ devices }) {
    const canManage = ['owner', 'admin'].includes(usePage().props.organization?.role);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Perangkat</h2>}>
            <Head title="Perangkat" />
            <div className="mx-auto max-w-7xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                {canManage && (
                    <Link href={route('devices.create')}>
                        <PrimaryButton>Tambah perangkat</PrimaryButton>
                    </Link>
                )}
                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table className="min-w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Lokasi</th>
                                <th className="px-4 py-3">Terakhir terlihat</th>
                            </tr>
                        </thead>
                        <tbody>
                            {devices.length === 0 && (
                                <tr>
                                    <td className="px-4 py-6 text-gray-500" colSpan="3">
                                        Belum ada perangkat.
                                    </td>
                                </tr>
                            )}
                            {devices.map((device) => (
                                <tr key={device.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Link href={route('devices.show', device.id)} className="font-medium text-brand-700">
                                            {device.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{device.location || '—'}</td>
                                    <td className="px-4 py-3">{formatWhen(device.last_seen_at)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
