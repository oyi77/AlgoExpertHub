import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

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
                    <span className="text-[#848e9c] ml-2">vs last month</span>
                </div>
            )}
        </CardContent>
    </Card>
);

const SignalItem = ({ signal }) => (
    <div className="flex items-center justify-between p-4 border-b border-[#2b3139] last:border-0 hover:bg-[#2b3139]/20 transition-colors">
        <div className="flex items-center space-x-4">
            <div className="h-10 w-10 rounded-full bg-[#2b3139] flex items-center justify-center font-bold text-[#eaecef]">
                {signal.signal?.pair?.name.split('/')[0]}
            </div>
            <div>
                <h4 className="font-medium text-[#eaecef]">{signal.signal?.title}</h4>
                <div className="flex items-center text-sm text-[#848e9c] space-x-2">
                    <span>{signal.signal?.pair?.name}</span>
                    <span>•</span>
                    <span className={signal.signal?.direction === 'buy' || signal.signal?.direction === 'long' ? 'text-[#0ecb81]' : 'text-[#f6465d]'}>
                        {signal.signal?.direction.toUpperCase()}
                    </span>
                </div>
            </div>
        </div>
        <div className="text-right">
            <div className="font-mono text-[#eaecef]">{signal.signal?.open_price}</div>
            <div className="text-sm text-[#848e9c]">{new Date(signal.created_at).toLocaleDateString()}</div>
        </div>
    </div>
);

const Dashboard = ({
    totalbalance,
    totalDeposit,
    totalWithdraw,
    totalSupportTickets,
    signals,
    transactions,
    currentPlan
}) => {
    return (
        <AppLayout>
            <Head title="Dashboard" />

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title="Total Balance"
                    value={`$${Number(totalbalance).toFixed(2)}`}
                    icon={<svg className="w-6 h-6 text-[#0ecb81]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
                />
                <StatCard
                    title="Total Deposit"
                    value={`$${Number(totalDeposit).toFixed(2)}`}
                    icon={<svg className="w-6 h-6 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>}
                />
                <StatCard
                    title="Total Withdraw"
                    value={`$${Number(totalWithdraw).toFixed(2)}`}
                    icon={<svg className="w-6 h-6 text-[#f6465d]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>}
                />
                <StatCard
                    title="Active Tickets"
                    value={totalSupportTickets}
                    icon={<svg className="w-6 h-6 text-[#f59e0b]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Recent Signals */}
                <div className="lg:col-span-2">
                    <Card className="h-full">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Recent Signals</CardTitle>
                            <Link href="/user/signal/dashboard">
                                <Button variant="ghost" size="sm">View All</Button>
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            {signals.data && signals.data.length > 0 ? (
                                <div className="divide-y divide-[#2b3139]">
                                    {signals.data.map(signal => (
                                        <SignalItem key={signal.id} signal={signal} />
                                    ))}
                                </div>
                            ) : (
                                <div className="p-6 text-center text-[#848e9c]">
                                    No signals available yet.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Right Column: Plan & Transactions */}
                <div className="space-y-6">
                    {/* Current Plan */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Current Plan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {currentPlan && currentPlan.plan ? (
                                <div>
                                    <div className="text-xl font-bold text-[#0ecb81]">{currentPlan.plan.name}</div>
                                    <div className="mt-2 text-sm text-[#848e9c]">
                                        Expires: {currentPlan.end_date ? new Date(currentPlan.end_date).toLocaleDateString() : 'Never'}
                                    </div>
                                    <Link href="/user/plan" className="mt-4 block">
                                        <Button className="w-full">Upgrade Plan</Button>
                                    </Link>
                                </div>
                            ) : (
                                <div className="text-center">
                                    <p className="text-[#848e9c] mb-4">No active subscription</p>
                                    <Link href="/user/plan">
                                        <Button className="w-full">Subscribe Now</Button>
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Transactions */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Activity</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {transactions && transactions.length > 0 ? (
                                <div className="divide-y divide-[#2b3139]">
                                    {transactions.map(trx => (
                                        <div key={trx.id} className="p-4 hover:bg-[#2b3139]/20 transition-colors">
                                            <div className="flex justify-between items-center">
                                                <div className="text-sm font-medium text-[#eaecef]">{trx.type.toUpperCase().replace('_', ' ')}</div>
                                                <div className={`font-mono text-sm ${trx.type === 'deposit' ? 'text-[#0ecb81]' : 'text-[#eaecef]'}`}>
                                                    {trx.type === 'deposit' ? '+' : '-'}${Number(trx.amount).toFixed(2)}
                                                </div>
                                            </div>
                                            <div className="mt-1 text-xs text-[#848e9c] flex justify-between">
                                                <span>{trx.trx}</span>
                                                <span>{new Date(trx.created_at).toLocaleDateString()}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="p-6 text-center text-[#848e9c]">
                                    No recent transactions.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
};

export default Dashboard;
