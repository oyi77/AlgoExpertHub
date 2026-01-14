import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function ExecutionLog({ title, activeTab, tradingManagementEnabled, stats, openPositions, closedPositions, recentExecutions }) {
    const tabs = [
        { key: 'open-positions', label: 'Open Positions', count: stats?.open_positions },
        { key: 'closed-positions', label: 'Closed Positions', count: stats?.closed_positions },
        { key: 'executions', label: 'Executions', count: stats?.today_executions },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Execution Log'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Execution Log</h1>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#eaecef]">{stats?.active_connections || 0}</div>
                        <div className="text-sm text-[#848e9c]">Active Connections</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#0ecb81]">{stats?.open_positions || 0}</div>
                        <div className="text-sm text-[#848e9c]">Open Positions</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#3b82f6]">{stats?.today_executions || 0}</div>
                        <div className="text-sm text-[#848e9c]">Today's Executions</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#f59e0b]">${stats?.today_pnl || 0}</div>
                        <div className="text-sm text-[#848e9c]">Today's P&L</div>
                    </CardContent>
                </Card>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-0">
                    {!tradingManagementEnabled ? (
                        <div className="p-6 text-center text-[#848e9c]">
                            Trading management addon is not enabled.
                        </div>
                    ) : (
                        <div className="p-6 text-center text-[#848e9c]">
                            {activeTab === 'open-positions' && (openPositions?.length > 0 ? (
                                <div className="divide-y divide-[#2b3139]">
                                    {openPositions.map((pos) => (
                                        <div key={pos.id} className="py-3 flex justify-between">
                                            <span>{pos.symbol}</span>
                                            <span className={pos.pnl >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}>{pos.pnl}</span>
                                        </div>
                                    ))}
                                </div>
                            ) : 'No open positions')}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
