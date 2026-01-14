import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function Withdraw({ title }) {
    return (
        <AppLayout>
            <Head title={title || 'Withdraw'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Withdraw Funds</h1>
                <p className="text-[#848e9c] mt-1">Request a withdrawal</p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139] max-w-xl">
                <CardHeader>
                    <CardTitle>Withdrawal Form</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">Amount</label>
                            <input
                                type="number"
                                className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                placeholder="0.00"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">Withdrawal Method</label>
                            <select className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]">
                                <option>Select method...</option>
                                <option>Bank Transfer</option>
                                <option>Crypto</option>
                            </select>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit">Submit Request</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
