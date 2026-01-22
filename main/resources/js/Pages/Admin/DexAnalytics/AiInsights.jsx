import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/ui/Card';
import { Button } from '../../../Components/ui/Button';

const InsightCard = ({ insight }) => (
    <Card className="mb-4">
        <CardContent className="p-6">
            <div className="flex items-start space-x-4">
                <div className={`p-3 rounded-lg ${
                    insight.type === 'warning' ? 'bg-[#f6465d]/20' :
                    insight.type === 'success' ? 'bg-[#0ecb81]/20' :
                    'bg-[#3b82f6]/20'
                }`}>
                    <svg className={`w-6 h-6 ${
                        insight.type === 'warning' ? 'text-[#f6465d]' :
                        insight.type === 'success' ? 'text-[#0ecb81]' :
                        'text-[#3b82f6]'
                    }`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div className="flex-1">
                    <h4 className="font-medium text-[#eaecef]">{insight.title}</h4>
                    <p className="text-sm text-[#848e9c] mt-1">{insight.description}</p>
                    <div className="flex items-center mt-3 text-xs text-[#848e9c]">
                        <span>{insight.trader}</span>
                        <span className="mx-2">•</span>
                        <span>{insight.time}</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
);

const ClusterCard = ({ cluster }) => (
    <div className="p-4 bg-[#2b3139]/50 rounded-lg">
        <div className="flex items-center justify-between mb-3">
            <h4 className="font-medium text-[#eaecef] capitalize">{cluster.name.replace(/_/g, ' ')}</h4>
            <span className="text-sm text-[#848e9c]">{cluster.count} traders</span>
        </div>
        <div className="space-y-2">
            <div className="flex justify-between text-sm">
                <span className="text-[#848e9c]">Avg Win Rate</span>
                <span className="text-[#eaecef]">{cluster.avg_win_rate}%</span>
            </div>
            <div className="flex justify-between text-sm">
                <span className="text-[#848e9c]">Avg PnL</span>
                <span className={cluster.avg_pnl >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}>{cluster.avg_pnl}</span>
            </div>
        </div>
    </div>
);

const AiInsights = ({ clusters, crowdedTrades }) => {
    return (
        <AppLayout>
            <Head title="DEX Analytics - AI Insights" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-[#eaecef]">AI Insights</h1>
                <p className="text-[#848e9c] mt-1">AI-powered market analysis and trader behavior clustering</p>
            </div>

            {/* AI Insights Tabs */}
            <div className="flex space-x-2 mb-6">
                <Button variant="secondary" size="sm">Overview</Button>
                <Button variant="ghost" size="sm">Crowded Trades</Button>
                <Button variant="ghost" size="sm">Regime Analysis</Button>
                <Button variant="ghost" size="sm">Clustering</Button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Insights */}
                <div className="lg:col-span-2">
                    <Card>
                        <CardHeader className="border-b border-[#2b3139]">
                            <CardTitle className="text-[#eaecef]">Latest Insights</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="p-6">
                                <p className="text-[#848e9c]">AI-generated insights will appear here based on trader behavior analysis.</p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Crowded Trades */}
                    <Card className="mt-6">
                        <CardHeader className="border-b border-[#2b3139]">
                            <CardTitle className="text-[#eaecef]">Crowded Trades</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="divide-y divide-[#2b3139]">
                                {crowdedTrades && crowdedTrades.length > 0 ? (
                                    crowdedTrades.map((trade, index) => (
                                        <div key={index} className="p-4 flex items-center justify-between">
                                            <div>
                                                <h4 className="font-medium text-[#eaecef]">{trade.symbol}</h4>
                                                <p className="text-sm text-[#848e9c]">{trade.traders} traders</p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-[#eaecef]">{trade.total_size}</p>
                                                <p className="text-sm text-[#848e9c]">total size</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="p-6 text-center text-[#848e9c]">
                                        No crowded trade data available.
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Sidebar - Clusters */}
                <div>
                    <Card>
                        <CardHeader className="border-b border-[#2b3139]">
                            <CardTitle className="text-[#eaecef]">Trader Clusters</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-4">
                            {clusters && Object.keys(clusters).length > 0 ? (
                                Object.entries(clusters).map(([key, cluster]) => (
                                    <ClusterCard key={key} cluster={{ name: key, ...cluster }} />
                                ))
                            ) : (
                                <>
                                    <ClusterCard cluster={{ name: 'consistent_winner', count: 12, avg_win_rate: 72, avg_pnl: 15420 }} />
                                    <ClusterCard cluster={{ name: 'mixed', count: 45, avg_win_rate: 48, avg_pnl: -3200 }} />
                                    <ClusterCard cluster={{ name: 'underperformer', count: 8, avg_win_rate: 32, avg_pnl: -28500 }} />
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
};

export default AiInsights;
