<!-- Footer CTA Banner -->
<section class="tv-footer-cta" id="footer-cta">
    <div class="tv-container">
        <h2 class="tv-footer-cta-title tv-gradient-text">
            Ready to Start Your Trading Journey?
        </h2>
        
        <div class="tv-footer-cta-actions">
            <a href="{{ route('user.register') }}" class="tv-btn tv-btn-primary tv-btn-lg">
                Create Free Account
                <i class="fas fa-arrow-right"></i>
            </a>
            <p class="tv-footer-cta-subtext">
                No credit card required • Start in minutes
            </p>
        </div>
        
        <!-- Registration Steps -->
        <div class="tv-registration-steps">
            <div class="tv-step">
                <div class="tv-step-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <div class="tv-step-line"></div>
            <div class="tv-step">
                <div class="tv-step-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
            </div>
            <div class="tv-step-line"></div>
            <div class="tv-step">
                <div class="tv-step-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Network Diagram Background -->
    <div class="tv-network-diagram">
        <!-- SVG lines and nodes will be added here -->
    </div>
</section>

<!-- Main Footer -->
<footer class="tv-footer">
    <div class="tv-container">
        <div class="tv-footer-grid">
            <!-- Brand Column -->
            <div class="tv-footer-brand">
                <img src="{{ Config::getFile('logo', Config::config()->logo ?? '') }}" alt="Logo" class="tv-footer-logo">
                <p class="tv-footer-desc">
                    {{ Config::config()->appname ?? 'AlgoExpertHub' }} - Your trusted partner in trading success. Access professional signals, expert analysis, and powerful tools.
                </p>
                <div class="tv-footer-social">
                    @if(Config::config()->facebook)
                        <a href="{{ Config::config()->facebook }}" class="tv-social-link" target="_blank">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    @endif
                    @if(Config::config()->twitter)
                        <a href="{{ Config::config()->twitter }}" class="tv-social-link" target="_blank">
                            <i class="fab fa-twitter"></i>
                        </a>
                    @endif
                    @if(Config::config()->instagram)
                        <a href="{{ Config::config()->instagram }}" class="tv-social-link" target="_blank">
                            <i class="fab fa-instagram"></i>
                        </a>
                    @endif
                    @if(Config::config()->linkedin)
                        <a href="{{ Config::config()->linkedin }}" class="tv-social-link" target="_blank">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    @endif
                </div>
            </div>
            
            <!-- Company -->
            <div class="tv-footer-column">
                <h4 class="tv-footer-title">Company</h4>
                <div class="tv-footer-links">
                    <a href="{{ route('home') }}" class="tv-footer-link">Home</a>
                    <a href="#" class="tv-footer-link">About Us</a>
                    <a href="#" class="tv-footer-link">Contact</a>
                    <a href="#" class="tv-footer-link">Blog</a>
                </div>
            </div>
            
            <!-- Products -->
            <div class="tv-footer-column">
                <h4 class="tv-footer-title">Products</h4>
                <div class="tv-footer-links">
                    <a href="#account-types" class="tv-footer-link">Pricing</a>
                    <a href="#why-choose-us" class="tv-footer-link">Features</a>
                    <a href="#market-trends" class="tv-footer-link">Markets</a>
                    @auth
                        <a href="{{ route('user.signal.all') }}" class="tv-footer-link">Signals</a>
                    @endauth
                </div>
            </div>
            
            <!-- Resources -->
            <div class="tv-footer-column">
                <h4 class="tv-footer-title">Resources</h4>
                <div class="tv-footer-links">
                    <a href="#" class="tv-footer-link">Help Center</a>
                    <a href="#" class="tv-footer-link">FAQ</a>
                    <a href="#" class="tv-footer-link">Tutorials</a>
                    <a href="#" class="tv-footer-link">API Docs</a>
                </div>
            </div>
            
            <!-- Legal -->
            <div class="tv-footer-column">
                <h4 class="tv-footer-title">Legal</h4>
                <div class="tv-footer-links">
                    <a href="#" class="tv-footer-link">Privacy Policy</a>
                    <a href="#" class="tv-footer-link">Terms of Service</a>
                    <a href="#" class="tv-footer-link">Cookie Policy</a>
                    <a href="#" class="tv-footer-link">Disclaimer</a>
                </div>
            </div>
        </div>
        
        <!-- Bottom -->
        <div class="tv-footer-bottom">
            <p class="tv-footer-copyright">
                © {{ date('Y') }} {{ Config::config()->appname ?? 'AlgoExpertHub' }}. All rights reserved.
            </p>
        </div>
    </div>
</footer>