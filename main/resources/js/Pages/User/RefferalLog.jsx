import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function RefferalLog({ title, reference }) {
    return (
        <AppLayout>
            <Head title={title || 'Referral Log'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Referral Log</h1>
                <p className="text-[#848e9c] mt-1">Users you have referred</p>
            </div>
            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-0">
                    {reference && reference.length > 0 ? (
                        <div className="divide-y divide-[#2b3139]">
                            {reference.map((user) => (
                                <div key={user.id} className="p-4 flex justify-between items-center">
                                    <div>
                                        <span className="font-medium text-[#eaecef]">{user.username}</span>
                                        <div className="text-xs text-[#848e9c]">{user.email}</div>
                                    </div>
                                    <span className="text-[#848e9c] text-sm">{user.created_at}</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center text-[#848e9c]">No referrals yet</div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
