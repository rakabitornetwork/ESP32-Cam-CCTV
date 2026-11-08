import { Link } from '@inertiajs/react';
import L from 'leaflet';
import { MapContainer, Marker, Popup, TileLayer, useMap, useMapEvents } from 'react-leaflet';
import { useEffect } from 'react';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

const markerIcon = L.icon({
    iconUrl,
    iconRetinaUrl,
    shadowUrl,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

const indonesia = [-2.5, 118];

function FitBounds({ points }) {
    const map = useMap();

    useEffect(() => {
        if (points.length > 1) {
            map.fitBounds(points, { padding: [24, 24] });
        }
    }, [map, points]);

    return null;
}

function PickHandler({ onPick }) {
    useMapEvents({
        click(event) {
            onPick(Number(event.latlng.lat.toFixed(7)), Number(event.latlng.lng.toFixed(7)));
        },
    });

    return null;
}

export default function DeviceMap({ devices = [], latitude = null, longitude = null, onPick = null }) {
    const placed = devices.filter((device) => device.latitude != null && device.longitude != null);
    const points = placed.map((device) => [device.latitude, device.longitude]);
    const selected = latitude != null && longitude != null ? [Number(latitude), Number(longitude)] : null;
    const center = selected ?? (points.length === 1 ? points[0] : indonesia);
    const zoom = selected || points.length === 1 ? 16 : 5;

    return (
        <MapContainer center={center} zoom={zoom} className="h-112 w-full rounded-lg border border-gray-200" scrollWheelZoom>
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            <FitBounds points={selected ? [] : points} />
            {onPick && <PickHandler onPick={onPick} />}
            {selected && <Marker position={selected} icon={markerIcon} />}
            {!onPick &&
                placed.map((device) => (
                    <Marker key={device.id} position={[device.latitude, device.longitude]} icon={markerIcon}>
                        <Popup>
                            <div className="space-y-1">
                                <div className="font-medium">{device.name}</div>
                                <div>{device.location || 'Tanpa lokasi'}</div>
                                <Link href={route('devices.show', device.id)} className="text-brand-700 underline">
                                    Detail perangkat
                                </Link>
                            </div>
                        </Popup>
                    </Marker>
                ))}
        </MapContainer>
    );
}
