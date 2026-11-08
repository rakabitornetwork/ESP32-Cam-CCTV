import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatWhen } from '@/lib/format';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Show({ device, plainToken }) {
    const canManage = ['owner', 'admin'].includes(usePage().props.organization?.role);
    const errors = usePage().props.errors;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">{device.name}</h2>}>
            <Head title={device.name} />
            <div className="mx-auto max-w-3xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                {plainToken && (
                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
                        Token hanya ditampilkan sekali. Simpan di ESP32-CAM:
                        <code className="mt-2 block break-all font-mono">{plainToken}</code>
                    </div>
                )}
                {errors.device && <p className="text-sm text-red-600">{errors.device}</p>}
                <div className="space-y-2 rounded-lg bg-white p-6 text-sm shadow-sm">
                    <p>Lokasi: {device.location || '—'}</p>
                    <p>
                        GPS: {device.latitude != null ? `${device.latitude}, ${device.longitude}` : 'Belum diatur'}
                    </p>
                    <p>Awalan token: {device.token_prefix}</p>
                    <p>Terakhir terlihat: {formatWhen(device.last_seen_at)}</p>
                    <p>PIR: {device.pir_armed ? 'Siaga' : 'Mati'}</p>
                </div>
                {canManage && (
                    <div className="flex flex-wrap gap-3">
                        <Link href={route('devices.edit', device.id)}>
                            <SecondaryButton>Ubah</SecondaryButton>
                        </Link>
                        <PrimaryButton
                            onClick={() => {
                                if (confirm('Token lama akan berhenti berfungsi. Lanjutkan?')) {
                                    router.post(route('devices.rotate-token', device.id));
                                }
                            }}
                        >
                            Putar token
                        </PrimaryButton>
                        <DangerButton
                            onClick={() => {
                                if (confirm('Hapus perangkat ini?')) {
                                    router.delete(route('devices.destroy', device.id));
                                }
                            }}
                        >
                            Hapus
                        </DangerButton>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
