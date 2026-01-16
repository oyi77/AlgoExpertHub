import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';

// Data Connections Tab Component
const DataConnectionsTab = () => {
    return (
        <div className="text-center text-[#848e9c] py-8">
            <i className="las la-database la-3x text-muted mb-4"></i>
            <h4 className="text-lg font-medium text-[#eaecef] mb-2">Data Connections</h4>
            <p className="text-sm mb-4">Configure your market data connections from exchanges and data providers.</p>
            <button className="px-4 py-2 bg-[#3b82f6] text-white rounded-lg hover:bg-[#2563eb]">
                <i className="las la-plus mr-2"></i>
                Add Data Connection
            </button>
        </div>
    );
};

// Risk Presets Tab Component
const RiskPresetsTab = () => {
    const presets = [
        { name: 'Conservative', risk: 1, description: 'Low risk, steady returns' },
        { name: 'Balanced', risk: 2, description: 'Moderate risk with balanced returns' },
        { name: 'Aggressive', risk: 3, description: 'Higher risk for maximum returns' },
    ];

    return (
        <div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                {presets.map((preset, index) => (
                    <Card key={index} className="bg-[#1e2329] border-[#2b3139]">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-lg">{preset.name}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-3">
                                <div className="flex items-center gap-2 mb-2">
                                    <span className="text-sm text-[#848e9c]">Risk Level:</span>
                                    <div className="flex">
                                        {[1, 2, 3].map((level) => (
                                            <div
                                                key={level}
                                                className={`w-2 h-2 mx-0.5 rounded ${
                                                    level <= preset.risk
                                                        ? 'bg-[#f6465d]'
                                                        : 'bg-[#2b3139]'
                                                }`}
                                            />
                                        ))}
                                    </div>
                                </div>
                                <p className="text-sm text-[#848e9c]">{preset.description}</p>
                            </div>
                            <button className="w-full px-3 py-2 bg-[#3b82f6] text-white rounded hover:bg-[#2563eb]">
                                Use Preset
                            </button>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

// Smart Risk Tab Component
const SmartRiskTab = () => {
    return (
        <div className="text-center text-[#848e9c] py-8">
            <i className="las la-brain la-3x text-muted mb-4"></i>
            <h4 className="text-lg font-medium text-[#eaecef] mb-2">Smart Risk Management</h4>
            <p className="text-sm mb-4">AI-powered risk management that adapts to market conditions and your trading performance.</p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto">
                <div className="bg-[#1e2329] border border-[#2b3139] rounded p-4">
                    <h5 className="font-medium text-[#eaecef] mb-2">Adaptive Sizing</h5>
                    <p className="text-sm text-[#848e9c]">Automatically adjusts position sizes based on volatility and account balance.</p>
                </div>
                <div className="bg-[#1e2329] border border-[#2b3139] rounded p-4">
                    <h5 className="font-medium text-[#eaecef] mb-2">Correlation Protection</h5>
                    <p className="text-sm text-[#848e9c]">Prevents overexposure to correlated currency pairs.</p>
                </div>
            </div>
        </div>
    );
};

// Filter Strategies Tab Component
const FilterStrategiesTab = () => {
    const strategies = [
        { name: 'EMA Crossover', status: 'Active', success: '65%' },
        { name: 'RSI Oversold', status: 'Testing', success: '72%' },
        { name: 'Bollinger Breakout', status: 'Inactive', success: '58%' },
    ];

    return (
        <div>
            <div className="space-y-4">
                {strategies.map((strategy, index) => (
                    <Card key={index} className="bg-[#1e2329] border-[#2b3139]">
                        <CardContent className="p-4">
                            <div className="flex justify-between items-center">
                                <div>
                                    <h5 className="font-medium text-[#eaecef]">{strategy.name}</h5>
                                    <p className="text-sm text-[#848e9c]">Win rate: {strategy.success}</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className={`px-2 py-1 text-xs rounded ${
                                        strategy.status === 'Active' 
                                            ? 'bg-[#0ecb81] text-white' 
                                            : strategy.status === 'Testing'
                                            ? 'bg-[#f0b90b] text-black'
                                            : 'bg-[#848e9c] text-white'
                                    }`}>
                                        {strategy.status}
                                    </span>
                                    <button className="px-3 py-1 text-sm bg-[#3b82f6] text-white rounded hover:bg-[#2563eb]">
                                        Configure
                                    </button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

// AI Profiles Tab Component
const AiProfilesTab = () => {
    const profiles = [
        { 
            name: 'Conservative Analyst', 
            provider: 'OpenAI GPT-4', 
            confidence: '85%',
            description: 'Focuses on high-probability setups with lower risk'
        },
        { 
            name: 'Aggressive Trader', 
            provider: 'Gemini Pro', 
            confidence: '72%',
            description: 'Identifies high-potential opportunities with calculated risks'
        },
        { 
            name: 'Balanced Strategy', 
            provider: 'Claude 3', 
            confidence: '78%',
            description: 'Balances risk and reward for consistent performance'
        },
    ];

    return (
        <div>
            <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                {profiles.map((profile, index) => (
                    <Card key={index} className="bg-[#1e2329] border-[#2b3139]">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-lg">{profile.name}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-sm text-[#848e9c]">Provider:</span>
                                    <span className="text-sm text-[#eaecef]">{profile.provider}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm text-[#848e9c]">Confidence:</span>
                                    <span className="text-sm text-[#0ecb81] font-medium">{profile.confidence}</span>
                                </div>
                                <p className="text-sm text-[#848e9c]">{profile.description}</p>
                                <button className="w-full px-3 py-2 bg-[#3b82f6] text-white rounded hover:bg-[#2563eb]">
                                    Select Profile
                                </button>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

export default function TradingConfiguration({ title, activeTab, tradingManagementEnabled }) {
    const tabs = [
        { key: 'data-connections', label: 'Data Connections' },
        { key: 'risk-presets', label: 'Risk Presets' },
        { key: 'smart-risk', label: 'Smart Risk' },
        { key: 'filter-strategies', label: 'Filter Strategies' },
        { key: 'ai-profiles', label: 'AI Profiles' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Trading Configuration'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Trading Configuration</h1>
            </div>

            <div className="flex gap-2 mb-6 overflow-x-auto">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={`/beta/trading/configuration?tab=${tab.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${
                            activeTab === tab.key
                                ? 'bg-[#3b82f6] text-white'
                                : 'bg-[#1e2329] text-[#848e9c] hover:text-[#eaecef] hover:bg-[#2b3139]'
                        }`}
                    >
                        {tab.label}
                    </Link>
                ))}
            </div>

            <Card className="bg-[#1e2329] border-[#2b3139]">
                <CardContent className="p-6">
                    {!tradingManagementEnabled ? (
                        <div className="text-center text-[#848e9c]">
                            Trading management features are not available.
                        </div>
                    ) : (
                        <div>
                            {activeTab === 'data-connections' && <DataConnectionsTab />}
                            {activeTab === 'risk-presets' && <RiskPresetsTab />}
                            {activeTab === 'smart-risk' && <SmartRiskTab />}
                            {activeTab === 'filter-strategies' && <FilterStrategiesTab />}
                            {activeTab === 'ai-profiles' && <AiProfilesTab />}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
