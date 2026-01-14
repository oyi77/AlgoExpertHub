import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function Backtesting({ title, activeTab, tradingManagementEnabled, currencyPairs, timeframes, backtests }) {
    const tabs = [
        { key: 'create', label: 'Create Backtest' },
        { key: 'results', label: 'Results' },
        { key: 'reports', label: 'Performance Reports' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Backtesting'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Backtesting Center</h1>
            </div>

            <div className="flex gap-2 mb-6 overflow-x-auto">
                {tabs.map((tab) => (
                    <a
                        key={tab.key}
                        href={`/user/beta/trading/backtesting?tab=${tab.key}`}
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
                    {!tradingManagementEnabled ? (
                        <div className="text-center text-[#848e9c]">
                            Backtesting features are not available.
                        </div>
                    ) : activeTab === 'create' ? (
                        <form className="space-y-4 max-w-xl mx-auto">
                            <div>
                                <label className="block text-sm text-[#848e9c] mb-2">Symbol</label>
                                <select className="w-full bg-[#0b0e11] border border-[#2b3139] rounded px-4 py-2 text-[#eaecef]">
                                    <option value="">Select symbol</option>
                                    {currencyPairs?.map((pair) => (
                                        <option key={pair.id} value={pair.name}>{pair.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm text-[#848e9c] mb-2">Timeframe</label>
                                <select className="w-full bg-[#0b0e11] border border-[#2b3139] rounded px-4 py-2 text-[#eaecef]">
                                    <option value="">Select timeframe</option>
                                    {timeframes?.map((tf) => (
                                        <option key={tf.id} value={tf.name}>{tf.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm text-[#848e9c] mb-2">Start Date</label>
                                    <input type="date" className="w-full bg-[#0b0e11] border border-[#2b3139] rounded px-4 py-2 text-[#eaecef]" />
                                </div>
                                <div>
                                    <label className="block text-sm text-[#848e9c] mb-2">End Date</label>
                                    <input type="date" className="w-full bg-[#0b0e11] border border-[#2b3139] rounded px-4 py-2 text-[#eaecef]" />
                                </div>
                            </div>
                            <button type="submit" className="w-full py-2 bg-[#3b82f6] text-white rounded-lg">Run Backtest</button>
                        </form>
                    ) : activeTab === 'results' && backtests?.data?.length > 0 ? (
                        <div className="divide-y divide-[#2b3139]">
                            {backtests.data.map((bt) => (
                                <div key={bt.id} className="py-3 flex justify-between">
                                    <span>{bt.name}</span>
                                    <span className="text-[#848e9c]">{bt.created_at}</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center text-[#848e9c] py-8">No backtest results yet</div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
