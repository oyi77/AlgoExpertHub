import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function ChangePassword({ title }) {
    return (
        <AppLayout>
            <Head title={title || 'Change Password'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Change Password</h1>
                <p className="text-[#848e9c] mt-1">Update your account password</p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139] max-w-xl">
                <CardHeader>
                    <CardTitle>Security</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">Current Password</label>
                            <input
                                type="password"
                                name="oldpassword"
                                className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">New Password</label>
                            <input
                                type="password"
                                name="password"
                                className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-[#848e9c] mb-2">Confirm New Password</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                            />
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit">Update Password</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
