import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Suspense, lazy } from 'react';

const LazyMap = lazy(() => import('@/Components/DeviceMap'));

export default function Edit({ device }) {
    const { data, setData, put, processing, errors } = useForm({
        name: device.name,
        location: device.location || '',
        latitude: device.latitude ?? '',
        longitude: device.longitude ?? '',
        image_quality: device.image_quality || 'standar',
    });

    const submit = (event) => {
        event.preventDefault();
        put(route('devices.update', device.id));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Ubah perangkat</h2>}>
            <Head title="Ubah perangkat" />
            <form onSubmit={submit} className="mx-auto grid max-w-7xl gap-6 px-4 py-8 lg:grid-cols-2 sm:px-6 lg:px-8">
                <div className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <div>
                        <InputLabel htmlFor="name" value="Nama" />
                        <TextInput id="name" value={data.name} className="mt-1 block w-full" onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="location" value="Lokasi" />
                        <TextInput id="location" value={data.location} className="mt-1 block w-full" onChange={(e) => setData('location', e.target.value)} />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel htmlFor="latitude" value="Lintang" />
                            <TextInput id="latitude" value={data.latitude} className="mt-1 block w-full" onChange={(e) => setData('latitude', e.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="longitude" value="Bujur" />
                            <TextInput id="longitude" value={data.longitude} className="mt-1 block w-full" onChange={(e) => setData('longitude', e.target.value)} />
                        </div>
                    </div>
                    <InputError message={errors.latitude || errors.longitude} className="mt-2" />
                    <div>
                        <InputLabel htmlFor="image_quality" value="Kualitas gambar" />
                        <select
                            id="image_quality"
                            value={data.image_quality}
                            onChange={(event) => setData('image_quality', event.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="hemat">Hemat — berkas lebih kecil</option>
                            <option value="standar">Standar</option>
                            <option value="tajam">Tajam — lebih detail</option>
                        </select>
                        <p className="mt-2 text-xs text-gray-500">Kamera memakai pilihan ini pada foto berikutnya, tanpa flash ulang.</p>
                        <InputError message={errors.image_quality} className="mt-2" />
                    </div>
                    <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                </div>
                <Suspense fallback={<div className="h-112 animate-pulse rounded-lg bg-gray-200" />}>
                    <LazyMap
                        latitude={data.latitude || null}
                        longitude={data.longitude || null}
                        onPick={(latitude, longitude) => {
                            setData((current) => ({ ...current, latitude, longitude }));
                        }}
                    />
                </Suspense>
            </form>
        </AuthenticatedLayout>
    );
}
