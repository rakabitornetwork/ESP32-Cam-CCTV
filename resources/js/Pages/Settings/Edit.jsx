import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const imageDefaults = {
    brightness: 0,
    contrast: 0,
    saturation: -1,
    sharpness: 0,
    ae_level: 0,
    wb_mode: 0,
    hmirror: false,
    vflip: false,
};

function RangeField({ id, label, min, max, value, onChange, disabled }) {
    return (
        <div>
            <div className="flex items-center justify-between">
                <InputLabel htmlFor={id} value={label} />
                <span className="text-sm tabular-nums text-gray-700">{value}</span>
            </div>
            <input
                id={id}
                type="range"
                min={min}
                max={max}
                step="1"
                value={value}
                disabled={disabled}
                onChange={(event) => onChange(Number(event.target.value))}
                className="mt-2 w-full accent-brand-600"
            />
        </div>
    );
}

function LiveCam({ deviceId }) {
    const [frame, setFrame] = useState(null);
    const [waiting, setWaiting] = useState(true);
    const frameUrl = useRef(null);

    useEffect(() => {
        if (!deviceId) {
            return undefined;
        }

        let cancelled = false;
        setWaiting(true);
        setFrame(null);

        const pull = async () => {
            try {
                const response = await window.axios.get(route('settings.preview.show', deviceId), {
                    params: { t: Date.now() },
                    responseType: 'blob',
                });
                if (cancelled || !response.data?.size) {
                    return;
                }
                const next = URL.createObjectURL(response.data);
                const intact = await new Promise((resolve) => {
                    const image = new Image();
                    image.onload = () => resolve(true);
                    image.onerror = () => resolve(false);
                    image.src = next;
                });
                if (!intact || cancelled) {
                    URL.revokeObjectURL(next);
                    return;
                }
                if (frameUrl.current) {
                    URL.revokeObjectURL(frameUrl.current);
                }
                frameUrl.current = next;
                setFrame(next);
                setWaiting(false);
            } catch {
                if (!cancelled && !frameUrl.current) {
                    setWaiting(true);
                }
            }
        };

        const pulse = () => {
            window.axios.post(route('settings.preview.pulse', deviceId)).catch(() => {});
        };

        pulse();
        pull();
        const pulseTimer = setInterval(pulse, 8000);
        const frameTimer = setInterval(pull, 1000);

        return () => {
            cancelled = true;
            clearInterval(pulseTimer);
            clearInterval(frameTimer);
            if (frameUrl.current) {
                URL.revokeObjectURL(frameUrl.current);
                frameUrl.current = null;
            }
        };
    }, [deviceId]);

    return (
        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
            <div className="flex items-center justify-between px-4 py-3">
                <h3 className="text-sm font-semibold text-gray-800">Kamera langsung</h3>
                <span className="text-xs text-gray-500">{waiting ? 'Menunggu kamera...' : 'Langsung'}</span>
            </div>
            <div className="flex aspect-[4/3] items-center justify-center bg-gray-900">
                {frame ? (
                    <img src={frame} alt="Pratinjau kamera" className="h-full w-full object-contain" />
                ) : (
                    <p className="px-6 text-center text-sm text-gray-300">Menunggu gambar dari kamera</p>
                )}
            </div>
            <p className="px-4 py-3 text-xs text-gray-500">Gambar diperbarui sekitar satu detik sekali selama halaman ini terbuka.</p>
        </div>
    );
}

export default function Edit({ retainDays, maxRetainDays, canManage, cameras }) {
    const { flash } = usePage().props;
    const retain = useForm({
        retain_days: retainDays,
    });
    const [selectedId, setSelectedId] = useState(cameras[0]?.id ?? null);
    const selected = cameras.find((camera) => camera.id === selectedId) || cameras[0] || null;
    const camera = useForm({
        image_quality: selected?.image_quality || 'standar',
        image_settings: { ...imageDefaults, ...(selected?.image_settings || {}) },
    });
    const skipSave = useRef(true);
    const selectedIdRef = useRef(selectedId);
    selectedIdRef.current = selectedId;

    useEffect(() => {
        if (!canManage || !selectedIdRef.current) {
            return undefined;
        }
        if (skipSave.current) {
            skipSave.current = false;
            return undefined;
        }
        const timer = setTimeout(() => {
            camera.put(route('settings.camera', selectedIdRef.current), {
                preserveScroll: true,
                preserveState: true,
            });
        }, 400);

        return () => clearTimeout(timer);
    }, [camera.data.image_quality, camera.data.image_settings]);

    const setSetting = (key, value) => {
        camera.setData((current) => ({
            ...current,
            image_settings: { ...current.image_settings, [key]: value },
        }));
    };

    const saveRetain = (event) => {
        event.preventDefault();
        retain.put(route('settings.update'));
    };

    const saveCamera = (event) => {
        event.preventDefault();
        if (!selected) {
            return;
        }
        camera.put(route('settings.camera', selected.id), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Pengaturan</h2>}>
            <Head title="Pengaturan" />
            <div className="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
                {flash?.status && <p className="text-sm text-brand-700">{flash.status}</p>}
                {selected ? (
                    <div className="grid items-start gap-6 lg:grid-cols-2">
                        <LiveCam deviceId={selected.id} />
                        <form onSubmit={saveCamera} className="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                            {cameras.length > 1 && (
                                <div>
                                    <InputLabel htmlFor="camera" value="Kamera" />
                                    <select
                                        id="camera"
                                        value={selected.id}
                                        onChange={(event) => {
                                            const next = cameras.find((item) => item.id === Number(event.target.value));
                                            if (!next) {
                                                return;
                                            }
                                            skipSave.current = true;
                                            setSelectedId(next.id);
                                            camera.setData({
                                                image_quality: next.image_quality || 'standar',
                                                image_settings: { ...imageDefaults, ...(next.image_settings || {}) },
                                            });
                                        }}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    >
                                        {cameras.map((item) => (
                                            <option key={item.id} value={item.id}>{item.name}</option>
                                        ))}
                                    </select>
                                </div>
                            )}
                            <div>
                                <InputLabel htmlFor="image_quality" value="Kualitas gambar" />
                                <select
                                    id="image_quality"
                                    value={camera.data.image_quality}
                                    disabled={!canManage}
                                    onChange={(event) => camera.setData('image_quality', event.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                >
                                    <option value="hemat">Hemat — berkas lebih kecil</option>
                                    <option value="standar">Standar</option>
                                    <option value="tajam">Tajam — lebih detail</option>
                                </select>
                                <p className="mt-2 text-xs text-gray-500">Menentukan ukuran foto. Kamera memakai pilihan ini tanpa flash ulang.</p>
                                <InputError message={camera.errors.image_quality} className="mt-2" />
                            </div>
                            <div className="space-y-4 border-t border-gray-100 pt-4">
                                <div>
                                    <h3 className="text-sm font-semibold text-gray-800">Gambar khusus</h3>
                                    <p className="mt-1 text-xs text-gray-500">Geser dari −2 sampai 2. Perubahan dikirim ke kamera, dan hasilnya muncul di kamera langsung.</p>
                                </div>
                                <RangeField id="brightness" label="Kecerahan" min={-2} max={2} value={camera.data.image_settings.brightness} disabled={!canManage} onChange={(value) => setSetting('brightness', value)} />
                                <RangeField id="contrast" label="Kontras" min={-2} max={2} value={camera.data.image_settings.contrast} disabled={!canManage} onChange={(value) => setSetting('contrast', value)} />
                                <RangeField id="saturation" label="Saturasi warna" min={-2} max={2} value={camera.data.image_settings.saturation} disabled={!canManage} onChange={(value) => setSetting('saturation', value)} />
                                <RangeField id="sharpness" label="Ketajaman" min={-2} max={2} value={camera.data.image_settings.sharpness} disabled={!canManage} onChange={(value) => setSetting('sharpness', value)} />
                                <RangeField id="ae_level" label="Kompensasi cahaya" min={-2} max={2} value={camera.data.image_settings.ae_level} disabled={!canManage} onChange={(value) => setSetting('ae_level', value)} />
                                <div>
                                    <InputLabel htmlFor="wb_mode" value="Keseimbangan putih" />
                                    <select
                                        id="wb_mode"
                                        value={camera.data.image_settings.wb_mode}
                                        disabled={!canManage}
                                        onChange={(event) => setSetting('wb_mode', Number(event.target.value))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    >
                                        <option value={0}>Otomatis</option>
                                        <option value={1}>Cerah</option>
                                        <option value={2}>Mendung</option>
                                        <option value={3}>Kantor</option>
                                        <option value={4}>Rumah</option>
                                    </select>
                                </div>
                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <Checkbox checked={camera.data.image_settings.hmirror} disabled={!canManage} onChange={(event) => setSetting('hmirror', event.target.checked)} />
                                    Cermin kiri-kanan
                                </label>
                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <Checkbox checked={camera.data.image_settings.vflip} disabled={!canManage} onChange={(event) => setSetting('vflip', event.target.checked)} />
                                    Balik atas-bawah
                                </label>
                                <InputError message={camera.errors['image_settings.brightness'] || camera.errors.image_settings} className="mt-2" />
                            </div>
                            {canManage && (
                                <PrimaryButton disabled={camera.processing}>
                                    {camera.processing ? 'Menyimpan...' : 'Simpan'}
                                </PrimaryButton>
                            )}
                        </form>
                    </div>
                ) : (
                    <div className="rounded-lg bg-white p-6 text-sm text-gray-600 shadow-sm">Belum ada kamera.</div>
                )}
                <form onSubmit={saveRetain} className="max-w-xl space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <InputLabel htmlFor="retain_days" value="Masa simpan (hari)" />
                    <TextInput
                        id="retain_days"
                        type="number"
                        min="1"
                        max={maxRetainDays}
                        value={retain.data.retain_days}
                        className="mt-1 block w-full"
                        disabled={!canManage}
                        onChange={(event) => retain.setData('retain_days', event.target.value)}
                    />
                    <p className="mt-2 text-xs text-gray-500">Maksimum paket saat ini: {maxRetainDays} hari.</p>
                    <InputError message={retain.errors.retain_days} className="mt-2" />
                    {canManage && (
                        <PrimaryButton disabled={retain.processing}>Simpan</PrimaryButton>
                    )}
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
