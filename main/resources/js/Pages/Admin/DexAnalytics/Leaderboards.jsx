import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/ui/Card';
import { Button } from '../../../Components/ui/Button';

const MetricTab = ({ active, onClick, label }) => (
    <button
        onClick={onClick}
        className={`px-4 py-2 text-sm font-medium transition-colors ${
            active
                ? 'text-[#0ecb81] border-b-2 border-[#0ecb81]'
                : 'text-[#848e9c] hover:text-[#eaecef]'
        }`}
    >
        {label}
    </button>
);

const TraderRow = ({ rank, trader }) => (
    <div className="flex items-center justify-between p-4 border-b border-[#2b3139] last:border-0 hover:bg-[#2b3139]/20 transition-colors">
        <div className="flex items-center space-x-4">
            <div className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm ${
                rank === 1 ? 'bg-[#ffd700] text-[#000]' :
                rank === 2 ? 'bg-[#c0c0c0] text-[#000]' :
                rank === 3 ? 'bg-[#cd7f32] text-[#fff]' :
                'bg-[#2b3139] text-[#eaecef]'
            }`}>
                {rank}
            </div>
            <div className="h-10 w-10 rounded-full bg-[#2b3139] flex items-center justify-center font-bold text-[#eaecef]">
                {trader.wallet_address?.slice(0, 4)}...
            </div>
            <div>
                <h4 className="font-medium text-[#eaecef]">{trader.wallet_address}</h4>
                <div className="flex items-center text-sm text-[#848e9c] space-x-2">
                    <span className="capitalize">{trader.platform}</span>
                    {trader.confidence_score && (
                        <>
                            <span>•</span>
                            <span>{trader.confidence_score}% confidence</span>
                        </>
                    )}
                </div>
            </div>
        </div>
        <div className="text-right">
            <div className={`font-mono font-bold ${
                (trader.score?.total_pnl ?? 0) >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'
            }`}>
                {trader.score?.total_pnl >= 0 ? '+' : ''}{trader.score?.total_pnl ?? 0}
            </div>
            <div className="text-sm text-[#848e9c]">
                Win Rate: {trader.score?.win_rate ?? 0}%
            </div>
        </div>
    </div>
);

const Leaderboards = ({ leaderboard, metricKey, platform }) => {
    const [activeMetric, setActiveMetric] = React.useState(metricKey || 'total_pnl');

    const metrics = [
        { key: 'total_pnl', label: 'Total PnL' },
        { key: 'win_rate', label: 'Win Rate' },
        { key: 'profit_factor', label: 'Profit Factor' },
    ];

    return (
        <AppLayout>
            <Head title="DEX Analytics - Leaderboards" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-[#eaecef]">Trader Leaderboards</h1>
                <p className="text-[#848e9c] mt-1">Top performing traders by metric</p>
            </div>

            {/* Metric Tabs */}
            <div className="flex space-x-4 mb-6 border-b border-[#2b3139]">
                {metrics.map((metric) => (
                    <MetricTab
                        key={metric.key}
                        active={activeMetric === metric.key}
                        onClick={() => setActiveMetric(metric.key)}
                        label={metric.label}
                    />
                ))}
            </div>

            {/* Sub-tabs for different leaderboard types */}
            <div className="flex space-x-2 mb-6">
                <Button variant="secondary" size="sm">Top Traders</Button>
                <Button variant="ghost" size="sm">Smart Money</Button>
                <Button variant="ghost" size="sm">Copy Suitable</Button>
            </div>

            <Card>
                <CardHeader className="border-b border-[#2b3139]">
                    <CardTitle className="text-[#eaecef]">
                        {metrics.find(m => m.key === activeMetric)?.label} Rankings
                    </CardTitle>
                </CardHeader>
                <div className="divide-y divide-[#2b3139]">
                    {leaderboard && leaderboard.length > 0 ? (
                        leaderboard.map((trader, index) => (
                            <TraderRow key={index} rank={index + 1} trader={trader} />
                        ))
                    ) : (
                        <div className="p-6 text-center text-[#848e9c]">
                            No leaderboard data available.
                        </div>
                    )}
                </div>
            </Card>
        </AppLayout>
    );
};

export default Leaderboards;
