import { Link } from '@inertiajs/react';

const items = [
    { name: 'Ringkasan', route: 'admin.dashboard' },
    { name: 'Pembaruan', route: 'admin.update' },
];

export default function AdminNav() {
    return (
        <div className="mb-6 flex gap-2">
            {items.map((item) => {
                const active = route().current(item.route);

                return (
                    <Link
                        key={item.route}
                        href={route(item.route)}
                        className={`rounded-md px-3 py-2 text-sm font-medium ${
                            active ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 shadow-sm'
                        }`}
                    >
                        {item.name}
                    </Link>
                );
            })}
        </div>
    );
}
