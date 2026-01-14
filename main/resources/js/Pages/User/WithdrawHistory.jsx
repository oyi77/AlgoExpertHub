import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';

export default function WithdrawHistory({ withdrawlogs }) {
    const { url } = usePage();

    const tabs = [
        { name: 'All Withdrawals', href: '/user/beta/withdraw/history', active: url.includes('/withdraw/history') },
        { name: 'Pending', href: '/user/beta/withdraw/pending', active: url.includes('/withdraw/pending') },
        { name: 'Completed', href: '/user/beta/withdraw/completed', active: url.includes('/withdraw/completed') },
    ];

    return (
        <>
            <Head>
                <title>Withdraw History - AlgoExpertHub</title>
            </Head>

            <AppLayout>
                <div className="space-y-6">
                    <div className="bg-[#1a1f2e] border border-[#2b3139] rounded-lg p-6">
                        <h1 className="text-2xl font-bold text-[#eaecef]">Withdraw History</h1>
                        <p className="text-[#848e9c] mt-2">View your withdrawal history</p>
                    </div>

                    <div className="bg-[#1a1f2e] border border-[#2b3139] rounded-lg">
                        <div className="border-b border-[#2b3139]">
                            <nav className="flex space-x-8 px-6" aria-label="Tabs">
                                {tabs.map((tab) => (
                                    <Link
                                        key={tab.name}
                                        href={tab.href}
                                        className={`py-4 px-1 border-b-2 font-medium inline-flex items-center ${
                                            tab.active
                                                ? 'border-[#0ecb81] text-[#0ecb81]'
                                                : 'border-transparent text-[#848e9c] hover:text-[#eaecef]'
                                        }`}
                                    >
                                        {tab.name}
                                    </Link>
                                ))}
                            </nav>
                        </div>

                        <div className="p-6">
                            {withdrawlogs && withdrawlogs.data.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left">
                                        <thead>
                                            <tr className="text-[#848e9c] text-xs uppercase tracking-wider border-b border-[#2b3139]">
                                                <th className="px-4 py-3">TRX ID</th>
                                                <th className="px-4 py-3">Method</th>
                                                <th className="px-4 py-3">Amount</th>
                                                <th className="px-4 py-3">Charge</th>
                                                <th className="px-4 py-3">Status</th>
                                                <th className="px-4 py-3">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-[#2b3139]">
                                            {withdrawlogs.data.map((item) => (
                                                <tr key={item.id} className="text-sm hover:bg-[#2b3139]/50 transition-colors">
                                                    <td className="px-4 py-3 text-[#eaecef] font-mono">
                                                        #{item.trx}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#eaecef]">
                                                        {item.withdraw_method?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#eaecef] font-medium">
                                                        {item.amount} {item.currency}
                                                    </td>
                                                    <td className="px-4 py-3 text-[#f6465d]">
                                                        {item.charge} {item.currency}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <span className={`px-2 py-1 text-xs rounded-full ${
                                                            item.status === 1 ? 'bg-[#0ecb81]/20 text-[#0ecb81]' :
                                                            item.status === 2 ? 'bg-[#f0b90b]/20 text-[#f0b90b]' :
                                                            item.status === 3 ? 'bg-[#f6465d]/20 text-[#f6465d]' :
                                                            'bg-[#848e9c]/20 text-[#848e9c]'
                                                        }`}>
                                                            {item.status === 1 ? 'Complete' : item.status === 2 ? 'Pending' : item.status === 3 ? 'Rejected' : 'Pending'}
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
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 0114 0z" />
                                        </svg>
                                        <p className="text-lg font-medium">No withdrawal records found</p>
                                        <p className="text-sm mt-2">Your withdrawal history will appear here</p>
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
