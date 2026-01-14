import React from 'react';
import { cn } from '@/lib/utils';
import { LayoutDashboard, Users, Activity, Settings, BarChart2 } from 'lucide-react';

interface SidebarProps {
    isOpen: boolean;
}

export function Sidebar({ isOpen }: SidebarProps) {
    const menuItems = [
        { icon: LayoutDashboard, label: 'Dashboard', href: '/admin/app/dashboard' },
        { icon: Activity, label: 'Signals', href: '/admin/app/signals' },
        { icon: Users, label: 'Users', href: '/admin/app/users' },
        { icon: BarChart2, label: 'Trading', href: '/admin/app/trading' },
        { icon: Settings, label: 'Settings', href: '/admin/app/settings' },
    ];

    return (
        <aside
            className={cn(
                "bg-card border-r border-border transition-all duration-300 flex flex-col",
                isOpen ? "w-64" : "w-16"
            )}
        >
            <div className="h-16 flex items-center justify-center border-b border-border">
                <span className={cn("font-bold text-xl text-primary", !isOpen && "hidden")}>
                    AlgoExpertHub
                </span>
                <span className={cn("font-bold text-xl text-primary", isOpen && "hidden")}>
                    AH
                </span>
            </div>

            <nav className="flex-1 py-4 space-y-1 px-2">
                {menuItems.map((item) => (
                    <a
                        key={item.href}
                        href={item.href}
                        className="flex items-center px-2 py-2 text-sm font-medium rounded-md text-foreground hover:bg-accent hover:text-accent-foreground group"
                    >
                        <item.icon className="mr-3 h-5 w-5 text-muted-foreground group-hover:text-accent-foreground" />
                        <span className={cn(!isOpen && "hidden")}>{item.label}</span>
                    </a>
                ))}
            </nav>
        </aside>
    );
}
