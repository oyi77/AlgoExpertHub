import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';
import { Modal } from '../../Components/ui/Modal';

export default function GatewayList({ title, gateways, type, plan }) {
    const [showModal, setShowModal] = useState(false);
    const [selectedGateway, setSelectedGateway] = useState(null);
    const [amount, setAmount] = useState(type === 'deposit' ? '' : (plan?.price || ''));

    const handlePayNow = (gateway) => {
        setSelectedGateway(gateway);
        setShowModal(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (selectedGateway) {
            const actionUrl = route('user.paynow', selectedGateway.id);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const tokenInput = document.createElement('input');
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);

            const amountInput = document.createElement('input');
            amountInput.name = 'amount';
            amountInput.value = amount;
            form.appendChild(amountInput);

            if (type !== 'deposit' && plan) {
                const planInput = document.createElement('input');
                planInput.name = 'plan_id';
                planInput.value = plan.id;
                form.appendChild(planInput);
            }

            document.body.appendChild(form);
            form.submit();
        }
    };

    if (!gateways || gateways.length === 0) {
        return (
            <AppLayout>
                <Head title={title || 'Payment Gateways'} />

                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-[#eaecef]">Payment Gateways</h1>
                    <p className="text-[#848e9c] mt-1">Select a payment method</p>
                </div>

                <Card className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="text-center py-12">
                        <p className="text-[#848e9c]">No payment gateways available</p>
                    </CardContent>
                </Card>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={title || 'Payment Gateways'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Payment Gateways</h1>
                <p className="text-[#848e9c] mt-1">Select a payment method</p>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                {gateways.map((gateway) => (
                    <Card key={gateway.id} className="bg-[#1e2329] border-[#2b3139] hover:border-[#3b82f6] transition-colors">
                        <CardContent className="p-4 text-center">
                            <div className="h-16 flex items-center justify-center mb-3">
                                <img
                                    src={gateway.image_url || '/asset/images/placeholder.png'}
                                    alt={gateway.name}
                                    className="max-h-full max-w-full object-contain"
                                    onError={(e) => { e.target.src = '/asset/images/placeholder.png'; }}
                                />
                            </div>
                            <h4 className="font-medium text-[#eaecef] mb-3">
                                {gateway.name.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())}
                            </h4>
                            <Button
                                onClick={() => handlePayNow(gateway)}
                                className="sp_theme_btn w-full"
                                size="sm"
                            >
                                Pay Now
                            </Button>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Modal show={showModal} onClose={() => setShowModal(false)} title={type === 'deposit' ? 'Deposit Amount' : 'Pay Amount'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-[#848e9c] mb-2">
                            Amount
                        </label>
                        <input
                            type="text"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            className="w-full bg-[#0b0e11] border border-[#2b3139] rounded-lg px-4 py-3 text-[#eaecef] focus:outline-none focus:border-[#3b82f6]"
                            placeholder="Enter Amount"
                            required
                            readOnly={type !== 'deposit'}
                        />
                    </div>
                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                            Close
                        </Button>
                        <Button type="submit" className="sp_theme_btn">
                            {type === 'deposit' ? 'Deposit Now' : 'Pay Now'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
