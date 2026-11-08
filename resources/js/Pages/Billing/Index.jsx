import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes, formatWhen } from '@/lib/format';
import { Head, router, usePage } from '@inertiajs/react';

export default function Index({ plans, invoices, canManage }) {
    const { organization, flash } = usePage().props;
    const current = organization?.plan?.slug;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Langganan</h2>}>
            <Head title="Langganan" />
            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {flash?.status && <p className="rounded-md bg-brand-50 px-4 py-3 text-sm text-brand-700">{flash.status}</p>}
                <div className="grid gap-4 lg:grid-cols-3">
                    {plans.map((plan) => (
                        <article key={plan.id} className="flex flex-col rounded-lg bg-white p-5 shadow-sm">
                            <h3 className="text-lg font-semibold">{plan.name}</h3>
                            <p className="mt-2 text-2xl">
                                {plan.price_idr === 0 ? 'Gratis' : `Rp${plan.price_idr.toLocaleString('id-ID')}`}
                                <span className="text-sm text-gray-500"> / bulan</span>
                            </p>
                            <ul className="mt-4 space-y-1 text-sm text-gray-600">
                                <li>{formatBytes(plan.max_bytes)} penyimpanan</li>
                                <li>{plan.max_devices} perangkat</li>
                                <li>Simpan {plan.retain_days} hari</li>
                                <li>{plan.max_captures_per_day == null ? 'Foto gerak tanpa batas harian' : `${plan.max_captures_per_day} foto gerak / hari`}</li>
                            </ul>
                            {canManage && (
                                <PrimaryButton
                                    className="mt-4"
                                    disabled={current === plan.slug}
                                    onClick={() => router.post(route('billing.store'), { plan_id: plan.id })}
                                >
                                    {current === plan.slug ? 'Paket aktif' : 'Pilih'}
                                </PrimaryButton>
                            )}
                        </article>
                    ))}
                </div>
                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table className="min-w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Jumlah</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoices.length === 0 && (
                                <tr>
                                    <td className="px-4 py-6 text-gray-500" colSpan="4">
                                        Belum ada faktur.
                                    </td>
                                </tr>
                            )}
                            {invoices.map((invoice) => (
                                <tr key={invoice.id} className="border-t">
                                    <td className="px-4 py-3">{invoice.number}</td>
                                    <td className="px-4 py-3">Rp{invoice.amount_idr.toLocaleString('id-ID')}</td>
                                    <td className="px-4 py-3">{invoice.status}</td>
                                    <td className="px-4 py-3 text-right">
                                        {canManage && invoice.status === 'pending' && (
                                            <button
                                                type="button"
                                                className="text-brand-700"
                                                onClick={() => router.post(route('billing.pay', invoice.id))}
                                            >
                                                Tandai lunas
                                            </button>
                                        )}
                                        {invoice.paid_at && <span className="text-gray-500">{formatWhen(invoice.paid_at)}</span>}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
