import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, X } from 'lucide-react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const links = [
        { name: 'Dasbor', route: 'dashboard', active: route().current('dashboard') },
        { name: 'Kamera', route: 'devices.index', active: route().current('devices.index') },
        { name: 'Galeri', route: 'captures.index', active: route().current('captures.index') },
        ...(user?.is_admin
            ? [{ name: 'Pembaruan', route: 'admin.update', active: route().current('admin.update') }]
            : []),
    ];

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="nav-bar relative border-b border-teal-950/40">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href={route('dashboard')}>
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-white" />
                                </Link>
                            </div>
                            <div className="hidden sm:-my-px sm:ms-8 sm:flex sm:gap-6">
                                {links.map((item) => (
                                    <NavLink key={item.route} href={route(item.route)} active={item.active} onDark>
                                        {item.name}
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
                                            className="inline-flex items-center gap-2 rounded-md border border-transparent bg-transparent px-3 py-2 text-sm font-medium text-teal-50 hover:text-white focus:outline-none"
                                        >
                                            {user.name}
                                            <ChevronDown className="h-4 w-4" />
                                        </button>
                                    </span>
                                </Dropdown.Trigger>

                                <Dropdown.Content contentClasses="bg-white">
                                    <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                    <Dropdown.Link href={route('logout')} method="post" as="button" danger>
                                        Keluar
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                type="button"
                                aria-expanded={showingNavigationDropdown}
                                aria-controls="menu-ponsel"
                                onClick={() => setShowingNavigationDropdown((open) => !open)}
                                className="inline-flex items-center justify-center rounded-md p-2 text-teal-100 hover:bg-white/10 hover:text-white focus:outline-none"
                            >
                                <span className="relative block h-6 w-6">
                                    <Menu
                                        className={`absolute inset-0 h-6 w-6 transition-opacity duration-200 ${
                                            showingNavigationDropdown ? 'opacity-0' : 'opacity-100'
                                        }`}
                                    />
                                    <X
                                        className={`absolute inset-0 h-6 w-6 transition-opacity duration-200 ${
                                            showingNavigationDropdown ? 'opacity-100' : 'opacity-0'
                                        }`}
                                    />
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    id="menu-ponsel"
                    className={`absolute inset-x-0 top-full z-20 grid bg-[#06302e] transition-[grid-template-rows] duration-300 ease-in-out sm:hidden ${
                        showingNavigationDropdown ? 'pointer-events-auto grid-rows-[1fr]' : 'pointer-events-none grid-rows-[0fr]'
                    }`}
                >
                    <div className="overflow-hidden" inert={showingNavigationDropdown ? undefined : true}>
                        <div className="space-y-1 pb-3 pt-2">
                            {links.map((item) => (
                                <ResponsiveNavLink key={item.route} href={route(item.route)} active={item.active} onDark>
                                    {item.name}
                                </ResponsiveNavLink>
                            ))}
                        </div>
                        <div className="border-t border-white/15 pt-4">
                            <div className="px-4">
                                <div className="text-base font-medium text-white">{user.name}</div>
                                <div className="text-sm font-medium text-teal-100">{user.email}</div>
                            </div>
                            <div className="mt-3">
                                <ResponsiveNavLink href={route('profile.edit')} active={route().current('profile.edit')} onDark>
                                    Profile
                                </ResponsiveNavLink>
                                <ResponsiveNavLink method="post" href={route('logout')} as="button" danger>
                                    Keluar
                                </ResponsiveNavLink>
                            </div>
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
