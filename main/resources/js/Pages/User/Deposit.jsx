import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function Deposit({ title }) {
    return (
        <AppLayout>
            <Head title={title || 'Deposit'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Deposit Funds</h1>
                <p className="text-[#848e9c] mt-1">Add funds to your account</p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardHeader>
                    <CardTitle>Select Payment Method</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {['Credit Card', 'Bank Transfer', 'Crypto'].map((method) => (
                            <button
                                key={method}
                                className="p-6 bg-[#2b3139] hover:bg-[#3b82f6] rounded-lg transition-colors text-left"
                            >
                                <h3 className="font-medium text-[#eaecef]">{method}</h3>
                                <p className="text-sm text-[#848e9c] mt-1">Click to select</p>
                            </button>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
