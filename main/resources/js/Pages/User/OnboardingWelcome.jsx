import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';
import { Progress } from '../../Components/ui/Progress';

export default function OnboardingWelcome({ title, progress }) {
    return (
        <AppLayout>
            <Head title={title || 'Welcome'} />

            <div className="row justify-content-center">
                <div className="col-lg-8">
                    <Card className="bg-[#1e2329] border-[#2b3139]">
                        <CardContent className="text-center py-12">
                            <div className="mb-6">
                                <i className="las la-rocket" style={{ fontSize: '80px', color: 'var(--base-color)' }}></i>
                            </div>
                            <h2 className="text-2xl font-bold text-[#eaecef] mb-3">Welcome to AlgoExpert Hub!</h2>
                            <p className="text-[#848e9c] mb-6">
                                Let's get you started with a quick setup guide. This will only take a few minutes.
                            </p>

                            <div className="mb-6">
                                <Progress value={progress} className="h-2" />
                                <p className="text-[#848e9c] mt-2">Progress: {progress}%</p>
                            </div>

                            <div className="flex gap-3 justify-center">
                                <form action={route('user.onboarding.welcome.complete')} method="POST">
                                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')} />
                                    <Button type="submit" className="sp_theme_btn btn-lg">
                                        <i className="las la-arrow-right mr-2"></i>
                                        Get Started
                                    </Button>
                                </form>
                                <form action={route('user.onboarding.skip')} method="POST">
                                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')} />
                                    <Button type="submit" variant="outline" className="btn-lg">
                                        Skip for Now
                                    </Button>
                                </form>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
