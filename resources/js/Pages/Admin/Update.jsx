import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

function Row({ label, children }) {
    return (
        <div className="grid gap-1 border-b border-gray-200 py-3 sm:grid-cols-[8rem_1fr] sm:gap-4">
            <dt className="text-gray-500">{label}</dt>
            <dd className="text-gray-900">{children}</dd>
        </div>
    );
}

export default function Update({ status }) {
    const result = usePage().props.flash.update_result;
    const current = result?.status ?? status;
    const check = useForm({});
    const apply = useForm({});
    const canApply = current.available && current.upstream;
    const busy = check.processing || apply.processing;

    const submitApply = (event) => {
        event.preventDefault();
        if (!confirm('Aplikasi akan mengambil kode terbaru, lalu menjalankan migrasi. Lanjutkan?')) {
            return;
        }
        apply.post(route('admin.update.apply'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Pembaruan</h2>}>
            <Head title="Pembaruan" />
            <div className="mx-auto max-w-3xl px-4 py-8 sm:px-6">
                {!current.available && <p className="text-sm text-gray-700">{current.message}</p>}

                {current.available && (
                    <dl className="border-t border-gray-200 text-sm">
                        <Row label="Cabang">{current.branch}</Row>
                        <Row label="Commit">
                            <span className="font-mono">{current.commit}</span>
                            {current.subject && <span className="mt-1 block text-gray-600">{current.subject}</span>}
                        </Row>
                        <Row label="Remote">{current.remote || 'Belum diatur'}</Row>
                        <Row label="Status">
                            {current.upstream
                                ? current.behind > 0
                                    ? `${current.behind} commit baru siap dipasang.`
                                    : 'Aplikasi sudah sama dengan remote.'
                                : 'Cabang belum terhubung ke remote.'}
                        </Row>
                        {current.dirty && (
                            <Row label="Lokal">
                                <p>Ada perubahan yang belum disimpan. Perubahan itu disimpan sementara saat pembaruan, lalu dikembalikan.</p>
                                <ul className="mt-2 space-y-1 font-mono text-xs text-gray-700">
                                    {current.dirty_files.map((file) => (
                                        <li key={file}>{file}</li>
                                    ))}
                                </ul>
                            </Row>
                        )}
                    </dl>
                )}

                <div className="mt-6 flex flex-wrap gap-3">
                    <form onSubmit={(event) => { event.preventDefault(); check.post(route('admin.update.check')); }}>
                        <button
                            type="submit"
                            disabled={busy}
                            className="inline-flex h-10 items-center border border-gray-300 bg-white px-4 text-sm text-gray-800 hover:bg-gray-50 disabled:opacity-50"
                        >
                            {check.processing ? 'Memeriksa...' : 'Periksa pembaruan'}
                        </button>
                    </form>
                    <form onSubmit={submitApply}>
                        <button
                            type="submit"
                            disabled={!canApply || busy}
                            className="inline-flex h-10 items-center bg-gray-900 px-4 text-sm text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {apply.processing ? 'Memperbarui...' : 'Perbarui sekarang'}
                        </button>
                    </form>
                </div>

                {apply.processing && (
                    <p className="mt-4 text-sm text-gray-600">
                        Jangan tutup halaman ini. Migrasi database bisa beberapa saat.
                    </p>
                )}

                {result && (
                    <div className="mt-8 space-y-4">
                        <p className={result.ok ? 'text-sm text-brand-700' : 'text-sm text-red-700'}>{result.message}</p>
                        {result.steps?.map((step) => (
                            <section key={step.command} className="border border-gray-200 bg-white">
                                <h3 className="border-b border-gray-200 px-4 py-3 font-mono text-xs text-gray-700">
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
