<section class="footer-cta-section">
    <div class="registration-banner">
        <div class="container">
            <h2 class="banner-title">Open Your Account in Minutes with Our Simple Registration Process</h2>

            <div class="banner-actions">
                <a href="{{ route('user.register') }}" class="btn btn-banner-cta btn-interactive" data-action="register">
                    <i class="fas fa-user-plus"></i> Start Trading Now
                </a>
                <p class="banner-subtitle">Join over 10,000+ traders worldwide</p>
            </div>

            <div class="registration-steps">
                <div class="step-item">
                    <div class="step-icon">
                        <i class="las la-wallet"></i>
                    </div>
                    <div class="step-line"></div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <i class="las la-shield-check"></i>
                    </div>
                    <div class="step-line"></div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <i class="las la-credit-card"></i>
                    </div>
                    <div class="step-line"></div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <i class="las la-folder-open"></i>
                    </div>
                    <div class="step-line"></div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <i class="las la-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="trading-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column footer-about">
                    <a href="{{ route('home') }}" class="footer-logo">
                        <img src="{{ Config::getFile('logo', Config::config()->logo) }}" alt="{{ Config::config()->appname ?? 'Logo' }}" loading="lazy">
                    </a>
                    <div class="footer-address">
                        <p class="address-label">Office Address:</p>
                        <p class="address-text">25th Floor, Coral Heights Tower, Seaview Blvd, Kingstown, St. Vincent and the Grenadines</p>
                    </div>
                    <div class="footer-address">
                        <p class="address-label">Representative Office:</p>
                        <p class="address-text">Suite 402, Liberty Plaza, 1234 Market Street, San Francisco, CA 94103, United States</p>
                    </div>
                    <div class="footer-social">
                        <a href="#" class="social-link" aria-label="Instagram"><i class="lab la-instagram"></i></a>
                        <a href="#" class="social-link" aria-label="LinkedIn"><i class="lab la-linkedin"></i></a>
                        <a href="#" class="social-link" aria-label="Facebook"><i class="lab la-facebook"></i></a>
                        <a href="#" class="social-link" aria-label="Twitter"><i class="lab la-twitter"></i></a>
                    </div>
                </div>
                
                <div class="footer-column footer-menu">
                    <div class="menu-group">
                        <h4 class="menu-title">Company</h4>
                        <ul class="menu-list">
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Insight</a></li>
                            <li><a href="{{ route('user.login') }}">Login</a></li>
                            <li><a href="{{ route('user.register') }}">Register</a></li>
                        </ul>
                    </div>
                    
                    <div class="menu-group">
                        <h4 class="menu-title">Products</h4>
                        <ul class="menu-list">
                            <li><a href="#">Trading Accounts</a></li>
                            <li><a href="#">Instrument</a></li>
                            <li><a href="#">Platforms</a></li>
                        </ul>
                    </div>
                    
                    <div class="menu-group">
                        <h4 class="menu-title">Tools</h4>
                        <ul class="menu-list">
                            <li><a href="#">Event Forecast</a></li>
                            <li><a href="#">News & Update</a></li>
                            <li><a href="#">Market Overview</a></li>
                        </ul>
                    </div>
                    
                    <div class="menu-group">
                        <h4 class="menu-title">Support</h4>
                        <ul class="menu-list">
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">FAQ</a></li>
                            <li><a href="#">Partnership</a></li>
                            <li><a href="#">Regulation</a></li>
                            <li><a href="#">Policy</a></li>
                            <li><a href="#">Client Agreement</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="copyright">Copyright © {{ date('Y') }} {{ Config::config()->appname ?? 'AlgoExpertHub' }} All rights reserved</p>
            </div>
        </div>
    </footer>
</section>

