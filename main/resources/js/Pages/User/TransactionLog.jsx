import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function TransactionLog({ title, transactions }) {
    return (
        <AppLayout>
            <Head title={title || 'Transaction Log'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Transaction History</h1>
                <p className="text-[#848e9c] mt-1">View all your transactions</p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-0">
                    {transactions && transactions.data && transactions.data.length > 0 ? (
                        <div className="divide-y divide-[#2b3139]">
                            {transactions.data.map((trx) => (
                                <div key={trx.id} className="p-4 hover:bg-[#2b3139]/20 transition-colors">
                                    <div className="flex justify-between items-center">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium text-[#eaecef] uppercase">{trx.type}</span>
                                                <span className="text-xs text-[#848e9c]">{trx.trx}</span>
                                            </div>
                                            <p className="text-sm text-[#848e9c] mt-1">{trx.date || trx.created_at}</p>
                                        </div>
                                        <div className={`font-mono font-medium ${
                                            trx.type === 'deposit' || trx.type === 'bonus' ? 'text-[#0ecb81]' : 'text-[#eaecef]'
                                        }`}>
                                            {trx.type === 'deposit' || trx.type === 'bonus' ? '+' : '-'}{trx.amount}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center text-[#848e9c]">
                            No transactions found
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
