import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

export default function Profile({ title, user }) {
    return (
        <AppLayout>
            <Head title={title || 'Profile Settings'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Profile Settings</h1>
                <p className="text-[#848e9c] mt-1">Manage your account information</p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardHeader>
                    <CardTitle>Personal Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-[#848e9c] mb-2">Name</label>
                                <input
                                    type="text"
                                    defaultValue={user?.name}
                                    className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[#848e9c] mb-2">Email</label>
                                <input
                                    type="email"
                                    defaultValue={user?.email}
                                    className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                    disabled
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[#848e9c] mb-2">Username</label>
                                <input
                                    type="text"
                                    defaultValue={user?.username}
                                    className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[#848e9c] mb-2">Phone</label>
                                <input
                                    type="text"
                                    defaultValue={user?.phone}
                                    className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-2 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                />
                            </div>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit">Save Changes</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
