import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function HelpTopic({ title, topic, topics }) {
    return (
        <AppLayout>
            <Head title={title} />
            <div className="mb-6">
                <Link href="/user/beta/help" className="text-[#848e9c] hover:text-[#eaecef] text-sm mb-2 inline-block">
                    ← Back to Help Center
                </Link>
                <h1 className="text-2xl font-bold text-[#eaecef]">{topics?.[topic] || topic}</h1>
            </div>
            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-6">
                    <p className="text-[#848e9c]">
                        Help content for {topic} is being developed. Please check back later.
                    </p>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
