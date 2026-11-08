import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Edit({ retainDays, maxRetainDays, canManage }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        retain_days: retainDays,
    });

    const submit = (event) => {
        event.preventDefault();
        put(route('settings.update'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Pengaturan</h2>}>
            <Head title="Pengaturan" />
            <form onSubmit={submit} className="mx-auto max-w-xl space-y-4 px-4 py-8 sm:px-6">
                {flash?.status && <p className="text-sm text-brand-700">{flash.status}</p>}
                <div className="rounded-lg bg-white p-6 shadow-sm">
                    <InputLabel htmlFor="retain_days" value="Masa simpan (hari)" />
                    <TextInput
                        id="retain_days"
                        type="number"
                        min="1"
                        max={maxRetainDays}
                        value={data.retain_days}
                        className="mt-1 block w-full"
                        disabled={!canManage}
                        onChange={(event) => setData('retain_days', event.target.value)}
                    />
                    <p className="mt-2 text-xs text-gray-500">Maksimum paket saat ini: {maxRetainDays} hari.</p>
                    <InputError message={errors.retain_days} className="mt-2" />
                    {canManage && (
                        <PrimaryButton className="mt-4" disabled={processing}>
                            Simpan
                        </PrimaryButton>
                    )}
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
