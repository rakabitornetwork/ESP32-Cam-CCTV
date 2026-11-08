import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import {
    Camera,
    Cctv,
    ChevronDown,
    Clapperboard,
    CreditCard,
    LayoutDashboard,
    Map,
    Menu,
    Settings,
    Shield,
    X,
} from 'lucide-react';
import { useState } from 'react';

const links = [
    { name: 'Dasbor', route: 'dashboard', icon: LayoutDashboard },
    { name: 'Perangkat', route: 'devices.index', icon: Cctv },
    { name: 'Peta', route: 'devices.map', icon: Map },
    { name: 'Rekaman', route: 'recordings.index', icon: Clapperboard },
    { name: 'Tangkapan', route: 'captures.index', icon: Camera },
    { name: 'Langganan', route: 'billing.index', icon: CreditCard },
    { name: 'Pengaturan', route: 'settings.edit', icon: Settings },
];

function isActive(name) {
    if (name === 'devices.map') {
        return route().current('devices.map');
    }

    if (name === 'devices.index') {
        return route().current('devices.*') && !route().current('devices.map');
    }

    const prefix = name.split('.')[0];

    return route().current(name) || route().current(`${prefix}.*`);
}

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const items = user?.is_admin
        ? [...links, { name: 'Panel Admin', route: 'admin.dashboard', icon: Shield }]
        : links;

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>

                            <div className="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                                {items.map((item) => (
                                    <NavLink key={item.route} href={route(item.route)} active={isActive(item.route)}>
                                        <span className="inline-flex items-center gap-1.5">
                                            <item.icon className="h-4 w-4" />
                                            {item.name}
                                        </span>
                                    </NavLink>
                                ))}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <span className="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            className="inline-flex items-center gap-2 rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none"
                                        >
                                            {user.name}
                                            <ChevronDown className="h-4 w-4" />
                                        </button>
                                    </span>
                                </Dropdown.Trigger>

                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>Profil</Dropdown.Link>
                                    <Dropdown.Link href={route('logout')} method="post" as="button">
                                        Keluar
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                type="button"
                                onClick={() => setShowingNavigationDropdown((open) => !open)}
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
                            >
                                {showingNavigationDropdown ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
                            </button>
                        </div>
                    </div>
                </div>

                <div className={(showingNavigationDropdown ? 'block' : 'hidden') + ' sm:hidden'}>
                    <div className="space-y-1 pb-3 pt-2">
                        {items.map((item) => (
                            <ResponsiveNavLink key={item.route} href={route(item.route)} active={isActive(item.route)}>
                                {item.name}
                            </ResponsiveNavLink>
                        ))}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">{user.name}</div>
                            <div className="text-sm font-medium text-gray-500">{user.email}</div>
                        </div>
                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>Profil</ResponsiveNavLink>
                            <ResponsiveNavLink method="post" href={route('logout')} as="button">
                                Keluar
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
