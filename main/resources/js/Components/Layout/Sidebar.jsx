import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';

const IconRenderer = ({ icon, className = "w-5 h-5" }) => {
    if (!icon) return null;

    // If it's a FontAwesome class
    if (icon.startsWith('fa')) {
        return <i className={`${icon} ${className} flex items-center justify-center`} />;
    }

    // If it's an SVG path
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={icon} />
        </svg>
    );
};

// Helper to determine if a URL should use Inertia
const isInertiaRoute = (url) => {
    if (!url) return false;
    // Allow Inertia for ALL beta routes
    return url.includes('/beta/');
};

const NavItem = ({ item, url, onClose }) => {
    const [isOpen, setIsOpen] = useState(false);
    const hasChildren = item.children && item.children.length > 0;
    
    // Fix: Use exact match or path segment boundary to prevent false positives
    // For example: /beta/profile should NOT match /beta/ (only /beta/profile or /beta/something)
    const isActive = item.url !== '#' && (url === item.url || url.startsWith(item.url + '/'));
    const isChildActive = hasChildren && item.children.some(child => 
        child.url !== '#' && (url === child.url || url.startsWith(child.url + '/'))
    );

    React.useEffect(() => {
        if (isChildActive) {
            setIsOpen(true);
        }
    }, [isChildActive]);

    const baseClasses = "flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors w-full";
    const activeClasses = "bg-[#2b3139] text-[#0ecb81]";
    const inactiveClasses = "text-[#848e9c] hover:bg-[#2b3139] hover:text-[#eaecef]";

    if (hasChildren) {
        return (
            <div>
                <button
                    onClick={() => setIsOpen(!isOpen)}
                    className={`${baseClasses} ${isActive || isChildActive ? activeClasses : inactiveClasses} justify-between`}
                >
                    <div className="flex items-center">
                        <IconRenderer icon={item.icon} className="w-5 h-5 mr-3" />
                        {item.label}
                    </div>
                    <svg
                        className={`w-4 h-4 transition-transform ${isOpen || isChildActive ? 'rotate-180' : ''}`}
                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                {(isOpen || isChildActive) && (
                    <div className="mt-1 ml-4 space-y-1 border-l border-[#2b3139]">
                        {item.children.map((child, idx) => {
                            const childUrl = child.url;
                            const shouldUseInertia = isInertiaRoute(childUrl);
                            // Fix: Use exact match or path segment boundary for child items too
                            const isChildItemActive = childUrl !== '#' && (url === childUrl || url.startsWith(childUrl + '/'));
                            const childClasses = `flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors ${isChildItemActive ? 'text-[#0ecb81]' : 'text-[#848e9c] hover:text-[#eaecef]'}`;

                            if (shouldUseInertia) {
                                return (
                                    <Link
                                        key={idx}
                                        href={childUrl}
                                        className={childClasses}
                                        onClick={onClose}
                                    >
                                        <IconRenderer icon={child.icon} className="w-4 h-4 mr-3" />
                                        {child.label}
                                    </Link>
                                );
                            }

                            return (
                                <a
                                    key={idx}
                                    href={childUrl}
                                    className={childClasses}
                                    onClick={onClose}
                                >
                                    <IconRenderer icon={child.icon} className="w-4 h-4 mr-3" />
                                    {child.label}
                                </a>
                            );
                        })}
                    </div>
                )}
            </div>
        );
    }

    const shouldUseInertia = isInertiaRoute(item.url);

    if (shouldUseInertia) {
        return (
            <Link
                href={item.url}
                className={`${baseClasses} ${isActive ? activeClasses : inactiveClasses}`}
                onClick={onClose}
            >
                <IconRenderer icon={item.icon} className="w-5 h-5 mr-3" />
                {item.label}
            </Link>
        );
    }

    return (
        <a
            href={item.url}
            className={`${baseClasses} ${isActive ? activeClasses : inactiveClasses}`}
            onClick={onClose}
        >
            <IconRenderer icon={item.icon} className="w-5 h-5 mr-3" />
            {item.label}
        </a>
    );
};

export const Sidebar = ({ className = '', onClose }) => {
    const { url, props } = usePage();
    const menu = props.menu || {};
    const general = props.general || {};
    const routes = props.routes || {};

    return (
        <aside className={`w-64 bg-[#0b0e11] border-r border-[#2b3139] flex-col h-screen sticky top-0 ${className}`}>
            <div className="h-16 flex items-center px-6 border-b border-[#2b3139]">
                <Link href={routes.dashboard || "/user/dashboard"} className="flex items-center">
                    {general.logo ? (
                        <img src={general.logo} alt={general.name} className="h-8 w-auto mr-2" />
                    ) : (
                        <span className="text-xl font-bold text-[#eaecef]">
                            AlgoExpert<span className="text-[#0ecb81]">Hub</span>
                        </span>
                    )}
                </Link>
            </div>

            <nav className="flex-1 p-4 space-y-6 overflow-y-auto">
                {Object.entries(menu).map(([groupKey, group]) => (
                    <div key={groupKey} className="space-y-1">
                        <div className="px-4 text-[10px] font-bold text-[#848e9c] uppercase tracking-widest mb-2 flex items-center">
                            <IconRenderer icon={group.icon} className="w-3 h-3 mr-2 opacity-50" />
                            {group.label}
                        </div>
                        {group.items.map((item, idx) => (
                            <NavItem key={idx} item={item} url={url} />
                        ))}
                    </div>
                ))}
            </nav>

            <div className="p-4 border-t border-[#2b3139]">
                <Link
                    href="/user/logout"
                    method="post"
                    as="button"
                    className="flex items-center px-4 py-3 text-sm font-medium text-[#848e9c] hover:bg-red-500/10 hover:text-red-500 rounded-lg transition-colors w-full"
                >
                    <svg className="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Logout
                </Link>
            </div>
        </aside>
    );
};
