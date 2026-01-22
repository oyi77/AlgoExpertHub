import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/ui/Card';
import { Button } from '../../../Components/ui/Button';

const AnalyticStat = ({ title, value, change, positive }) => (
    <div className="p-4 bg-[#2b3139]/50 rounded-lg">
        <p className="text-sm text-[#848e9c]">{title}</p>
        <p className="text-2xl font-bold text-[#eaecef] mt-1">{value}</p>
        {change !== undefined && (
            <p className={`text-sm mt-2 ${positive ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                {positive ? '+' : ''}{change}%
            </p>
        )}
    </div>
);

const PositionRow = ({ position }) => (
    <div className="flex items-center justify-between p-4 border-b border-[#2b3139] last:border-0 hover:bg-[#2b3139]/20 transition-colors">
        <div className="flex items-center space-x-3">
            <div className="h-10 w-10 rounded-full bg-[#2b3139] flex items-center justify-center font-bold text-[#eaecef]">
                {position.symbol?.slice(0, 4)}
            </div>
            <div>
                <h4 className="font-medium text-[#eaecef]">{position.symbol}</h4>
                <div className="flex items-center text-sm text-[#848e9c] space-x-2">
                    <span className={position.side === 'long' ? 'text-[#0ecb81]' : 'text-[#f6465d]'}>{position.side}</span>
                    <span>•</span>
                    <span>{position.leverage}x</span>
                </div>
            </div>
        </div>
        <div className="text-right">
            <div className="font-mono text-[#eaecef]">{position.size}</div>
            <div className={`text-sm ${parseFloat(position.unrealized_pnl) >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                {parseFloat(position.unrealized_pnl) >= 0 ? '+' : ''}{position.unrealized_pnl} PnL
            </div>
        </div>
    </div>
);

const Analytics = ({ positions, stats }) => {
    return (
        <AppLayout>
            <Head title="DEX Analytics - Analytics" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-[#eaecef]">Analytics</h1>
                <p className="text-[#848e9c] mt-1">Detailed trader analytics and positions</p>
            </div>

            {/* Analytics Tabs */}
            <div className="flex space-x-2 mb-6">
                <Button variant="secondary" size="sm">Positions</Button>
                <Button variant="ghost" size="sm">PnL</Button>
                <Button variant="ghost" size="sm">Funding</Button>
                <Button variant="ghost" size="sm">Liquidations</Button>
                <Button variant="ghost" size="sm">Performance</Button>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <AnalyticStat title="Total Volume" value="$1.2M" change={12.5} positive={true} />
                <AnalyticStat title="Avg Win Rate" value="68%" change={5.2} positive={true} />
                <AnalyticStat title="Avg Profit Factor" value="2.4" change={-2.1} positive={false} />
                <AnalyticStat title="Max Drawdown" value="-15%" change={1.5} positive={false} />
            </div>

            {/* Positions Table */}
            <Card>
                <CardHeader className="border-b border-[#2b3139]">
                    <CardTitle className="text-[#eaecef]">Open Positions</CardTitle>
                </CardHeader>
                <div className="divide-y divide-[#2b3139]">
                    {positions && positions.length > 0 ? (
                        positions.map((position, index) => (
                            <PositionRow key={index} position={position} />
                        ))
                    ) : (
                        <div className="p-6 text-center text-[#848e9c]">
                            No open positions found.
                        </div>
                    )}
                </div>
            </Card>
        </AppLayout>
    );
};

export default Analytics;
