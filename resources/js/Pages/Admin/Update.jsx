import AdminNav from '@/Components/AdminNav';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function Update({ status }) {
    const result = usePage().props.flash.update_result;
    const current = result?.status ?? status;
    const check = useForm({});
    const apply = useForm({});
    const canApply = current.available && !current.dirty && current.upstream;

    const submitApply = (event) => {
        event.preventDefault();
        if (!confirm('Aplikasi akan mengambil kode terbaru, memasang dependensi, membangun tampilan, lalu menjalankan migrasi. Lanjutkan?')) {
            return;
        }
        apply.post(route('admin.update.apply'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Pembaruan aplikasi</h2>}>
            <Head title="Pembaruan aplikasi" />
            <div className="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
                <AdminNav />
                <div className="space-y-4 rounded-lg bg-white p-6 text-sm shadow-sm">
                    {!current.available && <p>{current.message}</p>}
                    {current.available && (
                        <>
                            <p>Cabang: {current.branch}</p>
                            <p>Commit: {current.commit}</p>
                            <p>{current.subject}</p>
                            <p className="break-all">Remote: {current.remote || 'Belum diatur'}</p>
                            <p>
                                {current.upstream
                                    ? current.behind > 0
                                        ? `${current.behind} commit baru siap dipasang.`
                                        : 'Aplikasi sudah sama dengan remote.'
                                    : 'Cabang belum terhubung ke remote.'}
                            </p>
                            {current.dirty && (
                                <p className="text-amber-800">
                                    Ada perubahan lokal yang belum di-commit. Pembaruan tidak dijalankan agar perubahan itu tidak tertimpa.
                                </p>
                            )}
                        </>
                    )}
                    <div className="flex flex-wrap gap-3 pt-2">
                        <form onSubmit={(event) => { event.preventDefault(); check.post(route('admin.update.check')); }}>
                            <SecondaryButton type="submit" disabled={check.processing || apply.processing}>
                                {check.processing ? 'Memeriksa...' : 'Periksa pembaruan'}
                            </SecondaryButton>
                        </form>
                        <form onSubmit={submitApply}>
                            <PrimaryButton disabled={!canApply || check.processing || apply.processing}>
                                {apply.processing ? 'Memperbarui...' : 'Perbarui sekarang'}
                            </PrimaryButton>
                        </form>
                    </div>
                    {apply.processing && (
                        <p>Jangan tutup halaman ini. Pemasangan dependensi dan pembangunan tampilan bisa beberapa menit.</p>
                    )}
                </div>
                {result && (
                    <div className="mt-4 space-y-3">
                        <p className={result.ok ? 'text-sm text-brand-700' : 'text-sm text-red-700'}>{result.message}</p>
                        {result.steps?.map((step) => (
                            <section key={step.command} className="overflow-hidden rounded-lg bg-white shadow-sm">
                                <h3 className="border-b px-4 py-3 font-mono text-xs">
                                    {step.ok ? 'Berhasil' : 'Gagal'} — {step.command}
                                </h3>
                                {step.output && (
                                    <pre className="max-h-64 overflow-auto whitespace-pre-wrap px-4 py-3 text-xs text-gray-700">{step.output}</pre>
                                )}
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
