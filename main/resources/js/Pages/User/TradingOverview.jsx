import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function TradingOverview({ title, cards }) {
    const formatPL = (value) => {
        const isPositive = value >= 0;
        return (
            <span className={isPositive ? 'text-success' : 'text-danger'}>
                {isPositive ? '+' : ''}{typeof value === 'number' ? value.toFixed(2) : value}
            </span>
        );
    };

    if (!cards || cards.length === 0) {
        return (
            <AppLayout>
                <Head title={title || 'Trading Overview'} />

                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-[#eaecef]">Trading Overview</h1>
                    <p className="text-[#848e9c] mt-1">View and manage all your active trading setups</p>
                </div>

                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="text-center py-12">
                        <i className="las la-chart-line la-3x text-muted mb-4"></i>
                        <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Trading Setups</h4>
                        <p className="text-[#848e9c] mb-6">You don't have any active trading setups yet.</p>
                        <div className="flex gap-3 justify-center flex-wrap">
                            <Link href={route('user.execution-connections.create')} className="btn btn-primary sp_theme_btn">
                                <i className="las la-plus mr-2"></i>
                                Add Trading Connection
                            </Link>
                            <Link href={route('user.external-signals.index')} className="btn btn-outline-primary">
                                <i className="las la-signal mr-2"></i>
                                Configure Signal Sources
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={title || 'Trading Overview'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Trading Overview</h1>
                <p className="text-[#848e9c] mt-1">View and manage all your active trading setups</p>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {cards.map((card, index) => (
                    <Card key={index} className="bg-[#1e2329] border-[#2b3139]">
                        <CardContent className="p-6">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h4 className="font-medium text-[#eaecef]">{card.name}</h4>
                                    <span className={`badge ${card.type === 'execution_connection' ? 'bg-info' : 'bg-success'} mt-1`}>
                                        {card.type_label}
                                    </span>
                                </div>
                                <span className={`badge ${card.status === 'running' ? 'bg-success' : 'bg-warning'}`}>
                                    <i className={`las ${card.status === 'running' ? 'la-play-circle' : 'la-pause-circle'} mr-1`}></i>
                                    {card.status === 'running' ? 'Running' : 'Paused'}
                                </span>
                            </div>

                            <div className="space-y-2 mb-4">
                                <div className="flex justify-between">
                                    <span className="text-[#848e9c]">Broker/Account:</span>
                                    <strong className="text-[#eaecef]">{card.broker}</strong>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-[#848e9c]">Preset:</span>
                                    <strong className="text-[#eaecef]">{card.preset_name}</strong>
                                </div>
                                {card.open_positions > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-[#848e9c]">Open Positions:</span>
                                        <strong className="text-info">{card.open_positions}</strong>
                                    </div>
                                )}
                            </div>

                            <div className="border-t border-[#2b3139] pt-4 mb-4">
                                <div className="grid grid-cols-2 gap-4 text-center">
                                    <div>
                                        <div className="text-sm text-[#848e9c]">P/L Today</div>
                                        <div className="font-bold">{formatPL(card.pl_today)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-[#848e9c]">P/L This Week</div>
                                        <div className="font-bold">{formatPL(card.pl_week)}</div>
                                    </div>
                                </div>
                            </div>

                            <div className="flex gap-2">
                                <Link href={card.details_route} className="btn btn-outline-primary flex-1">
                                    <i className="las la-cog mr-1"></i>
                                    Manage
                                </Link>
                                <form action={card.toggle_route} method="POST" className="flex-1">
                                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')} />
                                    <button
                                        type="submit"
                                        className={`btn w-full ${card.status === 'running' ? 'btn-warning' : 'btn-success'}`}
                                    >
                                        <i className={`las ${card.status === 'running' ? 'la-pause' : 'la-play'} mr-1`}></i>
                                        {card.status === 'running' ? 'Stop' : 'Start'}
                                    </button>
                                </form>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
