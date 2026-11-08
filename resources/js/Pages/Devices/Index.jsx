import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatWhen } from '@/lib/format';
import { DialogTitle } from '@headlessui/react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ devices, plainToken, tokenDevice }) {
    const page = usePage();
    const canManage = ['owner', 'admin'].includes(page.props.organization?.role);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        location: '',
    });
    const edit = useForm({ name: '', location: '' });
    const [copied, setCopied] = useState(false);
    const [adding, setAdding] = useState(false);
    const [editingId, setEditingId] = useState(null);

    const closeAdd = () => {
        if (processing) {
            return;
        }
        setAdding(false);
        reset();
        clearErrors();
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('devices.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                clearErrors();
                setAdding(false);
            },
        });
    };

    const copyToken = async () => {
        await navigator.clipboard.writeText(plainToken);
        setCopied(true);
    };

    const startEdit = (device) => {
        setEditingId(device.id);
        edit.setData({ name: device.name, location: device.location || '' });
        edit.clearErrors();
    };

    const saveEdit = (event) => {
        event.preventDefault();
        edit.patch(route('devices.update', editingId), {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    };

    const remove = (device) => {
        if (!confirm(`Hapus kamera ${device.name}? Foto dari kamera ini ikut terhapus.`)) {
            return;
        }
        router.delete(route('devices.destroy', device.id), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Kamera</h2>}>
            <Head title="Kamera" />
            <div className="mx-auto max-w-3xl px-4 py-8 sm:px-6">
                {plainToken && (
                    <section className="mb-10 border border-gray-300 bg-white px-5 py-4">
                        <p className="text-sm text-gray-800">
                            {`Token untuk ${tokenDevice || 'kamera baru'}. Salin ke sketsa sekarang. Halaman ini tidak menampilkannya lagi.`}
                        </p>
                        <code className="mt-3 block break-all font-mono text-sm text-gray-900">{plainToken}</code>
                        <button type="button" onClick={copyToken} className="mt-3 text-sm text-brand-700 hover:text-brand-600">
                            {copied ? 'Tersalin' : 'Salin token'}
                        </button>
                    </section>
                )}

                <section>
                    <div className="flex items-center justify-between gap-4">
                        <h3 className="text-sm font-medium text-gray-500">Yang terpasang</h3>
                        {canManage && (
                            <button
                                type="button"
                                onClick={() => setAdding(true)}
                                className="inline-flex h-10 items-center bg-gray-900 px-4 text-sm text-white hover:bg-gray-800"
                            >
                                Tambah kamera
                            </button>
                        )}
                    </div>
                    <InputError message={page.props.errors?.device} className="mt-2" />
                    {devices.length === 0 ? (
                        <p className="mt-4 text-sm text-gray-600">Belum ada kamera.</p>
                    ) : (
                        <ul className="mt-2 divide-y divide-gray-200 border-y border-gray-200">
                            {devices.map((device) => (
                                <li key={device.id} className="flex items-start justify-between gap-4 py-3 text-sm">
                                    <div className="min-w-0 flex-1">
                                        {editingId === device.id ? (
                                            <form onSubmit={saveEdit} className="space-y-2">
                                                <TextInput
                                                    value={edit.data.name}
                                                    className="block w-full"
                                                    onChange={(event) => edit.setData('name', event.target.value)}
                                                    required
                                                    aria-label="Nama"
                                                />
                                                <TextInput
                                                    value={edit.data.location}
                                                    className="block w-full"
                                                    onChange={(event) => edit.setData('location', event.target.value)}
                                                    aria-label="Lokasi"
                                                />
                                                <div className="flex gap-4">
                                                    <button type="submit" disabled={edit.processing} className="text-green-700 hover:text-green-800 disabled:opacity-50">
                                                        Simpan
                                                    </button>
                                                    <button type="button" onClick={() => setEditingId(null)} className="text-gray-600 hover:text-gray-800">
                                                        Batal
                                                    </button>
                                                </div>
                                                <InputError message={edit.errors.name || edit.errors.location} />
                                            </form>
                                        ) : (
                                            <>
                                                <p className="font-medium text-gray-900">{device.name}</p>
                                                <p className="text-gray-500">{device.location || 'Lokasi belum diisi'}</p>
                                                {canManage && (
                                                    <div className="mt-2 flex gap-4">
                                                        <button type="button" onClick={() => startEdit(device)} className="text-blue-700 hover:text-blue-800">
                                                            Ubah
                                                        </button>
                                                        <button type="button" onClick={() => remove(device)} className="text-red-700 hover:text-red-800">
                                                            Hapus
                                                        </button>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </div>
                                    <div className="shrink-0 text-right text-gray-500">
                                        <p className="font-mono text-xs text-gray-700">{device.token_prefix}</p>
                                        <p>{formatWhen(device.last_seen_at)}</p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <Modal show={adding} maxWidth="md" onClose={closeAdd}>
                <form onSubmit={submit} className="px-6 py-5">
                    <DialogTitle className="text-lg font-semibold text-gray-900">Tambah kamera</DialogTitle>
                    <div className="mt-5">
                        <InputLabel htmlFor="name" value="Nama" />
                        <TextInput
                            id="name"
                            value={data.name}
                            className="mt-1 block w-full"
                            onChange={(event) => setData('name', event.target.value)}
                            autoFocus
                            required
                        />
                    </div>
                    <div className="mt-4">
                        <InputLabel htmlFor="location" value="Lokasi" />
                        <TextInput
                            id="location"
                            value={data.location}
                            className="mt-1 block w-full"
                            onChange={(event) => setData('location', event.target.value)}
                        />
                    </div>
                    <InputError message={errors.name || errors.location} className="mt-2" />
                    <div className="mt-6 flex justify-end gap-4">
                        <button type="button" onClick={closeAdd} className="text-sm text-gray-500 hover:text-gray-700">
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex h-10 items-center bg-gray-900 px-4 text-sm text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            Tambahkan
                        </button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
