import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Button } from '../ui/Button';

export const Header = ({ className = '', onToggleSidebar }) => {
    const { auth, title, routes } = usePage().props;

    return (
        <header className={`h-16 bg-[#0b0e11] border-b border-[#2b3139] flex items-center justify-between px-6 ${className}`}>
            <div className="flex items-center">
                {onToggleSidebar && (
                    <button
                        onClick={onToggleSidebar}
                        className="mr-4 p-2 rounded-lg hover:bg-[#2b3139] text-[#848e9c] hover:text-[#eaecef] transition-colors"
                        aria-label="Toggle sidebar"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                )}
                <h1 className="text-lg font-medium text-[#eaecef]">
                    {title || 'Dashboard'}
                </h1>
            </div>

            <div className="flex items-center space-x-4">
                <div className="text-sm text-[#848e9c]">
                    Balance: <span className="text-[#eaecef] font-mono font-medium">${auth?.user?.balance || '0.00'}</span>
                </div>

                <Link href={routes?.deposit || '#'}>
                    <Button variant="primary" size="sm">
                        Deposit
                    </Button>
                </Link>

                <div className="h-8 w-8 rounded-full bg-[#2b3139] flex items-center justify-center text-[#eaecef]">
                    {auth?.user?.username?.charAt(0).toUpperCase() || 'U'}
                </div>
            </div>
        </header>
    );
};
