import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes, formatWhen } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';

const cardTones = {
    cameras: 'stat-card stat-card--cameras',
    today: 'stat-card stat-card--today',
    stored: 'stat-card stat-card--stored',
};

function Widget({ label, value, detail, href, tone = 'cameras' }) {
    const body = (
        <span className="relative z-10 block">
            <span className="block text-sm text-white/80">{label}</span>
            <span className="mt-2 block text-3xl font-semibold tracking-tight text-white">{value}</span>
            {detail && <span className="mt-1 block text-sm text-white/75">{detail}</span>}
        </span>
    );

    const className = `${cardTones[tone]} block px-5 py-4`;

    if (!href) {
        return <section className={className}>{body}</section>;
    }

    return (
        <Link href={href} className={className}>
            {body}
        </Link>
    );
}

function Thumb({ capture }) {
    const angle = ((Number(capture.rotation) || 0) % 360 + 360) % 360;
    const sideways = angle % 180 !== 0;

    return (
        <span className="relative block h-28 overflow-hidden bg-gray-900">
            <img
                src={route('captures.stream', capture.id)}
                alt={capture.device_name || 'Foto'}
                style={sideways ? { transform: 'translate(-50%, -50%) rotate(' + angle + 'deg)' } : { transform: `rotate(${angle}deg)` }}
                className={sideways ? 'absolute left-1/2 top-1/2 h-24 w-32 max-w-none' : 'h-28 w-full object-cover'}
            />
        </span>
    );
}

export default function Dashboard({ summary, cameras, recent }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Dasbor</h2>}>
            <Head title="Dasbor" />
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="grid gap-4 sm:grid-cols-3">
                    <Widget label="Kamera" value={summary.cameras} href={route('devices.index')} tone="cameras" />
                    <Widget label="Foto hari ini" value={summary.photos_today} tone="today" />
                    <Widget
                        label="Foto tersimpan"
                        value={summary.photos}
                        detail={formatBytes(summary.storage_bytes)}
                        href={route('captures.index')}
                        tone="stored"
                    />
                </div>

                <div className="mt-8 grid gap-8 lg:grid-cols-2">
                    <section>
                        <h3 className="text-sm font-medium text-gray-500">Kamera terpasang</h3>
                        {cameras.length === 0 ? (
                            <p className="mt-4 text-sm text-gray-600">Belum ada kamera.</p>
                        ) : (
                            <ul className="mt-2 divide-y divide-gray-200 border-y border-gray-200 bg-white">
                                {cameras.map((camera) => (
                                    <li key={camera.id} className="flex items-baseline justify-between gap-4 px-4 py-3 text-sm">
                                        <div>
                                            <p className="font-medium text-gray-900">{camera.name}</p>
                                            <p className="text-gray-500">{camera.location || 'Lokasi belum diisi'}</p>
                                        </div>
                                        <div className="text-right text-gray-500">
                                            <p>{camera.photos} foto</p>
                                            <p>{formatWhen(camera.last_seen_at)}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section>
                        <h3 className="text-sm font-medium text-gray-500">Foto terbaru</h3>
                        {recent.length === 0 ? (
                            <p className="mt-4 text-sm text-gray-600">Belum ada foto dari sensor PIR.</p>
                        ) : (
                            <div className="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                {recent.map((capture) => (
                                    <Link key={capture.id} href={route('captures.index')} className="overflow-hidden border border-gray-200 bg-white">
                                        <Thumb capture={capture} />
                                        <span className="block px-2 py-2 text-xs text-gray-600">
                                            <span className="block truncate font-medium text-gray-900">{capture.device_name || 'Kamera'}</span>
                                            {formatWhen(capture.captured_at)}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
