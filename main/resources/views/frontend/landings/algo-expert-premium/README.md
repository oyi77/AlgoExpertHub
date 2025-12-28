# AlgoExpertHub Premium Landing Page

A high-performance, conversion-optimized landing page for AlgoExpertHub trading bot platform.

## Features

### 🎨 Design
- **Glassmorphism UI**: Modern frosted glass effects with backdrop blur
- **Gradient Accents**: Eye-catching gradient text and buttons
- **Dark Theme**: Professional dark color scheme optimized for trading platforms
- **Responsive Design**: Mobile-first approach with breakpoints at 768px and 992px

### 🚀 Sections

1. **Global Ticker Bar**
   - Live price marquee for BTC/USDT, ETH/USDT, SOL/USDT
   - Auto-updates every 5 seconds (simulated)
   - Green/red color coding for price changes

2. **Sticky Navigation**
   - Glassmorphism effect with backdrop blur
   - Smooth scroll to sections
   - High-contrast CTA buttons

3. **Hero Section**
   - Compelling headline with gradient text
   - Interactive strategy builder mockup
   - Animated statistics counters
   - Shimmer effect on primary CTA

4. **Feature Bento Grid**
   - DCA Bots with sparkline chart
   - Grid Trading visualization
   - Signal Integration badges
   - Paper Trading toggle switch

5. **Strategy Canvas**
   - Interactive rule builder mockup
   - Dropdown-based condition/action selectors
   - Visual strategy preview

6. **Performance & Trust**
   - Animated statistics counters
   - Security card with feature checklist
   - API security details

7. **Pricing Tiers**
   - Three pricing plans
   - Featured plan with glowing border
   - Clear feature comparison

8. **Advanced Footer**
   - Risk warning (fintech standard)
   - Categorized links
   - Social media icons

### ⚡ Performance

- **Optimized CSS**: No external CSS frameworks, custom utility classes
- **Lazy Animations**: Intersection Observer for scroll-triggered animations
- **Efficient JavaScript**: Minimal footprint, Alpine.js for interactivity
- **Icon Loading**: Lucide icons loaded from CDN with fallback

### 🎯 Conversion Optimization

- **Clear CTAs**: Multiple strategically placed call-to-action buttons
- **Social Proof**: Statistics and trust indicators
- **Value Proposition**: Clear benefits and features
- **Risk Transparency**: Standard fintech risk warning

## Usage

### Access the Landing Page

The landing page is accessible at:
```
/landing/algo-expert-premium
```

Or use the named route:
```php
route('landing.algo-expert-premium')
```

### Customization

#### Update Prices in Ticker

Edit the ticker items in `layout/master.blade.php`:
```html
<div class="ticker-item">
    <span class="ticker-symbol">BTC/USDT</span>
    <span class="ticker-price">$96,432.50</span>
    <span class="ticker-change positive">+1.2%</span>
</div>
```

#### Connect Real Price API

Replace the simulated price updates in the JavaScript section:
```javascript
function updateTickerPrices() {
    // Replace with real API call
    fetch('/api/prices')
        .then(response => response.json())
        .then(data => {
            // Update ticker items with real data
        });
}
```

#### Customize Colors

Edit CSS variables in `layout/master.blade.php`:
```css
:root {
    --primary: #3b82f6;
    --success: #10b981;
    --accent-gradient: linear-gradient(135deg, var(--primary), #8b5cf6);
}
```

#### Update Statistics

Edit the counter values in `index.blade.php`:
```html
<div class="stat-value" data-count="2.4">$0</div>
<div class="stat-value" data-count="150">0</div>
```

## Dependencies

- **Alpine.js**: For interactive components (loaded via CDN)
- **Lucide Icons**: For iconography (loaded via CDN)
- **Bootstrap 5**: For grid system and utilities (from existing assets)
- **jQuery**: For DOM manipulation (from existing assets)

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Metrics

Target Core Web Vitals:
- **LCP**: < 2.5s
- **FID**: < 100ms
- **CLS**: < 0.1

## Accessibility

- WCAG 2.1 AA compliant
- Keyboard navigation support
- Focus indicators on interactive elements
- Semantic HTML structure
- ARIA labels where needed

## Notes

- The strategy builder is a **visual mockup only** - not functional
- Price ticker uses simulated data - replace with real API
- All animations respect `prefers-reduced-motion`
- Icons are loaded asynchronously to prevent layout shift

## Future Enhancements

1. **Real-time Price API**: Connect to exchange APIs for live prices
2. **A/B Testing**: Create variants for different headlines
3. **Analytics**: Add event tracking for CTA clicks
4. **Video Demo**: Replace "Watch Demo" with embedded video
5. **Testimonials**: Add customer testimonials section
6. **Live Chat**: Integrate support chat widget

