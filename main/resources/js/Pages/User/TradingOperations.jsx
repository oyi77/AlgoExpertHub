import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

// Trading Bots Tab Component
const TradingBotsTab = ({ bots }) => {
    if (!bots || bots.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-robot la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Trading Bots</h4>
                <p className="text-sm mb-6">You haven't created any trading bots yet.</p>
                <Button className="bg-[#3b82f6] hover:bg-[#2563eb]">
                    <i className="las la-plus mr-2"></i>
                    Create Trading Bot
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {bots.data?.map((bot) => (
                <Card key={bot.id} className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4">
                        <div className="flex justify-between items-start">
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h4 className="font-medium text-[#eaecef]">{bot.name}</h4>
                                    <span className={`px-2 py-1 text-xs rounded ${bot.is_active ? 'bg-[#0ecb81] text-white' : 'bg-[#f6465d] text-white'}`}>
                                        {bot.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                    {bot.is_paper_trading && (
                                        <span className="px-2 py-1 text-xs bg-[#f0b90b] text-black rounded">
                                            Paper Trading
                                        </span>
                                    )}
                                </div>
                                {bot.description && (
                                    <p className="text-sm text-[#848e9c] mb-3">{bot.description}</p>
                                )}
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span className="text-[#848e9c]">Executions:</span>
                                        <span className="ml-2 text-[#eaecef]">{bot.total_executions || 0}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Success:</span>
                                        <span className="ml-2 text-[#0ecb81]">{bot.successful_executions || 0}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Failed:</span>
                                        <span className="ml-2 text-[#f6465d]">{bot.failed_executions || 0}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Win Rate:</span>
                                        <span className="ml-2 text-[#eaecef]">{bot.win_rate ? `${bot.win_rate}%` : 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-2 ml-4">
                                <Button size="sm" className="bg-[#0ecb81] hover:bg-[#00a878]">
                                    <i className="las la-play"></i>
                                </Button>
                                <Button size="sm" variant="outline">
                                    <i className="las la-cog"></i>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}
            
            {bots.links && (
                <div className="flex justify-center mt-6">
                    <div className="flex gap-2">
                        {bots.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url}
                                className={`px-3 py-2 text-sm rounded ${
                                    link.active
                                        ? 'bg-[#3b82f6] text-white'
                                        : 'bg-[#1e2329] text-[#848e9c] hover:bg-[#2b3139] hover:text-[#eaecef]'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

// Connections Tab Component
const ConnectionsTab = () => {
    return (
        <div className="text-center text-[#848e9c] py-8">
            <i className="las la-plug la-3x text-muted mb-4"></i>
            <h4 className="text-lg font-medium text-[#eaecef] mb-2">Connections</h4>
            <p className="text-sm">Exchange and data connections will be displayed here.</p>
        </div>
    );
};

// Open Positions Tab Component
const OpenPositionsTab = () => {
    return (
        <div className="text-center text-[#848e9c] py-8">
            <i className="las la-chart-line la-3x text-muted mb-4"></i>
            <h4 className="text-lg font-medium text-[#eaecef] mb-2">Open Positions</h4>
            <p className="text-sm">Your current trading positions will be displayed here.</p>
        </div>
    );
};

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
                        <div>
                            {activeTab === 'trading-bots' && (
                                <TradingBotsTab bots={bots} />
                            )}
                            {activeTab === 'connections' && (
                                <ConnectionsTab />
                            )}
                            {activeTab === 'open-positions' && (
                                <OpenPositionsTab />
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
