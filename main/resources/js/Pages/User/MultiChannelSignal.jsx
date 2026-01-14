import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function MultiChannelSignal({ title, activeTab, multiChannelEnabled }) {
    const tabs = [
        { key: 'all-signals', label: 'All Signals' },
        { key: 'signal-sources', label: 'Signal Sources' },
        { key: 'channel-forwarding', label: 'Channel Forwarding' },
        { key: 'signal-review', label: 'Signal Review' },
        { key: 'analytics', label: 'Analytics' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Multi-Channel Signal'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Multi-Channel Signal</h1>
            </div>

            <div className="flex gap-2 mb-6 overflow-x-auto">
                {tabs.map((tab) => (
                    <a
                        key={tab.key}
                        href={`/user/beta/trading/multi-channel-signal?tab=${tab.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
                            activeTab === tab.key
                                ? 'bg-[#3b82f6] text-white'
                                : 'bg-[#1e2329] text-[#848e9c] hover:text-[#eaecef] hover:bg-[#2b3139]'
                        }`}
                    >
                        {tab.label}
                    </a>
                ))}
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-6">
                    {!multiChannelEnabled ? (
                        <div className="text-center text-[#848e9c]">
                            Multi-channel signal features are not available.
                        </div>
                    ) : (
                        <div className="text-center text-[#848e9c]">
                            Content for {activeTab} tab will be displayed here.
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
