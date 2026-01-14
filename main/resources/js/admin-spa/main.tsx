import React from 'react';
import ReactDOM from 'react-dom/client';
import { Routes, Route } from 'react-router-dom';
import { Providers } from './providers';
import { AdminLayout } from './components/layout/admin-layout';
import './globals.css';

const Dashboard = () => <div className="p-4"><h2 className="text-2xl font-bold">Dashboard</h2><p>Welcome to the new React Admin!</p></div>;
const Signals = () => <div className="p-4"><h2 className="text-2xl font-bold">Signals</h2><p>Signal management coming soon...</p></div>;

const rootElement = document.getElementById('admin-spa-root');

if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(
        <React.StrictMode>
            <Providers>
                <AdminLayout>
                    <Routes>
                        <Route path="/" element={<Dashboard />} />
                        <Route path="/dashboard" element={<Dashboard />} />
                        <Route path="/signals" element={<Signals />} />
                        <Route path="*" element={<div className="p-4">404: Page Not Found</div>} />
                    </Routes>
                </AdminLayout>
            </Providers>
        </React.StrictMode>
    );
}
