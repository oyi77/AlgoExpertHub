import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/ui/Card';
import { Button } from '../../../Components/ui/Button';

const StatCard = ({ title, value, icon, trend }) => (
    <Card>
        <CardContent className="p-6">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-[#848e9c]">{title}</p>
                    <h3 className="text-2xl font-bold mt-2 text-[#eaecef]">{value}</h3>
                </div>
                <div className={`p-3 rounded-lg bg-[#2b3139]/50`}>
                    {icon}
                </div>
            </div>
            {trend && (
                <div className="mt-4 flex items-center text-sm">
                    <span className={trend > 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}>
                        {trend > 0 ? '+' : ''}{trend}%
                    </span>
                    <span className="text-[#848e9c] ml-2">vs last period</span>
                </div>
            )}
        </CardContent>
    </Card>
);

const PlatformStatus = ({ platform }) => (
    <div className="flex items-center justify-between p-4 border-b border-[#2b3139] last:border-0">
        <div className="flex items-center space-x-3">
            <div className={`w-3 h-3 rounded-full ${platform.enabled ? 'bg-[#0ecb81]' : 'bg-[#f6465d]'}`} />
            <div>
                <h4 className="font-medium text-[#eaecef] capitalize">{platform.platform}</h4>
                <p className="text-sm text-[#848e9c]">Rate limit: {platform.rate_limit ?? 'N/A'}/min</p>
            </div>
        </div>
        <span className={`text-sm ${platform.enabled ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
            {platform.enabled ? 'Enabled' : 'Disabled'}
        </span>
    </div>
);

const ActivityItem = ({ activity }) => (
    <div className="flex items-center justify-between p-4 border-b border-[#2b3139] last:border-0 hover:bg-[#2b3139]/20 transition-colors">
        <div className="flex items-center space-x-3">
            <div className="h-10 w-10 rounded-full bg-[#2b3139] flex items-center justify-center font-bold text-[#eaecef]">
                {activity.wallet_address?.slice(0, 4)}...
            </div>
            <div>
                <h4 className="font-medium text-[#eaecef]">{activity.symbol}</h4>
                <div className="flex items-center text-sm text-[#848e9c] space-x-2">
                    <span className="capitalize">{activity.platform}</span>
                </div>
            </div>
        </div>
        <div className="text-right">
            <div className={`font-mono ${parseFloat(activity.realized_pnl) >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                {parseFloat(activity.realized_pnl) >= 0 ? '+' : ''}{activity.realized_pnl}
            </div>
            <div className="text-sm text-[#848e9c]">{new Date(activity.closed_at).toLocaleDateString()}</div>
        </div>
    </div>
);

const Dashboard = ({ stats, recentActivity, platformHealth }) => {
    return (
        <AppLayout>
            <Head title="DEX Analytics Dashboard" />

            {/* Page Header */}
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-[#eaecef]">DEX Analytics Dashboard</h1>
                <p className="text-[#848e9c] mt-1">Monitor trader positions, PnL, and platform status</p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title="Total Traders"
                    value={stats.total_traders ?? 0}
                    icon={
                        <svg className="w-6 h-6 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    }
                />
                <StatCard
                    title="Active Positions"
                    value={stats.active_positions ?? 0}
                    icon={
                        <svg className="w-6 h-6 text-[#0ecb81]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    }
                />
                <StatCard
                    title="Total PnL"
                    value={`$${Number(stats.total_pnl ?? 0).toFixed(2)}`}
                    icon={
                        <svg className="w-6 h-6 text-[#f6465d]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    }
                />
                <StatCard
                    title="Liquidations"
                    value={stats.liquidations ?? 0}
                    icon={
                        <svg className="w-6 h-6 text-[#f6465d]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    }
                />
            </div>

            {/* Recent Activity & Platform Status */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                {/* Recent Activity */}
                <Card>
                    <CardHeader className="border-b border-[#2b3139]">
                        <CardTitle className="text-[#eaecef]">Recent Activity</CardTitle>
                    </CardHeader>
                    <div className="divide-y divide-[#2b3139]">
                        {recentActivity && recentActivity.length > 0 ? (
                            recentActivity.map((activity, index) => (
                                <ActivityItem key={index} activity={activity} />
                            ))
                        ) : (
                            <div className="p-6 text-center text-[#848e9c]">
                                No recent activity found.
                            </div>
                        )}
                    </div>
                </Card>

                {/* Platform Status */}
                <Card>
                    <CardHeader className="border-b border-[#2b3139]">
                        <CardTitle className="text-[#eaecef]">Platform Status</CardTitle>
                    </CardHeader>
                    <div className="divide-y divide-[#2b3139]">
                        {platformHealth && platformHealth.length > 0 ? (
                            platformHealth.map((platform, index) => (
                                <PlatformStatus key={index} platform={platform} />
                            ))
                        ) : (
                            <div className="p-6 text-center text-[#848e9c]">
                                No platform data available.
                            </div>
                        )}
                    </div>
                </Card>
            </div>

            {/* Quick Actions */}
            <div className="flex flex-wrap gap-4">
                <Link href="/beta/admin/dex-analytics/watchlist">
                    <Button variant="outline">
                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Manage Watchlist
                    </Button>
                </Link>
                <Link href="/beta/admin/dex-analytics/leaderboards">
                    <Button variant="outline">
                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        View Leaderboards
                    </Button>
                </Link>
                <Link href="/beta/admin/dex-analytics/analytics">
                    <Button variant="outline">
                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Analytics
                    </Button>
                </Link>
                <Link href="/beta/admin/dex-analytics/ai-insights">
                    <Button variant="outline">
                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        AI Insights
                    </Button>
                </Link>
            </div>
        </AppLayout>
    );
};

export default Dashboard;
