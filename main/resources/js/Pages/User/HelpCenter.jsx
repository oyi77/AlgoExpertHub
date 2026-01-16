import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function HelpCenter({ title, topic, topics }) {
    return (
        <AppLayout>
            <Head title={title || 'Help Center'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Help Center</h1>
                <p className="text-[#848e9c] mt-1">Find answers to common questions</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                {topics && Object.entries(topics).map(([key, label]) => (
                    <Link key={key} href={`/beta/help/topic/${key}`}>
                        <Card className="bg-[#1e2329] border-[#2b3139] hover:border-[#3b82f6] cursor-pointer transition-colors h-full">
                            <CardContent className="p-4 flex items-center">
                                <span className="font-medium text-[#eaecef]">{label}</span>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardHeader>
                    <CardTitle>{topics?.[topic] || 'General Help'}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-[#848e9c]">Help content for {topic} will be displayed here.</p>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
