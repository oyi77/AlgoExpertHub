import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function SignalDetails({ title, signal }) {
    const formatOutcome = (outcome) => {
        if (!outcome) return null;
        const isSuccess = outcome.toLowerCase().includes('hit') || outcome.toLowerCase().includes('success');
        return (
            <span className={`badge ${isSuccess ? 'bg-success' : 'bg-danger'}`}>
                {outcome}
            </span>
        );
    };

    return (
        <AppLayout>
            <Head title={title || 'Signal Details'} />

            <div className="mb-6">
                <Link href={route('user.beta.signals.index')} className="text-[#848e9c] hover:text-[#eaecef] mb-2 inline-block">
                    <i className="las la-arrow-left mr-2"></i>
                    Back to Signals
                </Link>
                <h1 className="text-2xl font-bold text-[#eaecef]">{signal?.title || 'Signal Details'}</h1>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2">
                    <Card className="bg-[#1e2329] border-[#2b3139]">
                        {signal?.image && (
                            <div className="w-full h-64 overflow-hidden rounded-t-lg">
                                <img
                                    src={signal.image}
                                    alt={signal.title}
                                    className="w-full h-full object-cover"
                                />
                            </div>
                        )}
                        <CardContent className="p-6">
                            <h3 className="text-xl font-semibold text-[#eaecef] mb-4">{signal?.title}</h3>
                            <div
                                className="prose prose-invert max-w-none text-[#848e9c]"
                                dangerouslySetInnerHTML={{ __html: signal?.description || '' }}
                            />
                        </CardContent>
                    </Card>
                </div>

                <div className="lg:col-span-1">
                    <Card className="bg-[#1e2329] border-[#2b3139] sticky top-6">
                        <CardHeader>
                            <CardTitle>Signal Overview</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-4">
                                <li className="flex justify-between">
                                    <span className="text-[#848e9c]">
                                        <i className="fas fa-id-badge mr-2"></i>
                                        Signal ID
                                    </span>
                                    <span className="text-[#eaecef] font-mono">#{signal?.id || 'N/A'}</span>
                                </li>
                                <li className="flex justify-between">
                                    <span className="text-[#848e9c]">
                                        <i className="fas fa-handshake mr-2"></i>
                                        Pair
                                    </span>
                                    <span className="text-[#eaecef]">{signal?.pair?.name || 'N/A'}</span>
                                </li>
                                <li className="flex justify-between">
                                    <span className="text-[#848e9c]">
                                        <i className="fas fa-plane-departure mr-2"></i>
                                        Direction
                                    </span>
                                    <span className={`badge ${signal?.direction === 'BUY' ? 'bg-success' : 'bg-danger'}`}>
                                        {signal?.direction || 'N/A'}
                                    </span>
                                </li>
                                <li className="flex justify-between">
                                    <span className="text-[#848e9c]">
                                        <i className="fas fa-hourglass-half mr-2"></i>
                                        Stop Loss
                                    </span>
                                    <span className="text-[#eaecef]">{signal?.sl || 'N/A'}</span>
                                </li>
                                <li className="flex justify-between">
                                    <span className="text-[#848e9c]">
                                        <i className="far fa-clock mr-2"></i>
                                        Time Frame
                                    </span>
                                    <span className="text-[#eaecef]">{signal?.time?.name || 'N/A'}</span>
                                </li>
                                <li className="flex justify-between">
                                    <span className="text-[#848e9c]">
                                        <i className="fas fa-money-bill mr-2"></i>
                                        Open Price
                                    </span>
                                    <span className="text-[#eaecef]">{signal?.open_price || 'N/A'}</span>
                                </li>
                                <li className="flex justify-between">
                                    <span className="text-[#848e9c]">
                                        <i className="fas fa-hand-holding-usd mr-2"></i>
                                        Take Profit
                                    </span>
                                    <span className="text-[#eaecef]">{signal?.tp || 'N/A'}</span>
                                </li>
                                {signal?.outcome && (
                                    <li className="flex justify-between">
                                        <span className="text-[#848e9c]">
                                            <i className="fas fa-flag-checkered mr-2"></i>
                                            Outcome
                                        </span>
                                        <span>{formatOutcome(signal.outcome)}</span>
                                    </li>
                                )}
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
