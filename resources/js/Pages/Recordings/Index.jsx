import DateRangePicker from '@/Components/DateRangePicker';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes, formatWhen, toYmd } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function parseYmd(value) {
    if (!value) {
        return undefined;
    }

    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export default function Index({ recordings, devices, filters }) {
    const [range, setRange] = useState({
        from: parseYmd(filters.from),
        to: parseYmd(filters.to),
    });
    const [deviceId, setDeviceId] = useState(filters.device_id || '');

    const apply = (event) => {
        event.preventDefault();
        router.get(
            route('recordings.index'),
            {
                device_id: deviceId || undefined,
                from: toYmd(range?.from) || undefined,
                to: toYmd(range?.to) || undefined,
            },
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Rekaman</h2>}>
            <Head title="Rekaman" />
            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <form onSubmit={apply} className="flex flex-wrap items-end gap-6 rounded-lg bg-white p-4 shadow-sm">
                    <div>
                        <label className="text-xs text-gray-500">Perangkat</label>
                        <select
                            value={deviceId}
                            onChange={(event) => setDeviceId(event.target.value)}
                            className="mt-1 block rounded-md border-gray-300 text-sm"
                        >
                            <option value="">Semua</option>
                            {devices.map((device) => (
                                <option key={device.id} value={device.id}>
                                    {device.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <DateRangePicker selected={range} onSelect={setRange} />
                    <button type="submit" className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white">
                        Filter
                    </button>
                </form>
                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table className="min-w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Mulai</th>
                                <th className="px-4 py-3">Perangkat</th>
                                <th className="px-4 py-3">Ukuran</th>
                                <th className="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recordings.data.length === 0 && (
                                <tr>
                                    <td className="px-4 py-6 text-gray-500" colSpan="4">
                                        Tidak ada rekaman pada rentang ini.
                                    </td>
                                </tr>
                            )}
                            {recordings.data.map((recording) => (
                                <tr key={recording.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Link href={route('recordings.show', recording.id)} className="text-brand-700">
                                            {formatWhen(recording.started_at)}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{recording.device_name}</td>
                                    <td className="px-4 py-3">{formatBytes(recording.bytes)}</td>
                                    <td className="px-4 py-3">{recording.status}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
