import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/ui/Card';
import { Button } from '../../../Components/ui/Button';

const WatchlistItem = ({ item, onDelete }) => (
    <div className="flex items-center justify-between p-4 border-b border-[#2b3139] last:border-0 hover:bg-[#2b3139]/20 transition-colors">
        <div className="flex items-center space-x-3">
            <div className="h-10 w-10 rounded-full bg-[#2b3139] flex items-center justify-center font-bold text-[#eaecef]">
                {item.wallet_address?.slice(0, 4)}...
            </div>
            <div>
                <h4 className="font-medium text-[#eaecef]">{item.wallet_address}</h4>
                <div className="flex items-center text-sm text-[#848e9c] space-x-2">
                    <span className="capitalize">{item.platform}</span>
                    <span>•</span>
                    <span className={item.status === 'active' ? 'text-[#0ecb81]' : 'text-[#f6465d]'}>{item.status}</span>
                </div>
            </div>
        </div>
        <div className="flex items-center space-x-2">
            <Link href={`/beta/admin/dex-analytics/watchlist/${item.id}/edit`}>
                <Button variant="ghost" size="sm">
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </Button>
            </Link>
            <Button variant="ghost" size="sm" onClick={() => onDelete(item.id)}>
                <svg className="w-4 h-4 text-[#f6465d]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </Button>
        </div>
    </div>
);

const Watchlist = ({ watchlist }) => {
    return (
        <AppLayout>
            <Head title="DEX Analytics - Watchlist" />

            <div className="mb-8 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-[#eaecef]">Trader Watchlist</h1>
                    <p className="text-[#848e9c] mt-1">Manage tracked trader wallets</p>
                </div>
                <Link href="/beta/admin/dex-analytics/watchlist/create">
                    <Button>
                        <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        Add Trader
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader className="border-b border-[#2b3139]">
                    <CardTitle className="text-[#eaecef]">Watched Traders</CardTitle>
                </CardHeader>
                <div className="divide-y divide-[#2b3139]">
                    {watchlist && watchlist.data && watchlist.data.length > 0 ? (
                        <>
                            {watchlist.data.map((item) => (
                                <WatchlistItem key={item.id} item={item} />
                            ))}
                        </>
                    ) : (
                        <div className="p-6 text-center text-[#848e9c]">
                            No traders found. Add your first trader to start tracking.
                        </div>
                    )}
                </div>
                {watchlist && watchlist.links && (
                    <div className="p-4 border-t border-[#2b3139]">
                        <div className="flex items-center justify-between">
                            <div className="text-sm text-[#848e9c]">
                                Showing {watchlist.data?.length ?? 0} of {watchlist.total ?? 0} traders
                            </div>
                            <div className="flex space-x-2">
                                {watchlist.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-3 py-1 text-sm rounded ${
                                            link.active
                                                ? 'bg-[#0ecb81] text-[#000]'
                                                : 'bg-[#2b3139] text-[#848e9c] hover:bg-[#3b4451]'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </Card>
        </AppLayout>
    );
};

export default Watchlist;
