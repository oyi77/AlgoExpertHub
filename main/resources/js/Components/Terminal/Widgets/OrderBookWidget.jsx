import React, { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

const OrderRow = ({ price, amount, total, type }) => {
    // Validate and convert to numbers, default to 0 if invalid
    const validPrice = price != null && !isNaN(price) ? parseFloat(price) : 0;
    const validAmount = amount != null && !isNaN(amount) ? parseFloat(amount) : 0;
    const validTotal = total != null && !isNaN(total) ? parseFloat(total) : 0;

    const bgWidth = Math.min((validAmount / 10) * 100, 100);
    const color = type === 'ask' ? '246, 70, 93' : '14, 203, 129';

    return (
        <div className="flex justify-between text-xs py-0.5 relative hover:bg-[#2b3139] cursor-pointer">
            <div
                className="absolute top-0 right-0 h-full opacity-10"
                style={{
                    backgroundColor: `rgb(${color})`,
                    width: `${bgWidth}%`
                }}
            />
            <span className={`z-10 w-1/3 text-left pl-2 ${type === 'ask' ? 'text-[#f6465d]' : 'text-[#0ecb81]'}`}>
                {validPrice.toFixed(2)}
            </span>
            <span className="z-10 w-1/3 text-right text-[#eaecef]">
                {validAmount.toFixed(4)}
            </span>
            <span className="z-10 w-1/3 text-right pr-2 text-[#848e9c]">
                {validTotal.toFixed(2)}
            </span>
        </div>
    );
};

export const OrderBookWidget = ({ symbol = 'BTCUSDT', currentPrice }) => {
    const [asks, setAsks] = useState([]);
    const [bids, setBids] = useState([]);
    const [lastPrice, setLastPrice] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const { routes } = usePage().props;

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

                // Process asks (sell orders) - sorted descending (highest price at top)
                const processedAsks = (asks || []).slice(0, 15).map(item => {
                    const price = parseFloat(item.price || item[0] || 0);
                    const amount = parseFloat(item.quantity || item[1] || 0);
                    return {
                        price: price,
                        amount: amount,
                        total: price * amount
                    };
                }).filter(item => item.price > 0 && item.amount > 0).sort((a, b) => b.price - a.price);

                // Process bids (buy orders) - sorted descending (highest price at top)
                const processedBids = (bids || []).slice(0, 15).map(item => {
                    const price = parseFloat(item.price || item[0] || 0);
                    const amount = parseFloat(item.quantity || item[1] || 0);
                    return {
                        price: price,
                        amount: amount,
                        total: price * amount
                    };
                }).filter(item => item.price > 0 && item.amount > 0);

                setAsks(processedAsks);
                setBids(processedBids);
            }
        } catch (error) {
            console.error('Failed to fetch order book:', error);
        }
    };

    // Initial fetch and interval
    useEffect(() => {
        setIsLoading(true);
        fetchOrderBook().then(() => setIsLoading(false));

        // Poll every 5 seconds
        const interval = setInterval(fetchOrderBook, 5000);
        return () => clearInterval(interval);
    }, [symbol]);

    // Fallback data if empty
    const basePrice = currentPrice ? parseFloat(currentPrice) : 95000;
    
    const displayAsks = asks.length > 0 ? asks : Array.from({ length: 15 }, (_, i) => ({
        price: basePrice + (i * (basePrice * 0.0001)) + 10,
        amount: Math.random() * 2,
        total: Math.random() * 100000
    })).reverse();

    const displayBids = bids.length > 0 ? bids : Array.from({ length: 15 }, (_, i) => ({
        price: basePrice - (i * (basePrice * 0.0001)) - 10,
        amount: Math.random() * 2,
        total: Math.random() * 100000
    }));

    return (
        <div className="flex flex-col h-full bg-[#0b0e11] overflow-hidden relative">
            <div className="flex justify-between px-2 py-2 text-xs text-[#848e9c] font-medium border-b border-[#2b3139]">
                <span className="w-1/3 text-left">Price(USDT)</span>
                <span className="w-1/3 text-right">Amount(BTC)</span>
                <span className="w-1/3 text-right">Total</span>
            </div>

            <div className="flex-1 overflow-y-auto scrollbar-hide relative">
                {isLoading && asks.length === 0 && (
                    <div className="absolute inset-0 flex items-center justify-center bg-[#0b0e11] bg-opacity-50 z-10">
                        <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-[#0ecb81]"></div>
                    </div>
                )}

                <div className="flex flex-col-reverse">
                    {displayAsks.map((ask, i) => (
                        <OrderRow key={i} {...ask} type="ask" />
                    ))}
                </div>

                <div className="py-2 text-center border-y border-[#2b3139] my-1">
                    <span className="text-lg font-bold text-[#0ecb81]">
                        {displayBids[0]?.price ? parseFloat(displayBids[0].price).toFixed(2) : '---'}
                    </span>
                    <span className="text-xs text-[#848e9c] ml-2">
                        ≈ ${displayBids[0]?.price ? parseFloat(displayBids[0].price).toFixed(2) : '---'}
                    </span>
                </div>

                <div className="flex flex-col">
                    {displayBids.map((bid, i) => (
                        <OrderRow key={i} {...bid} type="bid" />
                    ))}
                </div>
            </div>
        </div>
    );
};
