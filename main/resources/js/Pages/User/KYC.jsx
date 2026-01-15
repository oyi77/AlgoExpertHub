import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';
import { Alert } from '../../Components/ui/Alert';

export default function KYC({ title, user, kyc_config, kyc_status, errors }) {
    const [previews, setPreviews] = useState({});
    const form = useForm({
        ...Object.keys(kyc_config || {}).reduce((acc, key) => {
            const config = kyc_config[key];
            if (config.type === 'file') {
                acc[key] = null;
            } else {
                acc[key] = '';
            }
            return acc;
        }, {}),
    });

    const handleFileChange = (e) => {
        const { name, files } = e.target;
        const file = files[0];
        form.setData(name, file);

        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                setPreviews((prev) => ({ ...prev, [name]: e.target.result }));
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        Object.keys(form.data).forEach((key) => {
            formData.append(key, form.data[key]);
        });

        form.post(route('user.kyc.store'), {
            data: formData,
            forceFormData: true,
        });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 2:
                return <span className="badge bg-warning">Pending Verification</span>;
            case 3:
                return <span className="badge bg-danger">Rejected - Please Re-submit</span>;
            default:
                return null;
        }
    };

    return (
        <AppLayout>
            <Head title={title || 'KYC Verification'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">KYC Verification</h1>
                <p className="text-[#848e9c] mt-1">Verify your identity to enable all platform features</p>
            </div>

            {kyc_status === 2 && (
                <Alert type="warning" className="mb-6">
                    KYC Verification Request is Pending
                </Alert>
            )}

            {kyc_status === 3 && (
                <Alert type="error" className="mb-6">
                    KYC Verification Request is Rejected! Please Re-submit again
                </Alert>
            )}

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardHeader>
                    <CardTitle>KYC Document Upload</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {kyc_config && Object.entries(kyc_config).map(([key, config]) => (
                            <div key={key}>
                                <label className="block text-sm font-medium text-[#848e9c] mb-2">
                                    {config.field_label}
                                    {config.validation === 'required' && <span className="text-red-500 ml-1">*</span>}
                                </label>

                                {config.type === 'text' && (
                                    <input
                                        type="text"
                                        name={key}
                                        value={form.data[key] || ''}
                                        onChange={(e) => form.setData(key, e.target.value)}
                                        className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                        required={config.validation === 'required'}
                                    />
                                )}

                                {config.type === 'textarea' && (
                                    <textarea
                                        name={key}
                                        value={form.data[key] || ''}
                                        onChange={(e) => form.setData(key, e.target.value)}
                                        rows={4}
                                        className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                        required={config.validation === 'required'}
                                    />
                                )}

                                {config.type === 'file' && (
                                    <div className="space-y-2">
                                        <input
                                            type="file"
                                            name={key}
                                            onChange={handleFileChange}
                                            className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                                            accept="image/*,.pdf"
                                            required={config.validation === 'required'}
                                        />
                                        {previews[key] && (
                                            <div className="mt-2">
                                                <img
                                                    src={previews[key]}
                                                    alt="Preview"
                                                    className="w-32 h-32 object-cover rounded-lg border border-[#2b3139]"
                                                />
                                            </div>
                                        )}
                                    </div>
                                )}

                                {errors?.[key] && (
                                    <p className="text-red-500 text-sm mt-2">{errors[key]}</p>
                                )}
                            </div>
                        ))}

                        <div className="flex justify-end">
                            <Button type="submit" className="sp_theme_btn" disabled={form.processing}>
                                {form.processing ? 'Submitting...' : 'Submit KYC Verification'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
