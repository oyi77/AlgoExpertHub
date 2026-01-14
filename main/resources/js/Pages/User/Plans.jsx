import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

export default function Plans({ title, plans }) {
    return (
        <AppLayout>
            <Head title={title || 'Plans'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Choose Your Plan</h1>
                <p className="text-[#848e9c] mt-1">Select a plan that fits your trading needs</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {plans && plans.data && plans.data.map((plan) => (
                    <Card key={plan.id} className="bg-[#1e2329] border-[#2b3139]">
                        <CardHeader className="text-center">
                            <CardTitle className="text-xl">{plan.name}</CardTitle>
                            <div className="text-3xl font-bold text-[#0ecb81] mt-2">
                                ${plan.price}
                                <span className="text-sm text-[#848e9c] font-normal">/{plan.duration} days</span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2 mb-6">
                                {plan.features && plan.features.map((feature, idx) => (
                                    <li key={idx} className="text-sm text-[#eaecef] flex items-center">
                                        <svg className="w-4 h-4 text-[#0ecb81] mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                        </svg>
                                        {feature}
                                    </li>
                                ))}
                            </ul>
                            <button className="w-full py-2 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-lg transition-colors">
                                Subscribe
                            </button>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
