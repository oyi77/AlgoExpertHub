import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function TradingConfiguration({ title, activeTab, tradingManagementEnabled }) {
    const tabs = [
        { key: 'data-connections', label: 'Data Connections' },
        { key: 'risk-presets', label: 'Risk Presets' },
        { key: 'smart-risk', label: 'Smart Risk' },
        { key: 'filter-strategies', label: 'Filter Strategies' },
        { key: 'ai-profiles', label: 'AI Profiles' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Trading Configuration'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Trading Configuration</h1>
            </div>

            <div className="flex gap-2 mb-6 overflow-x-auto">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={`/beta/trading/configuration?tab=${tab.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
                            activeTab === tab.key
                                ? 'bg-[#3b82f6] text-white'
                                : 'bg-[#1e2329] text-[#848e9c] hover:text-[#eaecef] hover:bg-[#2b3139]'
                        }`}
                    >
                        {tab.label}
                    </Link>
                ))}
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-6">
                    {!tradingManagementEnabled ? (
                        <div className="text-center text-[#848e9c]">
                            Trading management features are not available.
                        </div>
                    ) : (
                        <div className="text-center text-[#848e9c]">
                            Configuration for {activeTab} will be displayed here.
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
