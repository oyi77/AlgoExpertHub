import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';

export default function InterestLog({ interestLogs }) {
    return (
        <>
            <Head>
                <title>Interest Log - AlgoExpertHub</title>
            </Head>

            <AppLayout>
                <div className="space-y-6">
                    <div className="bg-[#1a1f2e] border border-[#2b3139] rounded-lg p-6">
                        <h1 className="text-2xl font-bold text-[#eaecef]">Interest Log</h1>
                        <p className="text-[#848e9c] mt-2">View your earned interest history</p>
                    </div>

                    <div className="bg-[#1a1f2e] border border-[#2b3139] rounded-lg">
                        <div className="p-6">
                            {interestLogs && interestLogs.data.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left">
                                        <thead>
                                            <tr className="text-[#848e9c] text-xs uppercase tracking-wider border-b border-[#2b3139]">
                                                <th className="px-4 py-3">Date</th>
                                                <th className="px-4 py-3">Amount</th>
                                                <th className="px-4 py-3">Details</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-[#2b3139]">
                                            {interestLogs.data.map((item) => (
                                                <tr key={item.id} className="text-sm hover:bg-[#2b3139]/50 transition-colors">
                                                    <td className="px-4 py-3 text-[#848e9c]">
                                                        {new Date(item.created_at).toLocaleDateString()}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#0ecb81] font-medium">
                                                        +{item.amount}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#eaecef]">
                                                        {item.details || 'Interest Return'}
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
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p className="text-lg font-medium">No interest records found</p>
                                        <p className="text-sm mt-2">Interest earned from investments will appear here</p>
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
