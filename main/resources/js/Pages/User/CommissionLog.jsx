import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent } from '../../Components/ui/Card';

export default function CommissionLog({ title, commison }) {
    return (
        <AppLayout>
            <Head title={title || 'Commission Log'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Commission Log</h1>
            </div>
            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-0">
                    {commison && commison.data && commison.data.length > 0 ? (
                        <div className="divide-y divide-[#2b3139]">
                            {commison.data.map((item) => (
                                <div key={item.id} className="p-4 flex justify-between items-center">
                                    <div>
                                        <span className="text-[#eaecef]">From: {item.whoSendTheMoney?.username}</span>
                                        <div className="text-xs text-[#848e9c]">{item.trx}</div>
                                    </div>
                                    <span className="text-[#0ecb81]">+{item.amount}</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center text-[#848e9c]">No commissions yet</div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
