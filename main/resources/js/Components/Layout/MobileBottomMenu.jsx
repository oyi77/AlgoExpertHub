import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export const MobileBottomMenu = ({ onToggleSidebar }) => {
    const { url, props } = usePage();
    const routes = props.routes || {};

    // Helper to check active state
    const isActive = (path) => {
        if (!path) return false;
        return url.includes(path);
    };

    return (
        <div className="mobile-bottom-menu-wrapper">
            <ul className="mobile-bottom-menu">
                {/* Dashboard */}
                <li>
                    <Link
                        href="/user/beta/dashboard"
                        className={isActive('/beta/dashboard') ? 'active' : ''}
                    >
                        <i className="fas fa-home"></i>
                        <span>Overview</span>
                    </Link>
                </li>

                {/* Terminal */}
                <li>
                    <Link
                        href="/user/beta/terminal"
                        className={isActive('/beta/terminal') ? 'active' : ''}
                    >
                        <i className="fas fa-chart-line"></i>
                        <span>Trade</span>
                    </Link>
                </li>

                {/* Bots */}
                <li>
                    <Link
                        href="/user/beta/trading/operations"
                        className={isActive('/beta/trading/operations') ? 'active' : ''}
                    >
                        <i className="fas fa-robot"></i>
                        <span>Bots</span>
                    </Link>
                </li>

                {/* Signals */}
                <li>
                    <Link
                        href="/user/beta/signals"
                        className={isActive('/beta/signals') ? 'active' : ''}
                    >
                        <i className="fas fa-signal"></i>
                        <span>Signals</span>
                    </Link>
                </li>

                {/* More - Toggles Sidebar */}
                <li className="sidebar-open-btn">
                    <button onClick={(e) => { e.preventDefault(); onToggleSidebar(); }} className="w-full h-full flex flex-col items-center justify-center text-[#848e9c]">
                        <i className="fas fa-bars"></i>
                        <span>More</span>
                    </button>
                </li>
            </ul>
        </div>
    );
};
