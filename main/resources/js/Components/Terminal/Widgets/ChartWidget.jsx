import React, { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

export const ChartWidget = ({ symbol = 'BTCUSDT', height = 400, currentPrice }) => {
    const chartContainerRef = useRef();
    const chartRef = useRef(null);
    const candlestickSeriesRef = useRef(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isMounted, setIsMounted] = useState(false);
    const [LightweightCharts, setLightweightCharts] = useState(null);
    const { routes } = usePage().props;

    // Dynamically import lightweight-charts only on client side
    useEffect(() => {
        import('lightweight-charts').then(module => {
            setLightweightCharts(module);
            setIsMounted(true);
        }).catch(err => {
            console.error('Failed to load lightweight-charts:', err);
            setIsMounted(true);
        });
    }, []);

    const fetchData = async () => {
        try {
            // Check if routes.marketData exists
            if (!routes || !routes.marketData) {
                console.error('Market data route not found');
                return [];
            }

            const response = await axios.get(routes.marketData, {
                params: {
                    symbol: symbol,
                    type: 'candlestick',
                    interval: '1h',
                    limit: 100
                }
            });

            if (response.data.success && response.data.data) {
                const formattedData = response.data.data.map(item => ({
                    time: item.time / 1000,
                    open: parseFloat(item.open),
                    high: parseFloat(item.high),
                    low: parseFloat(item.low),
                    close: parseFloat(item.close)
                }));

                formattedData.sort((a, b) => a.time - b.time);
                return formattedData;
            }
        } catch (error) {
            console.error('Failed to fetch chart data:', error);
        }
        return [];
    };

    useEffect(() => {
        if (!chartContainerRef.current || !isMounted || !LightweightCharts) return;

        let resizeObserver = null;

        const initChart = async () => {
            if (!chartContainerRef.current) return;

            const container = chartContainerRef.current;
            const width = container.clientWidth;
            const height = container.clientHeight;

            if (width === 0 || height === 0) return;

            const { createChart, CandlestickSeries } = LightweightCharts;

            // Only create if not exists
            if (!chartRef.current) {
                try {
                    const chart = createChart(container, {
                        layout: {
                            background: { type: 'solid', color: '#0b0e11' },
                            textColor: '#848e9c',
                        },
                        grid: {
                            vertLines: { color: '#2b3139' },
                            horzLines: { color: '#2b3139' },
                        },
                        width: width,
                        height: height,
                        timeScale: {
                            timeVisible: true,
                            secondsVisible: false,
                        },
                        handleScroll: {
                            mouseWheel: true,
                            pressedMouseMove: true,
                            horzTouchDrag: true,
                            vertTouchDrag: true,
                        },
                        handleScale: {
                            axisPressedMouseMove: true,
                            mouseWheel: true,
                            pinch: true,
                        },
                        crosshair: {
                            mode: 0, // Normal crosshair
                        },
                    });

                    if (!chart) {
                        throw new Error("Failed to create chart instance");
                    }

                    chartRef.current = chart;

                    const series = chart.addSeries(CandlestickSeries, {
                        upColor: '#0ecb81',
                        downColor: '#f6465d',
                        borderVisible: false,
                        wickUpColor: '#0ecb81',
                        wickDownColor: '#f6465d',
                    });

                    if (!series) {
                        throw new Error("Failed to create candlestick series");
                    }

                    candlestickSeriesRef.current = series;

                    // Load data only after creation
                    setIsLoading(true);
                    const data = await fetchData();

                    if (data.length > 0) {
                        series.setData(data);
                    } else {
                        // Dynamic fallback data
                        const basePrice = currentPrice ? parseFloat(currentPrice) : 95000;
                        const now = Math.floor(Date.now() / 1000);
                        const mockData = Array.from({ length: 100 }, (_, i) => {
                            const time = now - (100 - i) * 3600;
                            const random = Math.random() * (basePrice * 0.02) - (basePrice * 0.01);
                            return {
                                time: time,
                                open: basePrice + random,
                                high: basePrice + random + (basePrice * 0.005),
                                low: basePrice + random - (basePrice * 0.005),
                                close: basePrice + random + (basePrice * 0.002),
                            };
                        });
                        series.setData(mockData);
                    }
                    setIsLoading(false);

                } catch (err) {
                    console.error("Failed to initialize TradingView Chart:", err);
                    setIsLoading(false);
                    // Cleanup if failed
                    if (chartRef.current) {
                        chartRef.current.remove();
                        chartRef.current = null;
                        candlestickSeriesRef.current = null;
                    }
                }
            } else {
                // Resize if already exists
                chartRef.current.applyOptions({ width, height });
            }
        };

        const timeoutId = setTimeout(() => {
            initChart();

            if (chartContainerRef.current) {
                resizeObserver = new ResizeObserver((entries) => {
                    if (entries && entries.length > 0 && isMounted && LightweightCharts) {
                        const { width, height } = entries[0].contentRect;
                        if (width > 0 && height > 0) {
                            if (chartRef.current) {
                                chartRef.current.applyOptions({ width, height });
                            } else {
                                initChart();
                            }
                        }
                    }
                });
                resizeObserver.observe(chartContainerRef.current);
            }
        }, 100);

        return () => {
            clearTimeout(timeoutId);
            if (resizeObserver) resizeObserver.disconnect();
            if (chartRef.current) {
                chartRef.current.remove();
                chartRef.current = null;
                candlestickSeriesRef.current = null;
            }
        };
    }, [symbol, isMounted, LightweightCharts]);

    return (
        <div className="w-full h-full relative">
            <div
                ref={chartContainerRef}
                className="w-full h-full min-h-[300px]"
                onMouseDown={(e) => e.stopPropagation()}
                onTouchStart={(e) => e.stopPropagation()}
            />
            {isLoading && (
                <div className="absolute inset-0 flex items-center justify-center bg-[#0b0e11] bg-opacity-50 z-10">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#0ecb81]"></div>
                </div>
            )}
        </div>
    );
};
