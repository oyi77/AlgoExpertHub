import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function TicketList({ title, tickets, tickets_pending, tickets_answered, tickets_closed, tickets_all, current_status }) {
    const [activeTab, setActiveTab] = useState(current_status || 'all');

    const tabs = [
        { key: 'all', label: 'All', count: tickets_all },
        { key: 'pending', label: 'Pending', count: tickets_pending },
        { key: 'answered', label: 'Answered', count: tickets_answered },
        { key: 'closed', label: 'Closed', count: tickets_closed },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Support Tickets'} />

            <div className="mb-6 flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-[#eaecef]">Support Tickets</h1>
                    <p className="text-[#848e9c] mt-1">Manage your support requests</p>
                </div>
                <Link href="/user/beta/ticket/create">
                    <Button>New Ticket</Button>
                </Link>
            </div>

            {/* Tabs */}
            <div className="flex gap-2 mb-6 overflow-x-auto">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={`/user/beta/ticket/status/${tab.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
                            activeTab === tab.key
                                ? 'bg-[#3b82f6] text-white'
                                : 'bg-[#1e2329] text-[#848e9c] hover:text-[#eaecef] hover:bg-[#2b3139]'
                        }`}
                    >
                        {tab.label} ({tab.count})
                    </Link>
                ))}
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-0">
                    {tickets && tickets.data && tickets.data.length > 0 ? (
                        <div className="divide-y divide-[#2b3139]">
                            {tickets.data.map((ticket) => (
                                <div key={ticket.id} className="p-4 hover:bg-[#2b3139]/20 transition-colors">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <Link
                                                href={`/user/beta/ticket/${ticket.id}`}
                                                className="font-medium text-[#eaecef] hover:text-[#3b82f6]"
                                            >
                                                #{ticket.id} - {ticket.subject}
                                            </Link>
                                            <p className="text-sm text-[#848e9c] mt-1">
                                                {ticket.last_reply ? `Last reply: ${ticket.last_reply}` : `Created: ${ticket.created_at}`}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className={`px-2 py-1 rounded text-xs ${
                                                ticket.status === 1 ? 'bg-[#f6465d]/10 text-[#f6465d]' :
                                                ticket.status === 2 ? 'bg-[#f59e0b]/10 text-[#f59e0b]' :
                                                'bg-[#0ecb81]/10 text-[#0ecb81]'
                                            }`}>
                                                {ticket.status === 1 ? 'Closed' : ticket.status === 2 ? 'Pending' : 'Answered'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center text-[#848e9c]">
                            No tickets found
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
