import React, { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

const OrderRow = ({ price, amount, total, type }) => {
    const validPrice = price != null && !isNaN(price) ? parseFloat(price) : 0;
    const validAmount = amount != null && !isNaN(amount) ? parseFloat(amount) : 0;
    const validTotal = total != null && !isNaN(total) ? parseFloat(total) : 0;

    const bgWidth = Math.min((validAmount / 2) * 100, 100); // Adjusted scale
    const color = type === 'ask' ? '246, 70, 93' : '14, 203, 129';

    return (
        <div className="flex justify-between text-xs py-0.5 relative hover:bg-[#2b3139] cursor-pointer group">
            <div
                className="absolute top-0 right-0 h-full opacity-10 transition-all duration-200"
                style={{
                    backgroundColor: `rgb(${color})`,
                    width: `${bgWidth}%`
                }}
            />
            <span className={`z-10 w-1/3 text-left pl-2 font-mono ${type === 'ask' ? 'text-[#f6465d]' : 'text-[#0ecb81]'}`}>
                {validPrice.toFixed(2)}
            </span>
            <span className="z-10 w-1/3 text-right text-[#eaecef] font-mono group-hover:text-white">
                {validAmount.toFixed(4)}
            </span>
            <span className="z-10 w-1/3 text-right pr-2 text-[#848e9c] font-mono group-hover:text-[#eaecef]">
                {validTotal.toFixed(2)}
            </span>
        </div>
    );
};

const RecentTrades = ({ currentPrice }) => {
    return (
        <div className="flex flex-col h-full overflow-hidden">
            <div className="flex justify-between px-2 py-2 text-xs text-[#848e9c] font-medium border-b border-[#2b3139]">
                <span className="w-1/3 text-left">Price(USDT)</span>
                <span className="w-1/3 text-right">Amount(BTC)</span>
                <span className="w-1/3 text-right">Time</span>
            </div>
            <div className="flex-1 overflow-y-auto scrollbar-hide p-1">
                {Array.from({ length: 20 }).map((_, i) => (
                    <div key={i} className="flex justify-between text-xs py-1 hover:bg-[#2b3139] cursor-pointer">
                        <span className={`w-1/3 text-left pl-2 font-mono ${i % 2 === 0 ? "text-[#0ecb81]" : "text-[#f6465d]"}`}>
                            {(parseFloat(currentPrice || 95000) + (Math.random() * 20 - 10)).toFixed(2)}
                        </span>
                        <span className="w-1/3 text-right text-[#eaecef] font-mono">{Math.random().toFixed(4)}</span>
                        <span className="w-1/3 text-right pr-2 text-[#848e9c] font-mono">
                            {new Date(Date.now() - i * 1000).toLocaleTimeString([], { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' })}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
};

export const OrderBookWidget = ({ symbol = 'BTCUSDT', currentPrice }) => {
    const [asks, setAsks] = useState([]);
    const [bids, setBids] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('orderbook'); // orderbook | trades
    const [lastPrice, setLastPrice] = useState(null);
    const { routes } = usePage().props;

    // track last price for color comparison
    useEffect(() => {
        if (currentPrice) {
            setLastPrice(prev => {
                if (prev !== currentPrice) return currentPrice;
                return prev;
            });
        }
    }, [currentPrice]);

    const fetchOrderBook = async () => {
        try {
            const response = await axios.get(routes.marketData, {
                params: {
                    symbol: symbol,
                    type: 'orderbook'
                }
            });

            if (response.data.success && response.data.data) {
                const { bids, asks } = response.data.data;

                // Process asks (sell orders)
                // Sort Descending (High -> Low) so that when rendered "flex-col-reverse", 
                // the Lowest Ask (Best Price) is at the bottom (visually connecting to spread).
                const processedAsks = (asks || []).slice(0, 15).map(item => ({
                    price: parseFloat(item.price || item[0] || 0),
                    amount: parseFloat(item.quantity || item[1] || 0),
                    total: parseFloat(item.price || item[0] || 0) * parseFloat(item.quantity || item[1] || 0)
                })).filter(item => item.price > 0).sort((a, b) => b.price - a.price); // High -> Low

                // Process bids (buy orders)
                // Sort Descending (High -> Low) so that when rendered normally "flex-col",
                // the Highest Bid (Best Price) is at the top (visually connecting to spread).
                const processedBids = (bids || []).slice(0, 15).map(item => ({
                    price: parseFloat(item.price || item[0] || 0),
                    amount: parseFloat(item.quantity || item[1] || 0),
                    total: parseFloat(item.price || item[0] || 0) * parseFloat(item.quantity || item[1] || 0)
                })).filter(item => item.price > 0).sort((a, b) => b.price - a.price); // High -> Low

                setAsks(processedAsks);
                setBids(processedBids);
            }
        } catch (error) {
            console.error('Failed to fetch order book:', error);
        }
    };

    useEffect(() => {
        setIsLoading(true);
        fetchOrderBook().then(() => setIsLoading(false));
        const interval = setInterval(fetchOrderBook, 3000);
        return () => clearInterval(interval);
    }, [symbol]);

    // Fallback Mock Data Logic
    const basePrice = currentPrice ? parseFloat(currentPrice) : 95000;

    // Asks: High -> Low (so lowest is at bottom of list)
    const displayAsks = asks.length > 0 ? asks : Array.from({ length: 15 }, (_, i) => ({
        price: basePrice + (15 - i) * 5 + 10, // High...Low
        amount: Math.random() * 2,
        total: Math.random() * 100000
    }));

    // Bids: High -> Low (so highest is at top of list)
    const displayBids = bids.length > 0 ? bids : Array.from({ length: 15 }, (_, i) => ({
        price: basePrice - i * 5 - 10, // High...Low
        amount: Math.random() * 2,
        total: Math.random() * 100000
    }));

    // Spread calculation
    const bestAsk = displayAsks[displayAsks.length - 1]?.price || 0;
    const bestBid = displayBids[0]?.price || 0;
    const spread = bestAsk - bestBid;
    const spreadPercent = bestAsk > 0 ? (spread / bestAsk) * 100 : 0;

    return (
        <div className="flex flex-col h-full bg-[#181a20] overflow-hidden relative">
            {/* Header / Tabs - Draggable Handle Area */}
            <div className="drag-handle h-8 bg-[#2b3139] flex items-center px-2 gap-4 cursor-move shrink-0">
                <button
                    onClick={() => setActiveTab('orderbook')}
                    className={`text-xs font-bold uppercase transition-colors h-full border-b-2 ${activeTab === 'orderbook' ? 'text-[#f0b90b] border-[#f0b90b]' : 'text-[#848e9c] border-transparent hover:text-[#eaecef]'}`}
                >
                    Order Book
                </button>
                <button
                    onClick={() => setActiveTab('trades')}
                    className={`text-xs font-bold uppercase transition-colors h-full border-b-2 ${activeTab === 'trades' ? 'text-[#f0b90b] border-[#f0b90b]' : 'text-[#848e9c] border-transparent hover:text-[#eaecef]'}`}
                >
                    Recent Trades
                </button>
            </div>

            {/* Content Area */}
            {activeTab === 'orderbook' ? (
                <>
                    <div className="flex justify-between px-2 py-2 text-[10px] text-[#848e9c] font-medium border-b border-[#2b3139]">
                        <span className="w-1/3 text-left">Price(USDT)</span>
                        <span className="w-1/3 text-right">Amount(BTC)</span>
                        <span className="w-1/3 text-right">Total</span>
                    </div>

                    <div className="flex-1 overflow-y-auto scrollbar-hide relative flex flex-col">
                        {/* Asks Section (Red) - Rendered Flex-Col (Top of container) but we want visual stack */}
                        {/* Wait, standard view: Sells on Top, Buys on Bottom.
                            Sells should be red. Lowest Price at Bottom.
                            So we simply map displayAsks. But displayAsks is High->Low.
                            So Top of Div = High Price. Bottom of Div = Low Price (Best Ask).
                            Perfect. */}
                        <div className="flex-1 overflow-hidden flex flex-col justify-end pb-1">
                            {displayAsks.map((ask, i) => (
                                <OrderRow key={`ask-${i}`} {...ask} type="ask" />
                            ))}
                        </div>

                        {/* Spread / Current Price */}
                        <div className="py-1 px-4 flex items-center justify-between border-y border-[#2b3139] bg-[#1e2329]">
                            <div className={`text-sm font-bold ${parseFloat(currentPrice) >= parseFloat(lastPrice || 0) ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                                {bestBid > 0 ? ((bestAsk + bestBid) / 2).toFixed(2) : parseFloat(currentPrice || 0).toFixed(2)}
                                <span className="ml-2 text-xs text-[#848e9c] font-normal">
                                    ${bestBid > 0 ? ((bestAsk + bestBid) / 2).toFixed(2) : parseFloat(currentPrice || 0).toFixed(2)}
                                </span>
                            </div>
                            <span className="text-[10px] text-[#848e9c]">
                                Spread: {spread.toFixed(1)} ({spreadPercent.toFixed(2)}%)
                            </span>
                        </div>

                        {/* Bids Section (Green) - Rendered Flex-Col (Bottom of container) */}
                        {/* displayBids is High->Low. 
                            Top of Div = High Price (Best Bid). Bottom of Div = Low Price.
                            Perfect. */}
                        <div className="flex-1 overflow-hidden flex flex-col pt-1">
                            {displayBids.map((bid, i) => (
                                <OrderRow key={`bid-${i}`} {...bid} type="bid" />
                            ))}
                        </div>
                    </div>
                </>
            ) : (
                <RecentTrades currentPrice={currentPrice} />
            )}
        </div>
    );
};
