import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';

export const SymbolSelector = ({ currentSymbol, onSymbolChange }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [markets, setMarkets] = useState([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [loading, setLoading] = useState(false);
    const [recentSymbols, setRecentSymbols] = useState([]);
    const dropdownRef = useRef(null);
    const searchInputRef = useRef(null);

    // Load recent symbols from localStorage
    useEffect(() => {
        const stored = localStorage.getItem('recentSymbols');
        if (stored) {
            try {
                setRecentSymbols(JSON.parse(stored));
            } catch (e) {
                setRecentSymbols([]);
            }
        }
    }, []);

    // Fetch markets when dropdown opens
    useEffect(() => {
        if (isOpen && markets.length === 0) {
            fetchMarkets();
        }
    }, [isOpen]);

    // Focus search input when opened
    useEffect(() => {
        if (isOpen && searchInputRef.current) {
            setTimeout(() => searchInputRef.current?.focus(), 100);
        }
    }, [isOpen]);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
            return () => document.removeEventListener('mousedown', handleClickOutside);
        }
    }, [isOpen]);

    const fetchMarkets = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/api/trading-terminal/trading-pairs');
            if (response.data && response.data.success) {
                setMarkets(Array.isArray(response.data.data) ? response.data.data : []);
            }
        } catch (error) {
            console.error('Failed to fetch markets:', error);
            setMarkets([]);
        } finally {
            setLoading(false);
        }
    };

    const handleSymbolSelect = (symbol) => {
        // Update recent symbols
        const updated = [symbol, ...recentSymbols.filter(s => s !== symbol)].slice(0, 5);
        setRecentSymbols(updated);
        localStorage.setItem('recentSymbols', JSON.stringify(updated));

        // Notify parent
        onSymbolChange(symbol);
        setIsOpen(false);
        setSearchQuery('');
    };

    // Filter markets based on search query
    const filteredMarkets = markets.filter(market => {
        if (!searchQuery) return true;
        const query = searchQuery.toLowerCase();
        const symbol = (market.symbol || '').toLowerCase();
        const displaySymbol = (market.displaySymbol || '').toLowerCase();
        const name = (market.name || '').toLowerCase();
        return symbol.includes(query) || displaySymbol.includes(query) || name.includes(query);
    });

    // Group by category (crypto, forex, indices, etc.)
    const groupedMarkets = filteredMarkets.reduce((acc, market) => {
        const category = (market.category || 'OTHER').toUpperCase();
        if (!acc[category]) acc[category] = [];
        acc[category].push(market);
        return acc;
    }, {});

    const categoryOrder = ['CRYPTO', 'FOREX', 'INDICES', 'COMMODITIES', 'STOCKS', 'OTHER'];
    const sortedGroups = categoryOrder.filter(c => groupedMarkets[c]);

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Trigger Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="flex items-center gap-1 hover:bg-[#2b3139] px-2 py-1 rounded transition-colors"
            >
                <span className="text-[#eaecef] font-bold text-sm">{currentSymbol}</span>
                <svg
                    className={`w-3 h-3 text-[#848e9c] transition-transform ${isOpen ? 'rotate-180' : ''}`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {/* Dropdown */}
            {isOpen && (
                <div className="absolute top-full left-0 mt-1 w-80 bg-[#181a20] border border-[#2b3139] rounded shadow-xl z-50 max-h-[500px] flex flex-col">
                    {/* Search Input */}
                    <div className="p-3 border-b border-[#2b3139]">
                        <input
                            ref={searchInputRef}
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search markets..."
                            className="w-full bg-[#0b0e11] text-[#eaecef] text-sm px-3 py-2 rounded border border-[#2b3139] focus:border-[#0ecb81] focus:outline-none"
                        />
                    </div>

                    {/* Content */}
                    <div className="overflow-y-auto flex-1 scrollbar-thin scrollbar-thumb-[#2b3139] scrollbar-track-transparent">
                        {loading ? (
                            <div className="flex items-center justify-center py-8">
                                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-[#0ecb81]"></div>
                            </div>
                        ) : (
                            <>
                                {/* Recent Symbols */}
                                {!searchQuery && recentSymbols.length > 0 && (
                                    <div className="p-3 border-b border-[#2b3139]">
                                        <div className="text-xs text-[#848e9c] mb-2 font-medium">RECENT</div>
                                        <div className="space-y-1">
                                            {recentSymbols.map(symbol => (
                                                <button
                                                    key={symbol}
                                                    onClick={() => handleSymbolSelect(symbol)}
                                                    className="w-full text-left px-2 py-1.5 rounded hover:bg-[#2b3139] transition-colors flex items-center justify-between group"
                                                >
                                                    <span className="text-sm text-[#eaecef]">{symbol}</span>
                                                    {symbol === currentSymbol && (
                                                        <span className="text-xs text-[#0ecb81]">✓</span>
                                                    )}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Grouped Markets */}
                                {sortedGroups.length > 0 ? (
                                    sortedGroups.map(category => (
                                        <div key={category} className="p-3 border-b border-[#2b3139] last:border-b-0">
                                            <div className="text-xs text-[#848e9c] mb-2 font-medium">{category}</div>
                                            <div className="space-y-1">
                                                {groupedMarkets[category].map(market => (
                                                    <button
                                                        key={market.symbol}
                                                        onClick={() => handleSymbolSelect(market.symbol)}
                                                        className="w-full text-left px-2 py-1.5 rounded hover:bg-[#2b3139] transition-colors flex items-center justify-between group"
                                                    >
                                                        <div className="flex flex-col">
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-sm text-[#eaecef]">{market.displaySymbol || market.symbol}</span>
                                                                {market.category === 'crypto' && (
                                                                    <span className="text-[10px] bg-[#2b3139] text-[#848e9c] px-1 rounded">100x</span>
                                                                )}
                                                            </div>
                                                            {market.name && (
                                                                <span className="text-xs text-[#848e9c]">{market.name}</span>
                                                            )}
                                                        </div>
                                                        <div className="flex flex-col items-end">
                                                            <span className="text-xs text-[#eaecef]">{market.price}</span>
                                                            <span className={`text-[10px] ${parseFloat(market.change24h) >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]'}`}>
                                                                {parseFloat(market.change24h) >= 0 ? '+' : ''}{market.change24h}%
                                                            </span>
                                                        </div>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="p-8 text-center text-sm text-[#848e9c]">
                                        {searchQuery ? 'No markets found' : 'No markets available'}
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};
