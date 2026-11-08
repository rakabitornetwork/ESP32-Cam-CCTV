import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatBytes, formatWhen } from '@/lib/format';
import { DialogTitle } from '@headlessui/react';
import { Head, router } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

function angleOf(rotation) {
    return ((Number(rotation) || 0) % 360 + 360) % 360;
}

function sideways(rotation) {
    return angleOf(rotation) % 180 !== 0;
}

function CapturePhoto({ src, alt, rotation, variant }) {
    const angle = angleOf(rotation);

    if (variant === 'thumb') {
        return (
            <span className="relative block h-48 overflow-hidden bg-gray-900">
                <img
                    src={src}
                    alt={alt}
                    style={sideways(angle) ? { transform: `translate(-50%, -50%) rotate(${angle}deg)` } : { transform: `rotate(${angle}deg)` }}
                    className={
                        sideways(angle)
                            ? 'absolute left-1/2 top-1/2 h-36 w-48 max-w-none'
                            : 'h-48 w-full object-cover'
                    }
                />
            </span>
        );
    }

    if (sideways(angle)) {
        return (
            <div className="relative mx-auto h-[min(70vh,72vw)] w-[calc(min(70vh,72vw)*0.75)]">
                <img
                    src={src}
                    alt={alt}
                    style={{ transform: `translate(-50%, -50%) rotate(${angle}deg)` }}
                    className="absolute left-1/2 top-1/2 h-[calc(min(70vh,72vw)*0.75)] w-[min(70vh,72vw)] max-w-none"
                />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            style={{ transform: `rotate(${angle}deg)` }}
            className="max-h-[70vh] w-full object-contain"
        />
    );
}

export default function Index({ captures, cameras = [], camera }) {
    const [selectedId, setSelectedId] = useState(null);
    const [turning, setTurning] = useState(false);
    const [cameraOpen, setCameraOpen] = useState(false);
    const cameraMenu = useRef(null);
    const selected = captures.data.find((capture) => capture.id === selectedId) || null;
    const cameraName = cameras.find((item) => String(item.id) === String(camera))?.name || 'Semua kamera';

    useEffect(() => {
        if (!cameraOpen) {
            return undefined;
        }

        const close = (event) => {
            if (!cameraMenu.current?.contains(event.target)) {
                setCameraOpen(false);
            }
        };
        const onKey = (event) => {
            if (event.key === 'Escape') {
                setCameraOpen(false);
            }
        };

        document.addEventListener('mousedown', close);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', close);
            document.removeEventListener('keydown', onKey);
        };
    }, [cameraOpen]);

    const chooseCamera = (value) => {
        setCameraOpen(false);
        router.get(
            route('captures.index'),
            value ? { kamera: value } : {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const rotate = (direction) => {
        if (!selected) {
            return;
        }
        setTurning(true);
        router.patch(route('captures.rotate', selected.id), { direction }, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => setTurning(false),
        });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Galeri</h2>}>
            <Head title="Galeri" />
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center gap-3">
                    <label id="kamera-label" className="text-sm text-gray-500">
                        Kamera
                    </label>
                    <div ref={cameraMenu} className="relative min-w-48">
                        <button
                            type="button"
                            id="kamera"
                            aria-labelledby="kamera-label"
                            aria-haspopup="listbox"
                            aria-expanded={cameraOpen}
                            onClick={() => setCameraOpen((open) => !open)}
                            className="flex h-10 w-full items-center justify-between gap-3 border border-gray-300 bg-white px-3 text-left text-sm text-gray-900"
                        >
                            <span className="truncate">{cameraName}</span>
                            <ChevronDown className="h-4 w-4 shrink-0 text-gray-500" />
                        </button>
                        <div
                            role="listbox"
                            aria-labelledby="kamera-label"
                            className={`absolute inset-x-0 top-full z-30 mt-1 grid bg-white text-sm shadow-sm transition-[grid-template-rows] duration-200 ease-out ${
                                cameraOpen
                                    ? 'grid-rows-[1fr] border border-gray-300'
                                    : 'pointer-events-none grid-rows-[0fr] border-0'
                            }`}
                        >
                            <div className="overflow-hidden">
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={!camera}
                                    onClick={() => chooseCamera('')}
                                    className="block w-full truncate px-3 py-2 text-left hover:bg-gray-50"
                                >
                                    Semua kamera
                                </button>
                                {cameras.map((item) => (
                                    <button
                                        key={item.id}
                                        type="button"
                                        role="option"
                                        aria-selected={String(item.id) === String(camera)}
                                        onClick={() => chooseCamera(item.id)}
                                        className="block w-full truncate px-3 py-2 text-left hover:bg-gray-50"
                                    >
                                        {item.name}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
                {captures.data.length === 0 ? (
                    <p className="text-sm text-gray-600">
                        {camera ? 'Belum ada foto dari kamera ini.' : 'Belum ada foto dari sensor PIR.'}
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {captures.data.map((capture) => (
                            <button
                                key={capture.id}
                                type="button"
                                onClick={() => setSelectedId(capture.id)}
                                className="overflow-hidden rounded-lg bg-white text-left shadow-sm"
                            >
                                <CapturePhoto
                                    src={route('captures.stream', capture.id)}
                                    alt={capture.device_name}
                                    rotation={capture.rotation}
                                    variant="thumb"
                                />
                                <span className="block space-y-1 p-3 text-sm">
                                    <span className="block font-medium">{capture.device_name}</span>
                                    <span className="block text-gray-500">
                                        {formatWhen(capture.captured_at)} · {formatBytes(capture.bytes)}
                                    </span>
                                </span>
                            </button>
                        ))}
                    </div>
                )}
            </div>
            <Modal show={selected !== null} maxWidth="4xl" onClose={() => setSelectedId(null)}>
                {selected && (
                    <div>
                        <div className="flex items-start justify-between gap-4 px-6 pt-5">
                            <div>
                                <DialogTitle className="text-lg font-semibold text-gray-900">{selected.device_name}</DialogTitle>
                                <p className="mt-1 text-sm text-gray-500">
                                    {formatWhen(selected.captured_at)}
                                </p>
                            </div>
                            <button type="button" className="text-sm text-gray-500" onClick={() => setSelectedId(null)}>
                                Tutup
                            </button>
                        </div>
                        <div className="relative z-0 flex items-center justify-center overflow-hidden bg-gray-900 px-6 py-4">
                            <CapturePhoto
                                src={route('captures.stream', selected.id)}
                                alt={selected.device_name}
                                rotation={selected.rotation}
                            />
                        </div>
                        <div className="relative z-10 flex justify-end gap-3 bg-white px-6 py-4">
                            <SecondaryButton disabled={turning} onClick={() => rotate('left')}>
                                Putar kiri
                            </SecondaryButton>
                            <SecondaryButton disabled={turning} onClick={() => rotate('right')}>
                                Putar kanan
                            </SecondaryButton>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
