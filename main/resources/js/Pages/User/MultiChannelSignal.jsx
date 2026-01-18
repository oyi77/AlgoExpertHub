import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

// All Signals Tab Component
const AllSignalsTab = ({ signals }) => {
    if (!signals || signals.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-wave-square la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Signals Yet</h4>
                <p className="text-sm mb-6">No trading signals have been generated yet.</p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {signals.data?.map((signal) => (
                <Card key={signal.id} className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4">
                        <div className="flex justify-between items-start">
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <span className="text-[#eaecef] font-medium">
                                        {signal.pair?.symbol || signal.symbol}
                                    </span>
                                    <span className={`px-2 py-1 text-xs rounded ${
                                        signal.direction === 'buy' ? 'bg-[#0ecb81] text-white' : 
                                        signal.direction === 'sell' ? 'bg-[#f6465d] text-white' : 
                                        'bg-[#848e9c] text-white'
                                    }`}>
                                        {signal.direction?.toUpperCase() || 'N/A'}
                                    </span>
                                    <span className="text-xs text-[#848e9c]">
                                        {signal.time?.timeframe || 'M15'}
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span className="text-[#848e9c]">Entry:</span>
                                        <span className="ml-2 text-[#eaecef]">{signal.entry_price || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">SL:</span>
                                        <span className="ml-2 text-[#f6465d]">{signal.stop_loss || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">TP:</span>
                                        <span className="ml-2 text-[#0ecb81]">{signal.take_profit || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Source:</span>
                                        <span className="ml-2 text-[#eaecef]">{signal.channel_source?.name || 'Auto'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}
            
            {signals.links && (
                <div className="flex justify-center mt-6">
                    <div className="flex gap-2">
                        {signals.links.map((link, index) => (
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

// Signal Sources Tab Component
const SignalSourcesTab = ({ sources }) => {
    if (!sources || sources.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-broadcast-tower la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Signal Sources</h4>
                <p className="text-sm mb-6">You haven't configured any signal sources yet.</p>
                <Button className="bg-[#3b82f6] hover:bg-[#2563eb]">
                    <i className="las la-plus mr-2"></i>
                    Add Signal Source
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {sources.data?.map((source) => (
                <Card key={source.id} className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4">
                        <div className="flex justify-between items-start">
                            <div>
                                <h4 className="font-medium text-[#eaecef]">{source.name}</h4>
                                <p className="text-sm text-[#848e9c]">{source.type || 'Telegram'}</p>
                            </div>
                            <span className={`px-2 py-1 text-xs rounded ${
                                source.status === 'active' ? 'bg-[#0ecb81] text-white' : 'bg-[#848e9c] text-white'
                            }`}>
                                {source.status || 'inactive'}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
};

// Channel Forwarding Tab Component
const ChannelForwardingTab = ({ channels, stats }) => {
    if (!channels || channels.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-random la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Forwarding Channels</h4>
                <p className="text-sm mb-6">No channels have been assigned to you for signal forwarding.</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#eaecef]">{stats?.total || 0}</div>
                        <div className="text-sm text-[#848e9c]">Total Channels</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#0ecb81]">{stats?.by_user || 0}</div>
                        <div className="text-sm text-[#848e9c]">Assigned to You</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#3b82f6]">{stats?.by_plan || 0}</div>
                        <div className="text-sm text-[#848e9c]">By Your Plan</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#f0b90b]">{stats?.global || 0}</div>
                        <div className="text-sm text-[#848e9c]">Global</div>
                    </CardContent>
                </Card>
            </div>

            <div className="space-y-4">
                {channels.data?.map((channel) => (
                    <Card key={channel.id} className="bg-[#1e2329] border-[#2b3139]">
                        <CardContent className="p-4">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h4 className="font-medium text-[#eaecef]">{channel.name}</h4>
                                    <p className="text-sm text-[#848e9c]">{channel.assignment_info?.description || 'Not assigned'}</p>
                                </div>
                                <span className={`px-2 py-1 text-xs rounded ${
                                    channel.status === 'active' ? 'bg-[#0ecb81] text-white' : 'bg-[#848e9c] text-white'
                                }`}>
                                    {channel.status || 'inactive'}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

// Signal Review Tab Component
const SignalReviewTab = ({ reviewSignals }) => {
    if (!reviewSignals || reviewSignals.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-check-circle la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">All Caught Up!</h4>
                <p className="text-sm">No signals pending review.</p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {reviewSignals.data?.map((signal) => (
                <Card key={signal.id} className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4">
                        <div className="flex justify-between items-start">
                            <div>
                                <h4 className="font-medium text-[#eaecef]">{signal.pair?.symbol || signal.symbol}</h4>
                                <p className="text-sm text-[#848e9c]">Draft signal awaiting review</p>
                            </div>
                            <Button size="sm" className="bg-[#0ecb81] hover:bg-[#00a878]">
                                <i className="las la-check mr-1"></i> Publish
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
};

// Analytics Tab Component
const AnalyticsTab = ({ analytics }) => {
    return (
        <div className="space-y-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#eaecef]">{analytics?.total_signals || 0}</div>
                        <div className="text-sm text-[#848e9c]">Total Signals</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#0ecb81]">{analytics?.published_signals || 0}</div>
                        <div className="text-sm text-[#848e9c]">Published</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#f0b90b]">{analytics?.draft_signals || 0}</div>
                        <div className="text-sm text-[#848e9c]">Drafts</div>
                    </CardContent>
                </Card>
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4 text-center">
                        <div className="text-2xl font-bold text-[#3b82f6]">{analytics?.active_sources || 0}</div>
                        <div className="text-sm text-[#848e9c]">Active Sources</div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default function MultiChannelSignal({ title, activeTab, multiChannelEnabled, signals, sources, channels, stats, reviewSignals, analytics }) {
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
                    <Link
                        key={tab.key}
                        href={`/beta/trading/multi-channel-signal?tab=${tab.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
                            activeTab === tab.key
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
                    {!multiChannelEnabled ? (
                        <div className="text-center text-[#848e9c]">
                            Multi-channel signal features are not available.
                        </div>
                    ) : (
                        <div>
                            {activeTab === 'all-signals' && <AllSignalsTab signals={signals} />}
                            {activeTab === 'signal-sources' && <SignalSourcesTab sources={sources} />}
                            {activeTab === 'channel-forwarding' && <ChannelForwardingTab channels={channels} stats={stats} />}
                            {activeTab === 'signal-review' && <SignalReviewTab reviewSignals={reviewSignals} />}
                            {activeTab === 'analytics' && <AnalyticsTab analytics={analytics} />}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
