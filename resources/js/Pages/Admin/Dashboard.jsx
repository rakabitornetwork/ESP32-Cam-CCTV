import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes, formatWhen } from '@/lib/format';
import { Head, router } from '@inertiajs/react';

export default function Dashboard({ users, organizations, invoices }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Panel admin</h2>}>
            <Head title="Panel admin" />
            <div className="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
                <section className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <h3 className="border-b px-4 py-3 font-medium">Pengguna</h3>
                    <table className="min-w-full text-sm">
                        <tbody>
                            {users.map((user) => (
                                <tr key={user.id} className="border-t">
                                    <td className="px-4 py-3">{user.name}</td>
                                    <td className="px-4 py-3">{user.email}</td>
                                    <td className="px-4 py-3">{user.is_admin ? 'Admin' : 'Pengguna'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>
                <section className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <h3 className="border-b px-4 py-3 font-medium">Organisasi</h3>
                    <table className="min-w-full text-sm">
                        <tbody>
                            {organizations.map((organization) => (
                                <tr key={organization.id} className="border-t">
                                    <td className="px-4 py-3">{organization.name}</td>
                                    <td className="px-4 py-3">{organization.subscription?.plan?.name}</td>
                                    <td className="px-4 py-3">{organization.devices_count} perangkat</td>
                                    <td className="px-4 py-3">
                                        {formatBytes(organization.storage_quota?.used_bytes)} / {formatBytes(organization.storage_quota?.max_bytes)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>
                <section className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <h3 className="border-b px-4 py-3 font-medium">Faktur</h3>
                    <table className="min-w-full text-sm">
                        <tbody>
                            {invoices.map((invoice) => (
                                <tr key={invoice.id} className="border-t">
                                    <td className="px-4 py-3">{invoice.number}</td>
                                    <td className="px-4 py-3">{invoice.organization?.name}</td>
                                    <td className="px-4 py-3">Rp{invoice.amount_idr.toLocaleString('id-ID')}</td>
                                    <td className="px-4 py-3">{invoice.status}</td>
                                    <td className="px-4 py-3">
                                        {invoice.status === 'pending' ? (
                                            <button type="button" className="text-brand-700" onClick={() => router.post(route('billing.pay', invoice.id))}>
                                                Tandai lunas
                                            </button>
                                        ) : (
                                            formatWhen(invoice.paid_at)
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
