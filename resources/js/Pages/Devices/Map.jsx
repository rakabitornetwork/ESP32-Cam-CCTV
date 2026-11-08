import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Suspense, lazy } from 'react';

const DeviceMap = lazy(() => import('@/Components/DeviceMap'));

export default function Map({ devices }) {
    const placed = devices.filter((device) => device.latitude != null && device.longitude != null);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Peta</h2>}>
            <Head title="Peta" />
            <div className="mx-auto max-w-7xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                {placed.length === 0 && <p className="text-sm text-gray-600">Belum ada perangkat dengan koordinat.</p>}
                <Suspense fallback={<div className="h-112 animate-pulse rounded-lg bg-gray-200" />}>
                    <DeviceMap devices={devices} />
                </Suspense>
            </div>
        </AuthenticatedLayout>
    );
}
