import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

const SignalCard = ({ signal }) => {
    const isBuy = signal.direction === 'buy' || signal.direction === 'long';
    const directionColor = isBuy ? 'text-[#0ecb81]' : 'text-[#f6465d]';
    const borderColor = isBuy ? 'border-[#0ecb81]/30' : 'border-[#f6465d]/30';

    return (
        <div className={`bg-[#1e2329] rounded-xl border ${borderColor} overflow-hidden hover:border-opacity-60 transition-all duration-300`}>
            <div className="p-4 border-b border-[#2b3139] flex justify-between items-center">
                <div className="flex items-center gap-2">
                    <span className={`text-xs font-bold px-2 py-1 rounded uppercase ${
                        isBuy ? 'bg-[#0ecb81]/10 text-[#0ecb81]' : 'bg-[#f6465d]/10 text-[#f6465d]'
                    }`}>
                        {signal.direction}
                    </span>
                    <span className="font-medium text-[#eaecef]">{signal.pair?.name || 'N/A'}</span>
                    {isBuy ? (
                        <svg className="w-4 h-4 text-[#0ecb81]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    ) : (
                        <svg className="w-4 h-4 text-[#f6465d]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    )}
                </div>
                <span className="text-xs text-[#848e9c]">ID: {signal.id}</span>
            </div>

            {signal.image && (
                <div className="h-40 overflow-hidden bg-[#0b0e11]">
                    <img
                        src={signal.image_url || signal.image}
                        alt={signal.title}
                        className="w-full h-full object-cover"
                    />
                </div>
            )}

            <div className="p-4">
                <h3 className="font-semibold text-[#eaecef] mb-4 truncate">
                    <Link
                        href={`/beta/signals/${signal.id}/${signal.slug || signal.title.toLowerCase().replace(/\s+/g, '-')}`}
                        className="hover:text-[#0ecb81] transition-colors"
                    >
                        {signal.title}
                    </Link>
                </h3>

                <div className="space-y-2">
                    <div className="flex justify-between items-center text-sm">
                        <span className="text-[#848e9c] flex items-center">
                            <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Time Frame
                        </span>
                        <span className="text-[#eaecef] font-medium">{signal.time?.name || 'N/A'}</span>
                    </div>

                    <div className="flex justify-between items-center text-sm">
                        <span className="text-[#848e9c] flex items-center">
                            <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Open Price
                        </span>
                        <span className="text-[#eaecef] font-mono">{signal.open_price}</span>
                    </div>

                    <div className="flex justify-between items-center text-sm">
                        <span className="text-[#848e9c] flex items-center">
                            <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            Stop Loss
                        </span>
                        <span className="text-[#f6465d] font-mono">{signal.sl}</span>
                    </div>

                    <div className="flex justify-between items-center text-sm">
                        <span className="text-[#848e9c] flex items-center">
                            <svg className="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            Take Profit
                        </span>
                        <span className="text-[#0ecb81] font-mono">{signal.tp}</span>
                    </div>
                </div>

                <Link
                    href={`/beta/signals/${signal.id}/${signal.slug || signal.title.toLowerCase().replace(/\s+/g, '-')}`}
                    className="mt-4 block w-full text-center py-2 px-4 bg-[#2b3139] hover:bg-[#3b82f6] text-[#eaecef] rounded-lg transition-colors text-sm font-medium"
                >
                    View Details
                </Link>
            </div>
        </div>
    );
};

const EmptyState = () => (
    <div className="text-center py-12">
        <div className="mx-auto h-16 w-16 bg-[#2b3139] rounded-full flex items-center justify-center mb-4">
            <svg className="w-8 h-8 text-[#848e9c]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </div>
        <h3 className="text-lg font-medium text-[#eaecef] mb-2">No Trading Signals Available</h3>
        <p className="text-[#848e9c] mb-6 max-w-sm mx-auto">
            Signals will appear here once you subscribe to a plan with signal access.
            Our expert traders publish high-quality signals with entry, stop loss, and take profit levels.
        </p>
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <Link href="/user/plan">
                <Button className="w-full sm:w-auto">
                    <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    Upgrade Plan
                </Button>
            </Link>
            <Link href="/beta/trading/multi-channel-signal?tab=signal-sources">
                <Button variant="outline" className="w-full sm:w-auto border-[#2b3139] text-[#848e9c] hover:text-[#eaecef] hover:bg-[#2b3139]">
                    <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7m-6 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                    Add Custom Signal Source
                </Button>
            </Link>
        </div>
    </div>
);

const Pagination = ({ links }) => {
    if (!links || links.length <= 3) return null;

    return (
        <div className="flex justify-center items-center gap-1 mt-8">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url || '#'}
                    className={`px-3 py-2 text-sm rounded-lg transition-colors ${
                        link.active
                            ? 'bg-[#3b82f6] text-white'
                            : link.url
                            ? 'bg-[#2b3139] text-[#848e9c] hover:text-[#eaecef] hover:bg-[#3b82f6]/20'
                            : 'bg-transparent text-[#848e9c] cursor-not-allowed opacity-50'
                    }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    preserveScroll
                />
            ))}
        </div>
    );
};

export default function SignalCenter({ title, signals, search }) {
    return (
        <AppLayout>
            <Head title={title || 'Signal Center'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">{title || 'All Signals'}</h1>
                <p className="text-[#848e9c] mt-1">Browse and follow trading signals from expert traders</p>
            </div>

            {/* Search Bar */}
            <div className="mb-6">
                <form className="flex gap-2">
                    <div className="relative flex-1 max-w-md">
                        <input
                            type="text"
                            name="search"
                            defaultValue={search}
                            placeholder="Search signals by ID or title..."
                            className="w-full bg-[#1e2329] border border-[#2b3139] rounded-lg px-4 py-2 pl-10 text-[#eaecef] placeholder-[#848e9c] focus:outline-none focus:border-[#3b82f6] transition-colors"
                        />
                        <svg
                            className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#848e9c]"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>
            </div>

            {/* Signals Grid */}
            {signals && signals.data && signals.data.length > 0 ? (
                <>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {signals.data.map((signal) => (
                            <SignalCard key={signal.id} signal={signal} />
                        ))}
                    </div>

                    {/* Pagination */}
                    <Pagination links={signals.links} />
                </>
            ) : (
                <EmptyState />
            )}
        </AppLayout>
    );
}
