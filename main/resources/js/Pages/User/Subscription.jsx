import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function Subscription({ title, subscription }) {
    return (
        <AppLayout>
            <Head title={title || 'My Subscription'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">My Subscription</h1>
            </div>
            <Card className="bg-[#1e2329] border-[#2b3139] max-w-xl">
                <CardHeader>
                    <CardTitle>Current Plan</CardTitle>
                </CardHeader>
                <CardContent>
                    {subscription ? (
                        <div>
                            <div className="text-3xl font-bold text-[#0ecb81] mb-2">{subscription.plan?.name}</div>
                            <div className="text-[#848e9c]">
                                <p>Expires: {subscription.plan_expired_at || 'Never'}</p>
                            </div>
                            <div className="mt-6">
                                <a href="/beta/plans">
                                    <Button>Upgrade Plan</Button>
                                </a>
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-6">
                            <p className="text-[#848e9c] mb-4">No active subscription</p>
                            <a href="/beta/plans">
                                <Button>Subscribe Now</Button>
                            </a>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
