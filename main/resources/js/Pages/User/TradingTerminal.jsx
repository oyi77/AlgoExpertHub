import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { AppLayout } from '../../Components/Layout/AppLayout';
import { ChartWidget } from '../../Components/Terminal/Widgets/ChartWidget';
import { OrderBookWidget } from '../../Components/Terminal/Widgets/OrderBookWidget';
import { SymbolSelector } from '../../Components/Terminal/Widgets/SymbolSelector';

const TradingTerminal = () => {
    const { symbol: initialSymbol, currentPrice: initialPrice, openPositions, stats24h: initialStats } = usePage().props;
    const [symbol, setSymbol] = useState(initialSymbol || 'BTCUSDT');
    const [currentPrice, setCurrentPrice] = useState(initialPrice);
    const [stats24h, setStats24h] = useState(initialStats);
    const [Layout, setLayout] = useState(null);
    const [isLoaded, setIsLoaded] = useState(false);

    useEffect(() => {
        // Dynamically import react-grid-layout only on client side
        Promise.all([
            import('react-grid-layout'),
            import('react-grid-layout/css/styles.css'),
            import('react-resizable/css/styles.css')
        ]).then(([RGL]) => {
            const { Responsive } = RGL;

            // Custom WidthProvider implementation since it's missing from RGL exports
            const WidthProvider = (ComposedComponent) => {
                return (props) => {
                    const outerRef = React.useRef(null);
                    const [width, setWidth] = React.useState(0);
                    const [mounted, setMounted] = React.useState(false);

                    React.useEffect(() => {
                        setMounted(true);
                        if (!outerRef.current) return;

                        const observer = new ResizeObserver(entries => {
                            if (entries[0]) {
                                setWidth(entries[0].contentRect.width);
                            }
                        });

                        observer.observe(outerRef.current);

                        // Initial size
                        setWidth(outerRef.current.clientWidth);

                        return () => observer.disconnect();
                    }, []);

                    // If not mounted or width is 0, render a placeholder div to measure
                    if (!mounted) {
                        return <div ref={outerRef} className={props.className} style={props.style} />;
                    }

                    return (
                        <div ref={outerRef} className={props.className} style={props.style}>
                            {width > 0 && <ComposedComponent {...props} width={width} />}
                        </div>
                    );
                };
            };

            const ResponsiveLayout = WidthProvider(Responsive);
            setLayout(() => ResponsiveLayout);
            setIsLoaded(true);
        }).catch((err) => {
            console.error('Failed to load trading terminal:', err);
            setIsLoaded(true);
        });
    }, []);

    const defaultLayouts = {
        lg: [
            { i: 'chart', x: 0, y: 0, w: 9, h: 20, minW: 4, minH: 10 },
            { i: 'orderbook', x: 9, y: 0, w: 3, h: 20, minW: 2, minH: 10 },
            { i: 'trades', x: 9, y: 20, w: 3, h: 10, minW: 2, minH: 5 },
            { i: 'positions', x: 0, y: 20, w: 9, h: 10, minW: 4, minH: 5 },
        ]
    };

    const onLayoutChange = (layout, layouts) => {
        // Handle layout change if needed
    };

    const LoadingFallback = () => (
        <div className="h-[calc(100vh-64px)] -m-6 bg-[#0b0e11] flex items-center justify-center">
            <div className="text-[#848e9c] flex flex-col items-center">
                <svg className="animate-spin h-8 w-8 mb-4 text-[#0ecb81]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading Trading Terminal...</span>
            </div>
        </div>
    );

    if (!Layout || !isLoaded) {
        return (
            <AppLayout>
                <Head title={`Trading ${symbol || 'Terminal'}`} />
                <LoadingFallback />
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title={`Trading ${symbol || 'Terminal'}`} />

            <div className="h-[calc(100vh-64px)] -m-6 bg-[#0b0e11]">
                <Layout
                    className="layout"
                    layouts={defaultLayouts}
                    breakpoints={{ lg: 1200, md: 996, sm: 768, xs: 480, xxs: 0 }}
                    cols={{ lg: 12, md: 10, sm: 6, xs: 4, xxs: 2 }}
                    rowHeight={30}
                    draggableHandle=".drag-handle"
                    onLayoutChange={onLayoutChange}
                    margin={[4, 4]}
                >
                    <div key="chart" className="bg-[#181a20] border border-[#2b3139] rounded overflow-hidden">
                        <div className="drag-handle h-6 bg-[#2b3139] cursor-move flex items-center px-2 justify-between">
                            <span className="text-xs text-[#848e9c] font-medium flex items-center gap-2">
                                <SymbolSelector
                                    currentSymbol={symbol}
                                    onSymbolChange={(newSymbol) => setSymbol(newSymbol)}
                                />
                                <span className={stats24h?.price_change_percent >= 0 ? "text-[#0ecb81]" : "text-[#f6465d]"}>
                                    {currentPrice}
                                </span>
                            </span>
                            <span className="text-xs text-[#848e9c]">Chart</span>
                        </div>
                        <div className="h-[calc(100%-24px)]">
                            <ChartWidget symbol={symbol} currentPrice={currentPrice} />
                        </div>
                    </div>

                    <div key="orderbook" className="bg-[#181a20] border border-[#2b3139] rounded overflow-hidden">
                        <div className="drag-handle h-6 bg-[#2b3139] cursor-move flex items-center px-2">
                            <span className="text-xs text-[#848e9c] font-medium">Order Book</span>
                        </div>
                        <div className="h-[calc(100%-24px)]">
                            <OrderBookWidget symbol={symbol} currentPrice={currentPrice} />
                        </div>
                    </div>

                    <div key="trades" className="bg-[#181a20] border border-[#2b3139] rounded flex items-center justify-center text-[#848e9c] overflow-hidden">
                        <div className="w-full h-full flex flex-col">
                            <div className="drag-handle w-full h-6 bg-[#2b3139] cursor-move flex items-center px-2 shrink-0">
                                <span className="text-xs text-[#848e9c] font-medium">Recent Trades</span>
                            </div>
                            <div className="w-full flex-1 overflow-y-auto p-2 scrollbar-hide">
                                <div className="flex justify-between text-[10px] text-[#848e9c] mb-1">
                                    <span>Price</span>
                                    <span>Amount</span>
                                    <span>Time</span>
                                </div>
                                {Array.from({ length: 15 }).map((_, i) => (
                                    <div key={i} className="flex justify-between text-[10px] py-0.5 hover:bg-[#2b3139]">
                                        <span className={i % 2 === 0 ? "text-[#0ecb81]" : "text-[#f6465d]"}>
                                            {(parseFloat(currentPrice || 45000) + (Math.random() * 10 - 5)).toFixed(2)}
                                        </span>
                                        <span className="text-[#eaecef]">{Math.random().toFixed(4)}</span>
                                        <span className="text-[#848e9c]">10:3{i}:22</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div key="positions" className="bg-[#181a20] border border-[#2b3139] rounded flex items-center justify-center text-[#848e9c] overflow-hidden">
                        <div className="w-full h-full flex flex-col">
                            <div className="drag-handle w-full h-6 bg-[#2b3139] cursor-move flex items-center px-2 shrink-0">
                                <span className="text-xs text-[#848e9c] font-medium">Positions & Orders</span>
                            </div>
                            <div className="w-full flex-1 overflow-y-auto p-4">
                                {openPositions && openPositions.length > 0 ? (
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="text-[#848e9c]">
                                                <th className="pb-2">Symbol</th>
                                                <th className="pb-2">Size</th>
                                                <th className="pb-2">Entry Price</th>
                                                <th className="pb-2">PnL</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {openPositions.map((pos) => (
                                                <tr key={pos.id} className="border-t border-[#2b3139]">
                                                    <td className="py-2 text-[#eaecef]">{pos.symbol}</td>
                                                    <td className="py-2 text-[#eaecef]">{pos.quantity}</td>
                                                    <td className="py-2 text-[#eaecef]">{pos.entry_price}</td>
                                                    <td className={`py-2 ${pos.pnl >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                                                        {pos.pnl}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                ) : (
                                    <div className="text-center text-sm mt-4">No open positions</div>
                                )}
                            </div>
                        </div>
                    </div>
                </Layout>
            </div>
        </AppLayout>
    );
};

export default TradingTerminal;
