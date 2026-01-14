import React from 'react';
import { Sidebar } from './Sidebar';
import { Header } from './Header';
import { MobileBottomMenu } from './MobileBottomMenu';

export const AppLayout = ({ children }) => {
    // Initialize sidebar as open on desktop
    const [isSidebarOpen, setIsSidebarOpen] = React.useState(true);

    const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);

    return (
        <div className="flex h-screen bg-[#0b0e11]">
            <Sidebar
                className={`transition-all duration-300 ${isSidebarOpen ? 'w-64 translate-x-0' : 'w-0 -translate-x-full md:w-0 md:translate-x-0 overflow-hidden'} md:static fixed inset-y-0 left-0 z-50 shadow-xl md:shadow-none`}
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
