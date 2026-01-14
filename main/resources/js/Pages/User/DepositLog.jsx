import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';

export default function DepositLog({ deposits }) {
    return (
        <>
            <Head>
                <title>Deposit Log - AlgoExpertHub</title>
            </Head>

            <AppLayout>
                <div className="space-y-6">
                    <div className="bg-[#1a1f2e] border border-[#2b3139] rounded-lg p-6">
                        <h1 className="text-2xl font-bold text-[#eaecef]">Deposit Log</h1>
                        <p className="text-[#848e9c] mt-2">View your deposit history</p>
                    </div>

                    <div className="bg-[#1a1f2e] border border-[#2b3139] rounded-lg">
                        <div className="p-6">
                            {deposits && deposits.data.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left">
                                        <thead>
                                            <tr className="text-[#848e9c] text-xs uppercase tracking-wider border-b border-[#2b3139]">
                                                <th className="px-4 py-3">TRX ID</th>
                                                <th className="px-4 py-3">Gateway</th>
                                                <th className="px-4 py-3">Amount</th>
                                                <th className="px-4 py-3">Status</th>
                                                <th className="px-4 py-3">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-[#2b3139]">
                                            {deposits.data.map((item) => (
                                                <tr key={item.id} className="text-sm hover:bg-[#2b3139]/50 transition-colors">
                                                    <td className="px-4 py-3 text-[#eaecef] font-mono">
                                                        #{item.trx}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#eaecef]">
                                                        {item.gateway?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#eaecef] font-medium">
                                                        {item.amount} {item.method_currency}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <span className={`px-2 py-1 text-xs rounded-full ${
                                                            item.status === 1 ? 'bg-[#0ecb81]/20 text-[#0ecb81]' :
                                                            item.status === 2 ? 'bg-[#f0b90b]/20 text-[#f0b90b]' :
                                                            'bg-[#f6465d]/20 text-[#f6465d]'
                                                        }`}>
                                                            {item.status === 1 ? 'Complete' : item.status === 2 ? 'Pending' : 'Rejected'}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-[#848e9c]">
                                                        {new Date(item.created_at).toLocaleDateString()}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <div className="text-[#848e9c]">
                                        <svg className="mx-auto h-12 w-12 mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v16m8-8H4" />
                                        </svg>
                                        <p className="text-lg font-medium">No deposit records found</p>
                                        <p className="text-sm mt-2">Your deposit history will appear here</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
