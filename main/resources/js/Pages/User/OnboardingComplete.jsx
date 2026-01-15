import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function OnboardingComplete({ title, steps }) {
    return (
        <AppLayout>
            <Head title={title || 'Onboarding Complete'} />

            <div className="row justify-content-center">
                <div className="col-lg-8">
                    <Card className="bg-[#1e2329] border-[#2b3139]">
                        <CardContent className="text-center py-12">
                            <div className="mb-6">
                                <i className="las la-check-circle" style={{ fontSize: '100px', color: '#28a745' }}></i>
                            </div>
                            <h2 className="text-2xl font-bold text-[#eaecef] mb-3">Onboarding Complete!</h2>
                            <p className="text-[#848e9c] mb-6">
                                Congratulations! You've completed the setup process. You're all set to start trading.
                            </p>

                            {steps && (
                                <div className="row g-3 mb-6 justify-content-center">
                                    {Object.entries(steps)
                                        .filter(([key]) => key !== 'welcome' && (step => step.completed))
                                        .map(([stepKey, step]) => (
                                            <div key={stepKey} className="col-md-6">
                                                <div className="p-3 border border-[#2b3139] rounded bg-[#0b0e11]">
                                                    <i className="las la-check-circle text-success mr-2"></i>
                                                    <span className="text-[#eaecef]">
                                                        {step.label || stepKey.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                </div>
                            )}

                            <div className="flex gap-3 justify-center flex-wrap">
                                <Link href={route('user.beta.dashboard')} className="btn sp_theme_btn btn-lg">
                                    <i className="las la-home mr-2"></i>
                                    Go to Dashboard
                                </Link>
                                <Link href={route('user.beta.trading.multi-channel-signal.index')} className="btn btn-outline-primary btn-lg">
                                    <i className="las la-signal mr-2"></i>
                                    View Signals
                                </Link>
                            </div>

                            <div className="mt-6">
                                <p className="text-[#848e9c] small">
                                    Need help?
                                    <Link href={route('user.beta.ticket.index')} className="ml-2 text-[#3b82f6]">
                                        Contact Support
                                    </Link>
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
