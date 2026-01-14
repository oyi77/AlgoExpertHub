import React from 'react';
import { Sidebar } from './Sidebar';
import { Header } from './Header';
import { MobileBottomMenu } from './MobileBottomMenu';

export const AppLayout = ({ children }) => {
    const [isSidebarOpen, setIsSidebarOpen] = React.useState(false);

    const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);

    return (
        <div className="flex h-screen bg-[#0b0e11]">
            <Sidebar
                className={`hidden md:flex ${isSidebarOpen ? '!flex fixed inset-y-0 left-0 z-50 w-64 shadow-xl' : ''}`}
                onClose={() => setIsSidebarOpen(false)}
            />

            {/* Overlay for mobile sidebar */}
            {isSidebarOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-40 md:hidden"
                    onClick={() => setIsSidebarOpen(false)}
                />
            )}

            <div className="flex-1 flex flex-col overflow-hidden">
                <Header onToggleSidebar={toggleSidebar} />

                <main className="flex-1 overflow-y-auto p-6 pb-24 md:pb-6">
                    {children}
                </main>

                <div className="md:hidden">
                    <MobileBottomMenu onToggleSidebar={toggleSidebar} />
                </div>
            </div>
        </div>
    );
};
