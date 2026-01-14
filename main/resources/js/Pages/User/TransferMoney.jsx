import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function TransferMoney({ title }) {
    return (
        <AppLayout>
            <Head title={title || 'Transfer Money'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Transfer Money</h1>
                <p className="text-[#848e9c] mt-1">Send funds to another user</p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139] max-w-xl">
                <CardHeader>
                    <CardTitle>Transfer Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">Recipient Username/Email</label>
                            <input
                                type="text"
                                className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                placeholder="Enter username or email"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">Amount</label>
                            <input
                                type="number"
                                className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                placeholder="0.00"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">Note (Optional)</label>
                            <textarea
                                className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6] h-24"
                                placeholder="Add a note..."
                            ></textarea>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit">Transfer Funds</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
