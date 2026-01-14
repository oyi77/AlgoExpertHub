import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent } from '../../Components/ui/Card';

export default function SubscriptionLog({ title, subscriptions }) {
    return (
        <AppLayout>
            <Head title={title || 'Subscription Log'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Subscription Log</h1>
            </div>
            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-0">
                    {subscriptions && subscriptions.data && subscriptions.data.length > 0 ? (
                        <div className="divide-y divide-[#2b3139]">
                            {subscriptions.data.map((sub) => (
                                <div key={sub.id} className="p-4">
                                    <div className="flex justify-between">
                                        <div>
                                            <span className="font-medium text-[#eaecef]">{sub.plan?.name}</span>
                                            <div className="text-xs text-[#848e9c]">
                                                {sub.start_date} - {sub.plan_expired_at || 'Lifetime'}
                                            </div>
                                        </div>
                                        <span className={`px-2 py-1 rounded text-xs ${sub.is_current ? 'bg-[#0ecb81]/10 text-[#0ecb81]' : 'text-[#848e9c]'}`}>
                                            {sub.is_current ? 'Active' : 'Expired'}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center text-[#848e9c]">No subscriptions found</div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
