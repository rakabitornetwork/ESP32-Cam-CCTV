import { Head, Link } from '@inertiajs/react';
import { Camera, MapPinned, ShieldCheck } from 'lucide-react';

export default function Welcome({ auth, canLogin, canRegister }) {
    return (
        <>
            <Head title="Beranda" />
            <div className="min-h-screen bg-gray-50 text-gray-900">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <div className="text-lg font-semibold">CCTVku</div>
                    <nav className="flex items-center gap-3 text-sm">
                        {auth.user ? (
                            <Link href={route('dashboard')} className="rounded-md bg-brand-600 px-4 py-2 text-white">
                                Dasbor
                            </Link>
                        ) : (
                            <>
                                {canLogin && (
                                    <Link href={route('login')} className="px-3 py-2">
                                        Masuk
                                    </Link>
                                )}
                                {canRegister && (
                                    <Link href={route('register')} className="rounded-md bg-brand-600 px-4 py-2 text-white">
                                        Daftar
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>
                <main className="mx-auto grid max-w-6xl gap-8 px-6 py-16 lg:grid-cols-2">
                    <div className="space-y-6">
                        <h1 className="text-4xl font-semibold tracking-tight">Penyimpanan cloud untuk kamera dan ESP32-CAM</h1>
                        <p className="text-lg text-gray-600">
                            Simpan rekaman dan foto gerak dari sensor PIR, pantau posisi kamera di peta, dan kelola kuota sesuai paket.
                        </p>
                    </div>
                    <div className="grid gap-4">
                        {[
                            [Camera, 'Foto gerak PIR masuk sebagai JPEG lewat token perangkat.'],
                            [MapPinned, 'Setiap kamera bisa ditandai di peta OpenStreetMap.'],
                            [ShieldCheck, 'Kuota, masa simpan, dan jumlah perangkat mengikuti paket.'],
                        ].map(([Icon, text]) => (
                            <div key={text} className="flex gap-3 rounded-xl border border-gray-200 bg-white p-4">
                                <Icon className="mt-0.5 h-5 w-5 text-brand-700" />
                                <p>{text}</p>
                            </div>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
