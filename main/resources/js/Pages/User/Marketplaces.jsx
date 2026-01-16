import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function Marketplaces({ title, activeCategory, tradingManagementEnabled, items }) {
    const categories = [
        { key: 'trading-presets', label: 'Trading Presets' },
        { key: 'filter-strategies', label: 'Strategies' },
        { key: 'ai-profiles', label: 'AI Profiles' },
        { key: 'bot-marketplace', label: 'Bots' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Marketplaces'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Marketplaces</h1>
                <p className="text-[#848e9c] mt-1">Discover and clone trading resources</p>
            </div>

            <div className="flex gap-2 mb-6 overflow-x-auto">
                {categories.map((cat) => (
                    <Link
                        key={cat.key}
                        href={`/beta/trading/marketplaces?category=${cat.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
                            activeCategory === cat.key
                                ? 'bg-[#3b82f6] text-white'
                                : 'bg-[#1e2329] text-[#848e9c] hover:text-[#eaecef] hover:bg-[#2b3139]'
                        }`}
                    >
                        {cat.label}
                    </Link>
                ))}
            </div>

            {!tradingManagementEnabled ? (
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-6 text-center text-[#848e9c]">
                        Marketplace features are not available.
                    </CardContent>
                </Card>
            ) : items?.data?.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {items.data.map((item) => (
                        <Card key={item.id} className="bg-[#1e2329] border-[#2b3139]">
                            <CardHeader>
                                <CardTitle className="text-lg">{item.name || 'Item'}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-[#848e9c] mb-4">{item.description || 'No description'}</p>
                                <button className="w-full py-2 bg-[#3b82f6] text-white rounded-lg text-sm">Clone</button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            ) : (
                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-6 text-center text-[#848e9c]">
                        No items found in this category.
                    </CardContent>
                </Card>
            )}
        </AppLayout>
    );
}
