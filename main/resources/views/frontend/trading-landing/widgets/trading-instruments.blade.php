@php
    // Use $content if available (from Section::render), fallback to Config::builder
    if (isset($content) && is_object($content)) {
        $instruments = (object)['content' => $content];
    } else {
        $instruments = Config::builder('trading_instruments');
    }
@endphp

<section class="trading-instruments-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">{{ Config::trans(($instruments->content->title ?? null) ?? 'Explore Global Market Opportunities') }}</h2>
            <p class="section-description">{{ Config::trans(($instruments->content->description ?? null) ?? 'Diversified Trading Instruments Across Major Asset Classes') }}</p>
        </div>
        
        <div class="instruments-container">
            <div class="category-tabs">
                <button class="tab-btn active" data-category="forex">Forex</button>
                <button class="tab-btn" data-category="metal">Metal</button>
                <button class="tab-btn" data-category="energies">Energies</button>
                <button class="tab-btn" data-category="indices">Indices</button>
            </div>
            
            <div class="instruments-table-wrapper">
                <div class="sub-tabs">
                    <button class="sub-tab active" data-sub="majors">FX Majors</button>
                    <button class="sub-tab" data-sub="minors">FX Minors</button>
                    <button class="sub-tab" data-sub="exotics">FX Exotics</button>
                </div>
                
                <div class="instruments-table">
                    <div class="table-header">
                        <div class="table-cell">Instrument Name</div>
                        <div class="table-cell">Contract Size</div>
                        <div class="table-cell">Leverage</div>
                        <div class="table-cell">
                            Swap Long
                            <i class="las la-question-circle" data-bs-toggle="tooltip" title="Swap rate for long positions"></i>
                        </div>
                        <div class="table-cell">
                            Swap Short
                            <i class="las la-question-circle" data-bs-toggle="tooltip" title="Swap rate for short positions"></i>
                        </div>
                        <div class="table-cell">Trading Hours: GMT+3</div>
                    </div>
                    
                    <div class="table-body">
                        <div class="table-row">
                            <div class="table-cell">
                                <div class="currency-flags">
                                    <span class="flag flag-gb"></span>
                                    <span class="flag flag-us"></span>
                                </div>
                                <span class="currency-pair">EUR / USD</span>
                            </div>
                            <div class="table-cell">100000</div>
                            <div class="table-cell">1:100</div>
                            <div class="table-cell">-3.53</div>
                            <div class="table-cell">1.45</div>
                            <div class="table-cell">Monday - Friday (00:00 - 24:00)</div>
                        </div>
                        
                        <div class="table-row">
                            <div class="table-cell">
                                <div class="currency-flags">
                                    <span class="flag flag-us"></span>
                                    <span class="flag flag-jp"></span>
                                </div>
                                <span class="currency-pair">USD / JPY</span>
                            </div>
                            <div class="table-cell">100000</div>
                            <div class="table-cell">1:200</div>
                            <div class="table-cell">-2.10</div>
                            <div class="table-cell">1.30</div>
                            <div class="table-cell">Monday - Friday (00:00 - 24:00)</div>
                        </div>
                        
                        <div class="table-row">
                            <div class="table-cell">
                                <div class="currency-flags">
                                    <span class="flag flag-au"></span>
                                    <span class="flag flag-us"></span>
                                </div>
                                <span class="currency-pair">AUD / USD</span>
                            </div>
                            <div class="table-cell">100000</div>
                            <div class="table-cell">1:100</div>
                            <div class="table-cell">-2.75</div>
                            <div class="table-cell">1.50</div>
                            <div class="table-cell">Monday - Friday (00:00 - 24:00)</div>
                        </div>
                        
                        <div class="table-row">
                            <div class="table-cell">
                                <div class="currency-flags">
                                    <span class="flag flag-nz"></span>
                                    <span class="flag flag-us"></span>
                                </div>
                                <span class="currency-pair">NZD / USD</span>
                            </div>
                            <div class="table-cell">100000</div>
                            <div class="table-cell">1:200</div>
                            <div class="table-cell">-1.65</div>
                            <div class="table-cell">0.95</div>
                            <div class="table-cell">Monday - Friday (00:00 - 24:00)</div>
                        </div>
                        
                        <div class="table-row">
                            <div class="table-cell">
                                <div class="currency-flags">
                                    <span class="flag flag-us"></span>
                                    <span class="flag flag-ca"></span>
                                </div>
                                <span class="currency-pair">USD / CAD</span>
                            </div>
                            <div class="table-cell">100000</div>
                            <div class="table-cell">1:150</div>
                            <div class="table-cell">-2.25</div>
                            <div class="table-cell">1.45</div>
                            <div class="table-cell">Monday - Friday (00:00 - 24:00)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

