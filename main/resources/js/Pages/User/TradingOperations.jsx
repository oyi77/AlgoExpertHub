import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function TradingOperations({ title, activeTab, tradingManagementEnabled, bots }) {
    const tabs = [
        { key: 'trading-bots', label: 'Trading Bots' },
        { key: 'connections', label: 'Connections' },
        { key: 'open-positions', label: 'Open Positions' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Trading Operations'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Trading Operations</h1>
            </div>

            <div className="flex gap-2 mb-6 overflow-x-auto">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={`/beta/trading/operations?tab=${tab.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${activeTab === tab.key
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
                            <p>Trading management features are not available.</p>
                        </div>
                    ) : (
                        <div className="text-center text-[#848e9c]">
                            <p>Trading operations content for tab: {activeTab}</p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
