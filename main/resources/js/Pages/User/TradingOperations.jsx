import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/ui/Card';
import { Button } from '../../Components/ui/Button';

// Trading Bots Tab Component
const TradingBotsTab = ({ bots }) => {
    if (!bots || bots.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-robot la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Trading Bots</h4>
                <p className="text-sm mb-6">You haven't created any trading bots yet.</p>
                <Button className="bg-[#3b82f6] hover:bg-[#2563eb]">
                    <i className="las la-plus mr-2"></i>
                    Create Trading Bot
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {bots.data?.map((bot) => (
                <Card key={bot.id} className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4">
                        <div className="flex justify-between items-start">
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h4 className="font-medium text-[#eaecef]">{bot.name}</h4>
                                    <span className={`px-2 py-1 text-xs rounded ${bot.is_active ? 'bg-[#0ecb81] text-white' : 'bg-[#f6465d] text-white'}`}>
                                        {bot.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                    {bot.is_paper_trading && (
                                        <span className="px-2 py-1 text-xs bg-[#f0b90b] text-black rounded">
                                            Paper Trading
                                        </span>
                                    )}
                                </div>
                                {bot.description && (
                                    <p className="text-sm text-[#848e9c] mb-3">{bot.description}</p>
                                )}
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span className="text-[#848e9c]">Executions:</span>
                                        <span className="ml-2 text-[#eaecef]">{bot.total_executions || 0}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Success:</span>
                                        <span className="ml-2 text-[#0ecb81]">{bot.successful_executions || 0}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Failed:</span>
                                        <span className="ml-2 text-[#f6465d]">{bot.failed_executions || 0}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Win Rate:</span>
                                        <span className="ml-2 text-[#eaecef]">{bot.win_rate ? `${bot.win_rate}%` : 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-2 ml-4">
                                <Button size="sm" className="bg-[#0ecb81] hover:bg-[#00a878]">
                                    <i className="las la-play"></i>
                                </Button>
                                <Button size="sm" variant="outline">
                                    <i className="las la-cog"></i>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}
            
            {bots.links && (
                <div className="flex justify-center mt-6">
                    <div className="flex gap-2">
                        {bots.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url}
                                className={`px-3 py-2 text-sm rounded ${
                                    link.active
                                        ? 'bg-[#3b82f6] text-white'
                                        : 'bg-[#1e2329] text-[#848e9c] hover:bg-[#2b3139] hover:text-[#eaecef]'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

// Connections Tab Component
const ConnectionsTab = ({ connections }) => {
    if (!connections || connections.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-plug la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Connections</h4>
                <p className="text-sm mb-6">You haven't configured any exchange connections yet.</p>
                <Button className="bg-[#3b82f6] hover:bg-[#2563eb]">
                    <i className="las la-plus mr-2"></i>
                    Add Connection
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {connections.data?.map((connection) => (
                <Card key={connection.id} className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4">
                        <div className="flex justify-between items-start">
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h4 className="font-medium text-[#eaecef]">{connection.name}</h4>
                                    <span className={`px-2 py-1 text-xs rounded ${
                                        connection.status === 'active' ? 'bg-[#0ecb81] text-white' : 
                                        connection.status === 'error' ? 'bg-[#f6465d] text-white' : 
                                        'bg-[#848e9c] text-white'
                                    }`}>
                                        {connection.status || 'unknown'}
                                    </span>
                                    {connection.is_admin_owned && (
                                        <span className="px-2 py-1 text-xs bg-[#3b82f6] text-white rounded">
                                            System
                                        </span>
                                    )}
                                </div>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span className="text-[#848e9c]">Provider:</span>
                                        <span className="ml-2 text-[#eaecef]">{connection.provider || connection.exchange_name || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Type:</span>
                                        <span className="ml-2 text-[#eaecef]">{connection.connection_type || connection.type || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Data:</span>
                                        <span className="ml-2 text-[#0ecb81]">{connection.data_fetching_enabled ? 'Enabled' : 'Disabled'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Execution:</span>
                                        <span className="ml-2 text-[#0ecb81]">{connection.trade_execution_enabled ? 'Enabled' : 'Disabled'}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-2 ml-4">
                                <Button size="sm" variant="outline">
                                    <i className="las la-cog"></i>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}
            
            {connections.links && (
                <div className="flex justify-center mt-6">
                    <div className="flex gap-2">
                        {connections.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url}
                                className={`px-3 py-2 text-sm rounded ${
                                    link.active
                                        ? 'bg-[#3b82f6] text-white'
                                        : 'bg-[#1e2329] text-[#848e9c] hover:bg-[#2b3139] hover:text-[#eaecef]'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

// Open Positions Tab Component
const OpenPositionsTab = ({ positions }) => {
    if (!positions || positions.data?.length === 0) {
        return (
            <div className="text-center text-[#848e9c] py-8">
                <i className="las la-chart-line la-3x text-muted mb-4"></i>
                <h4 className="text-lg font-medium text-[#eaecef] mb-2">No Open Positions</h4>
                <p className="text-sm">You don't have any open trading positions.</p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {positions.data?.map((position) => (
                <Card key={position.id} className="bg-[#1e2329] border-[#2b3139]">
                    <CardContent className="p-4">
                        <div className="flex justify-between items-start">
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <span className="text-[#eaecef] font-medium">
                                        {position.symbol || position.pair?.symbol || 'N/A'}
                                    </span>
                                    <span className={`px-2 py-1 text-xs rounded ${
                                        position.direction === 'buy' || position.direction === 'long' ? 'bg-[#0ecb81] text-white' : 
                                        position.direction === 'sell' || position.direction === 'short' ? 'bg-[#f6465d] text-white' : 
                                        'bg-[#848e9c] text-white'
                                    }`}>
                                        {(position.direction || 'unknown').toUpperCase()}
                                    </span>
                                    <span className={`px-2 py-1 text-xs rounded ${
                                        position.status === 'open' ? 'bg-[#0ecb81] text-white' : 
                                        position.status === 'partial' ? 'bg-[#f0b90b] text-black' : 
                                        'bg-[#848e9c] text-white'
                                    }`}>
                                        {position.status || 'unknown'}
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                    <div>
                                        <span className="text-[#848e9c]">Entry Price:</span>
                                        <span className="ml-2 text-[#eaecef]">{position.entry_price || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Current:</span>
                                        <span className="ml-2 text-[#eaecef]">{position.current_price || position.exit_price || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">Volume:</span>
                                        <span className="ml-2 text-[#eaecef]">{position.volume || position.quantity || 'N/A'}</span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">P/L:</span>
                                        <span className={`ml-2 ${position.profit_loss >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                                            {position.profit_loss ? `${position.profit_loss >= 0 ? '+' : ''}${position.profit_loss}` : 'N/A'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-[#848e9c]">P/L %:</span>
                                        <span className={`ml-2 ${position.profit_loss_percent >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                                            {position.profit_loss_percent ? `${position.profit_loss_percent >= 0 ? '+' : ''}${position.profit_loss_percent}%` : 'N/A'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col gap-2 ml-4">
                                <Button size="sm" className="bg-[#f6465d] hover:bg-[#e5364d]">
                                    <i className="las la-times"></i>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}
            
            {positions.links && (
                <div className="flex justify-center mt-6">
                    <div className="flex gap-2">
                        {positions.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url}
                                className={`px-3 py-2 text-sm rounded ${
                                    link.active
                                        ? 'bg-[#3b82f6] text-white'
                                        : 'bg-[#1e2329] text-[#848e9c] hover:bg-[#2b3139] hover:text-[#eaecef]'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

export default function TradingOperations({ title, activeTab, tradingManagementEnabled, bots, connections, positions }) {
    const tabs = [
        { key: 'trading-bots', label: 'Trading Bots' },
        { key: 'connections', label: 'Connections' },
        { key: 'open-positions', label: 'Open Positions' },
    ];

    return (
        <AppLayout>
            <Head title={title || 'Trading Operations'} />
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-[#eaecef]">Trading Operations</h1>
            </div>

            <div className="flex gap-2 mb-6 overflow-x-auto">
                {tabs.map((tab) => (
                    <Link
                        key={tab.key}
                        href={`/beta/trading/operations?tab=${tab.key}`}
                        className={`px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors ${activeTab === tab.key
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
                            <p>Trading management features are not available.</p>
                        </div>
                    ) : (
                        <div>
                            {activeTab === 'trading-bots' && (
                                <TradingBotsTab bots={bots} />
                            )}
                            {activeTab === 'connections' && (
                                <ConnectionsTab connections={connections} />
                            )}
                            {activeTab === 'open-positions' && (
                                <OpenPositionsTab positions={positions} />
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
