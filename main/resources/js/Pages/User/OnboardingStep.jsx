import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';
import { Progress } from '../../Components/ui/Progress';

export default function OnboardingStep({ title, step, currentStepIndex, totalSteps, progress, stepData }) {
    const renderStepContent = () => {
        switch (step) {
            case 'profile':
                return (
                    <div className="space-y-4">
                        <h4 className="text-lg font-medium text-[#eaecef]">Complete Your Profile</h4>
                        <p className="text-[#848e9c]">Fill in your personal details to get started.</p>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-[#848e9c] mb-2">Full Name</label>
                                <input type="text" className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef]" placeholder="Enter your name" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[#848e9c] mb-2">Phone</label>
                                <input type="tel" className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef]" placeholder="Enter phone number" />
                            </div>
                        </div>
                    </div>
                );
            case 'plan':
                return (
                    <div className="space-y-4">
                        <h4 className="text-lg font-medium text-[#eaecef]">Choose a Plan</h4>
                        <p className="text-[#848e9c]">Select a subscription plan that suits your needs.</p>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {[1, 2, 3].map((i) => (
                                <div key={i} className="bg-[#0b0e11] rounded-lg p-4 border border-[#2b3139] cursor-pointer hover:border-[#3b82f6]">
                                    <h5 className="font-medium text-[#eaecef]">Plan {i}</h5>
                                    <p className="text-2xl font-bold text-[#eaecef] mt-2">$99<span className="text-sm text-[#848e9c]">/mo</span></p>
                                    <ul className="text-sm text-[#848e9c] mt-4 space-y-2">
                                        <li><i className="las la-check text-success mr-2"></i>Feature 1</li>
                                        <li><i className="las la-check text-success mr-2"></i>Feature 2</li>
                                        <li><i className="las la-check text-success mr-2"></i>Feature 3</li>
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </div>
                );
            case 'signal_source':
                return (
                    <div className="space-y-4">
                        <h4 className="text-lg font-medium text-[#eaecef]">Configure Signal Sources</h4>
                        <p className="text-[#848e9c]">Connect your preferred signal sources.</p>
                        <div className="space-y-3">
                            {['Telegram', 'RSS Feed', 'API'].map((source) => (
                                <div key={source} className="flex items-center justify-between bg-[#0b0e11] rounded-lg p-4 border border-[#2b3139]">
                                    <span className="text-[#eaecef]">{source}</span>
                                    <Button variant="outline" size="sm">Connect</Button>
                                </div>
                            ))}
                        </div>
                    </div>
                );
            case 'trading_connection':
                return (
                    <div className="space-y-4">
                        <h4 className="text-lg font-medium text-[#eaecef]">Add Trading Connection</h4>
                        <p className="text-[#848e9c]">Connect your trading account.</p>
                        <div className="bg-[#0b0e11] rounded-lg p-6 border border-[#2b3139] text-center">
                            <i className="las la-exchange-alt la-3x text-muted mb-4"></i>
                            <p className="text-[#848e9c] mb-4">Connect your broker account to start trading</p>
                            <Button className="sp_theme_btn">Add Connection</Button>
                        </div>
                    </div>
                );
            default:
                return (
                    <div className="text-center py-8">
                        <i className="las la-cog la-3x text-muted mb-4"></i>
                        <p className="text-[#848e9c]">{step.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())} Setup</p>
                    </div>
                );
        }
    };

    return (
        <AppLayout>
            <Head title={title || 'Onboarding'} />

            <div className="row justify-content-center">
                <div className="col-lg-10">
                    <Card className="bg-[#1e2329] border-[#2b3139]">
                        <CardHeader>
                            <div className="d-flex justify-content-between align-items-center">
                                <CardTitle>Onboarding Setup</CardTitle>
                                <span className="badge bg-primary">{currentStepIndex + 1}/{totalSteps}</span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-6">
                                <div className="d-flex justify-content-between mb-2">
                                    <small className="text-[#848e9c]">Step {currentStepIndex + 1} of {totalSteps}</small>
                                    <small className="text-[#848e9c]">{progress}% Complete</small>
                                </div>
                                <Progress value={progress} className="h-2" />
                            </div>

                            <div className="mb-6">
                                {renderStepContent()}
                            </div>

                            <div className="d-flex justify-content-between">
                                <form action={route('user.onboarding.skip')} method="POST">
                                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')} />
                                    <Button type="submit" variant="outline">
                                        <i className="las la-times mr-2"></i>
                                        Skip Onboarding
                                    </Button>
                                </form>

                                {stepData?.completed && (
                                    <form action={route('user.onboarding.step.complete', { step })} method="POST">
                                        <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')} />
                                        <Button type="submit" className="sp_theme_btn">
                                            Continue
                                            <i className="las la-arrow-right ml-2"></i>
                                        </Button>
                                    </form>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
