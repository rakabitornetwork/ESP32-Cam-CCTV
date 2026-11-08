import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    onDark = false,
    className = '',
    children,
    ...props
}) {
    const tone = onDark
        ? active
            ? 'border-white text-white focus:border-white'
            : 'border-transparent text-teal-100 hover:border-white/50 hover:text-white focus:border-white/50 focus:text-white'
        : active
            ? 'border-brand-700 text-gray-900 focus:border-brand-700'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:border-gray-300 focus:text-gray-700';

    return (
        <Link
            {...props}
            className={
                'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none ' +
                tone +
                className
            }
        >
            {children}
        </Link>
    );
}
