import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function TicketShow({ title, ticket, ticket_reply }) {
    return (
        <AppLayout>
            <Head title={title || 'Ticket Discussion'} />

            <div className="mb-6">
                <Link href="/beta/ticket" className="text-[#848e9c] hover:text-[#eaecef] text-sm mb-2 inline-block">
                    ← Back to Tickets
                </Link>
                <h1 className="text-2xl font-bold text-[#eaecef]">#{ticket.id} - {ticket.subject}</h1>
            </div>

            <div className="space-y-4 mb-6">
                {ticket_reply && ticket_reply.map((reply) => (
                    <Card key={reply.id} className={`bg-[#1e2329] border-[#2b3139] ${reply.user_id === ticket.user_id ? 'ml-8' : 'mr-8'}`}>
                        <CardContent className="p-4">
                            <div className="flex justify-between items-center mb-2">
                                <span className="font-medium text-[#eaecef]">{reply.user?.name || 'Support'}</span>
                                <span className="text-xs text-[#848e9c]">{reply.created_at}</span>
                            </div>
                            <div className="text-[#eaecef]" dangerouslySetInnerHTML={{ __html: reply.message }} />
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardHeader>
                    <CardTitle>Reply</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-4">
                        <textarea
                            className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6] h-32"
                            placeholder="Type your reply..."
                        ></textarea>
                        <div className="flex justify-end">
                            <Button type="submit">Send Reply</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
