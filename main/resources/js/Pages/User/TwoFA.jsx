import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';
import { Alert } from '../../Components/ui/Alert';

export default function TwoFA({ title, user, google2fa_url, google2fa_enable, errors }) {
    const [step, setStep] = useState(user?.loginSecurity?.google2fa_enable ? 'disable' : (user?.loginSecurity ? 'verify' : 'setup'));
    const form = useForm({
        secret: '',
        'current-password': '',
    });

    const handleGenerateSecret = (e) => {
        e.preventDefault();
        window.location.href = route('user.generate2faSecret');
    };

    const handleEnable2FA = (e) => {
        e.preventDefault();
        form.post(route('user.enable2fa'));
    };

    const handleDisable2FA = (e) => {
        e.preventDefault();
        form.post(route('user.disable2fa'));
    };

    return (
        <AppLayout>
            <Head title={title || 'Two Factor Authentication'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Two Factor Authentication</h1>
                <p className="text-[#848e9c] mt-1">
                    Two factor authentication (2FA) strengthens access security by requiring two methods to verify your identity.
                </p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardHeader>
                    <CardTitle>Two Factor Authentication Settings</CardTitle>
                </CardHeader>
                <CardContent>
                    {step === 'setup' && (
                        <div className="text-center">
                            <p className="text-[#848e9c] mb-6">
                                Two factor authentication protects against phishing, social engineering and password brute force attacks.
                            </p>
                            <Button onClick={handleGenerateSecret} className="sp_theme_btn">
                                Generate Secret Key to Enable 2FA
                            </Button>
                        </div>
                    )}

                    {step === 'verify' && google2fa_url && (
                        <div className="max-w-md mx-auto">
                            <div className="text-center mb-6">
                                <p className="text-[#848e9c] mb-4">
                                    1. Scan this QR code with your Google Authenticator App.
                                </p>
                                <div className="bg-white p-4 rounded-lg inline-block mb-4">
                                    <img src={google2fa_url} alt="2FA QR Code" className="w-48 h-48" />
                                </div>
                            </div>

                            <form onSubmit={handleEnable2FA} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-[#848e9c] mb-2">
                                        2. Enter the pin from Google Authenticator app
                                    </label>
                                    <input
                                        type="text"
                                        name="secret"
                                        value={form.data.secret}
                                        onChange={(e) => form.setData('secret', e.target.value)}
                                        className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                        placeholder="Enter 6-digit code"
                                        maxLength={6}
                                        required
                                    />
                                    {errors?.['verify-code'] && (
                                        <p className="text-red-500 text-sm mt-2">{errors['verify-code']}</p>
                                    )}
                                </div>
                                <Button type="submit" className="w-full sp_theme_btn" disabled={form.processing}>
                                    {form.processing ? 'Enabling...' : 'Enable 2FA'}
                                </Button>
                            </form>
                        </div>
                    )}

                    {step === 'disable' && (
                        <div className="max-w-md mx-auto">
                            <Alert type="warning" className="mb-6">
                                If you are looking to disable Two Factor Authentication, please confirm your password and click Disable 2FA.
                            </Alert>

                            <form onSubmit={handleDisable2FA} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-[#848e9c] mb-2">
                                        Current Password
                                    </label>
                                    <input
                                        type="password"
                                        name="current-password"
                                        value={form.data['current-password']}
                                        onChange={(e) => form.setData('current-password', e.target.value)}
                                        className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                        placeholder="Enter your current password"
                                        required
                                    />
                                    {errors?.['current-password'] && (
                                        <p className="text-red-500 text-sm mt-2">{errors['current-password']}</p>
                                    )}
                                </div>
                                <Button type="submit" className="w-full sp_theme_btn" disabled={form.processing}>
                                    {form.processing ? 'Disabling...' : 'Disable 2FA'}
                                </Button>
                            </form>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
