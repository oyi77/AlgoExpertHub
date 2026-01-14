import React, { useState, useEffect } from 'react';

export const OrderFormWidget = ({ symbol = 'BTCUSDT', currentPrice, balance = { USDT: 1000, BTC: 0.05 } }) => {
    const [side, setSide] = useState('buy'); // buy | sell
    const [type, setType] = useState('limit'); // limit | market
    const [price, setPrice] = useState('');
    const [amount, setAmount] = useState('');
    const [total, setTotal] = useState('');
    const [sliderValue, setSliderValue] = useState(0);

    // Update price when currentPrice changes (if empty)
    useEffect(() => {
        if (currentPrice && !price && type === 'limit') {
            setPrice(currentPrice);
        }
    }, [currentPrice]);

    // Handle calculations
    useEffect(() => {
        if (!price || !amount) {
            if (sliderValue === 0) setTotal('');
            return;
        }
        
        const p = parseFloat(price);
        const a = parseFloat(amount);
        
        if (!isNaN(p) && !isNaN(a)) {
            setTotal((p * a).toFixed(2));
        }
    }, [price, amount]);

    const handleSliderChange = (value) => {
        setSliderValue(value);
        
        // Calculate based on available balance
        const avail = side === 'buy' ? balance.USDT : balance.BTC;
        
        if (side === 'buy') {
            const budget = avail * (value / 100);
            const p = parseFloat(price || currentPrice || 0);
            if (p > 0) {
                const amt = budget / p;
                setAmount(amt.toFixed(6));
                setTotal(budget.toFixed(2));
            }
        } else {
            const amt = avail * (value / 100);
            setAmount(amt.toFixed(6));
            if (price) {
                setTotal((amt * parseFloat(price)).toFixed(2));
            }
        }
    };

    const handleAmountChange = (val) => {
        setAmount(val);
        setSliderValue(0); 
        // Recalculate total if price exists
        if (price && val) {
            setTotal((parseFloat(price) * parseFloat(val)).toFixed(2));
        }
    };

    const handleTotalChange = (val) => {
        setTotal(val);
        setSliderValue(0);
        // Recalculate amount if price exists
        if (price && val) {
            setAmount((parseFloat(val) / parseFloat(price)).toFixed(6));
        }
    };

    return (
        <div className="flex flex-col h-full bg-[#1e2329] text-sm overflow-hidden">
            {/* Header / Tabs */}
            <div className="flex w-full">
                <button
                    onClick={() => setSide('buy')}
                    className={`flex-1 py-3 font-medium text-sm transition-colors relative ${
                        side === 'buy' ? 'text-white' : 'text-[#848e9c] hover:text-[#eaecef] bg-[#2b3139]/30'
                    }`}
                >
                    Buy
                    {side === 'buy' && (
                        <div className="absolute top-0 left-0 right-0 h-0.5 bg-[#0ecb81]" />
                    )}
                </button>
                <button
                    onClick={() => setSide('sell')}
                    className={`flex-1 py-3 font-medium text-sm transition-colors relative ${
                        side === 'sell' ? 'text-white' : 'text-[#848e9c] hover:text-[#eaecef] bg-[#2b3139]/30'
                    }`}
                >
                    Sell
                    {side === 'sell' && (
                        <div className="absolute top-0 left-0 right-0 h-0.5 bg-[#f6465d]" />
                    )}
                </button>
            </div>

            {/* Order Type Selector */}
            <div className="flex items-center px-4 mt-3 mb-2 gap-4">
                <button
                    onClick={() => setType('limit')}
                    className={`text-xs font-medium transition-colors hover:text-[#f0b90b] ${
                        type === 'limit' ? 'text-[#f0b90b]' : 'text-[#848e9c]'
                    }`}
                >
                    Limit
                </button>
                <button
                    onClick={() => setType('market')}
                    className={`text-xs font-medium transition-colors hover:text-[#f0b90b] ${
                        type === 'market' ? 'text-[#f0b90b]' : 'text-[#848e9c]'
                    }`}
                >
                    Market
                </button>
            </div>

            {/* Available Balance */}
            <div className="flex justify-between px-4 text-[10px] text-[#848e9c] mb-2">
                <span>Avail</span>
                <span className="text-[#eaecef]">
                    {side === 'buy' 
                        ? `${balance.USDT} USDT` 
                        : `${balance.BTC} BTC`
                    }
                </span>
            </div>

            {/* Form Inputs */}
            <div className="flex-1 px-4 space-y-3 overflow-y-auto">
                {/* Price Input */}
                <div className="bg-[#2b3139] rounded flex items-center px-2 h-10 border border-transparent focus-within:border-[#f0b90b] transition-colors">
                    <span className="text-[#848e9c] w-12 text-xs">Price</span>
                    {type === 'limit' ? (
                        <input
                            type="number"
                            value={price}
                            onChange={(e) => setPrice(e.target.value)}
                            className="flex-1 bg-transparent text-right text-[#eaecef] focus:outline-none text-sm placeholder-[#474d57]"
                            placeholder="Price"
                        />
                    ) : (
                        <input
                            type="text"
                            value="Market"
                            disabled
                            className="flex-1 bg-transparent text-right text-[#eaecef] focus:outline-none text-sm cursor-not-allowed opacity-70"
                        />
                    )}
                    <span className="text-[#848e9c] ml-2 text-xs">USDT</span>
                </div>

                {/* Amount Input */}
                <div className="bg-[#2b3139] rounded flex items-center px-2 h-10 border border-transparent focus-within:border-[#f0b90b] transition-colors">
                    <span className="text-[#848e9c] w-12 text-xs">Amount</span>
                    <input
                        type="number"
                        value={amount}
                        onChange={(e) => handleAmountChange(e.target.value)}
                        className="flex-1 bg-transparent text-right text-[#eaecef] focus:outline-none text-sm placeholder-[#474d57]"
                        placeholder="Amount"
                    />
                    <span className="text-[#848e9c] ml-2 text-xs">BTC</span>
                </div>

                {/* Percentage Slider */}
                <div className="px-1">
                    <input
                        type="range"
                        min="0"
                        max="100"
                        step="1"
                        value={sliderValue}
                        onChange={(e) => handleSliderChange(parseFloat(e.target.value))}
                        className="w-full h-1 bg-[#474d57] rounded-lg appearance-none cursor-pointer accent-[#f0b90b]"
                    />
                    <div className="flex justify-between mt-1">
                        {[0, 25, 50, 75, 100].map(val => (
                            <div 
                                key={val} 
                                onClick={() => handleSliderChange(val)}
                                className="w-2 h-2 bg-[#474d57] rounded-full cursor-pointer hover:bg-[#eaecef] transition-colors" 
                            />
                        ))}
                    </div>
                </div>

                {/* Total Input */}
                {type === 'limit' && (
                    <div className="bg-[#2b3139] rounded flex items-center px-2 h-10 border border-transparent focus-within:border-[#f0b90b] transition-colors">
                        <span className="text-[#848e9c] w-12 text-xs">Total</span>
                        <input
                            type="number"
                            value={total}
                            onChange={(e) => handleTotalChange(e.target.value)}
                            className="flex-1 bg-transparent text-right text-[#eaecef] focus:outline-none text-sm placeholder-[#474d57]"
                            placeholder="Total"
                        />
                        <span className="text-[#848e9c] ml-2 text-xs">USDT</span>
                    </div>
                )}

                {/* Submit Button */}
                <button
                    className={`w-full h-10 rounded font-bold text-sm transition-opacity hover:opacity-90 mt-2 ${
                        side === 'buy' ? 'bg-[#0ecb81] text-white' : 'bg-[#f6465d] text-white'
                    }`}
                >
                    {side === 'buy' ? 'Buy BTC' : 'Sell BTC'}
                </button>
            </div>
        </div>
    );
};
