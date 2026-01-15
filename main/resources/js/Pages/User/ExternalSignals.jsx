import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';
import { Alert } from '../../Components/ui/Alert';

export default function ExternalSignals({ title, multiChannelEnabled, activeTab, sources, channels, stats, channelStats }) {
    const [activeTabState, setActiveTabState] = useState(activeTab || 'sources');

    if (!multiChannelEnabled) {
        return (
            <AppLayout>
                <Head title={title || 'External Signals'} />

                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-[#eaecef]">External Signal</h1>
                    <p className="text-[#848e9c] mt-1">Manage your signal sources, channel forwarding, and pattern templates</p>
                </div>

                <Alert type="warning">
                    <i className="las la-exclamation-triangle mr-2"></i>
                    Multi-Channel Signal Addon is not enabled. Please contact administrator.
                </Alert>
            </AppLayout>
        );
    }

    const tabs = [
        { id: 'sources', label: 'Signal Sources', icon: 'la-signal' },
        { id: 'forwarding', label: 'Channel Forwarding', icon: 'la-share-alt' },
        { id: 'templates', label: 'Pattern Templates', icon: 'la-code' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'External Signals'} />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">External Signal</h1>
                <p className="text-[#848e9c] mt-1">Manage your signal sources, channel forwarding, and pattern templates</p>
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-0">
                    {/* Tab Navigation */}
                    <div className="flex border-b border-[#2b3139]">
                        {tabs.map((tab) => (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTabState(tab.id)}
                                className={`flex items-center px-6 py-4 text-sm font-medium transition-colors border-b-2 ${
                                    activeTabState === tab.id
                                        ? 'border-[#3b82f6] text-[#3b82f6]'
                                        : 'border-transparent text-[#848e9c] hover:text-[#eaecef]'
                                }`}
                            >
                                <i className={`las ${tab.icon} mr-2`}></i>
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    {/* Tab Content */}
                    <div className="p-6">
                        {activeTabState === 'sources' && (
                            <div>
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-semibold text-[#eaecef]">Signal Sources</h3>
                                    <Button className="sp_theme_btn">
                                        <i className="las la-plus mr-2"></i>
                                        Add Source
                                    </Button>
                                </div>

                                {sources && sources.length > 0 ? (
                                    <div className="space-y-4">
                                        {sources.map((source) => (
                                            <div key={source.id} className="bg-[#0b0e11] rounded-lg p-4 border border-[#2b3139]">
                                                <div className="flex justify-between items-start">
                                                    <div>
                                                        <h4 className="font-medium text-[#eaecef]">{source.name}</h4>
                                                        <p className="text-sm text-[#848e9c] mt-1">{source.type}</p>
                                                    </div>
                                                    <span className={`badge ${source.active ? 'bg-success' : 'bg-warning'}`}>
                                                        {source.active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </div>
                                                <div className="mt-4 flex gap-2">
                                                    <Button variant="outline" size="sm">
                                                        <i className="las la-edit mr-1"></i> Edit
                                                    </Button>
                                                    <Button variant="outline" size="sm">
                                                        <i className="las la-sync mr-1"></i> Sync
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-12">
                                        <i className="las la-signal la-3x text-muted mb-4"></i>
                                        <p className="text-[#848e9c]">No signal sources configured</p>
                                        <Button className="mt-4 sp_theme_btn">
                                            <i className="las la-plus mr-2"></i>
                                            Add Your First Source
                                        </Button>
                                    </div>
                                )}
                            </div>
                        )}

                        {activeTabState === 'forwarding' && (
                            <div>
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-semibold text-[#eaecef]">Channel Forwarding</h3>
                                    <Button className="sp_theme_btn">
                                        <i className="las la-plus mr-2"></i>
                                        Add Forwarding
                                    </Button>
                                </div>

                                {channels && channels.length > 0 ? (
                                    <div className="space-y-4">
                                        {channels.map((channel) => (
                                            <div key={channel.id} className="bg-[#0b0e11] rounded-lg p-4 border border-[#2b3139]">
                                                <div className="flex justify-between items-start">
                                                    <div>
                                                        <h4 className="font-medium text-[#eaecef]">{channel.name}</h4>
                                                        <p className="text-sm text-[#848e9c] mt-1">{channel.type}</p>
                                                    </div>
                                                    <span className={`badge ${channel.active ? 'bg-success' : 'bg-warning'}`}>
                                                        {channel.active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-12">
                                        <i className="las la-share-alt la-3x text-muted mb-4"></i>
                                        <p className="text-[#848e9c]">No channel forwarding configured</p>
                                        <Button className="mt-4 sp_theme_btn">
                                            <i className="las la-plus mr-2"></i>
                                            Create Forwarding Rule
                                        </Button>
                                    </div>
                                )}
                            </div>
                        )}

                        {activeTabState === 'templates' && (
                            <div>
                                <div className="text-center py-12">
                                    <i className="las la-code la-3x text-muted mb-4"></i>
                                    <h4 className="text-lg font-medium text-[#eaecef] mb-2">Pattern Templates</h4>
                                    <p className="text-[#848e9c] mb-6 max-w-md mx-auto">
                                        Pattern templates are managed by administrators. Contact your admin to create or modify pattern templates for signal parsing.
                                    </p>
                                    <Button variant="outline" disabled>
                                        <i className="las la-lock mr-2"></i>
                                        Admin Only
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
