import React from 'react';
import { Menu, Bell, User } from 'lucide-react';

interface HeaderProps {
    onToggleSidebar: () => void;
}

export function Header({ onToggleSidebar }: HeaderProps) {
    return (
        <header className="h-16 bg-card border-b border-border flex items-center justify-between px-6">
            <button
                onClick={onToggleSidebar}
                className="p-2 rounded-md hover:bg-accent hover:text-accent-foreground focus:outline-none"
            >
                <Menu className="h-6 w-6" />
            </button>

            <div className="flex items-center space-x-4">
                <button className="p-2 rounded-full hover:bg-accent">
                    <Bell className="h-5 w-5 text-muted-foreground" />
                </button>
                <div className="flex items-center space-x-2">
                    <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                        <User className="h-5 w-5 text-primary" />
                    </div>
                    <span className="text-sm font-medium">Admin</span>
                </div>
            </div>
        </header>
    );
}
