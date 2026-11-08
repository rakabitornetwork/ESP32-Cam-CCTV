import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Suspense, lazy } from 'react';

const LazyMap = lazy(() => import('@/Components/DeviceMap'));

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        location: '',
        latitude: '',
        longitude: '',
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('devices.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Perangkat baru</h2>}>
            <Head title="Perangkat baru" />
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
                        <InputError message={errors.location} className="mt-2" />
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
