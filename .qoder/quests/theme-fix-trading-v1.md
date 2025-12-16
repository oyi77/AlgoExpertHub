# Trading-V1 Theme Figma Alignment Design

## Overview
Fix the trading-v1 theme implementation to precisely match the Figma design specifications for all landing page sections including Hero, Why Choose Us, Trade Sections, Plans, Blog CTA, and Footer.

## Business Context
The trading-v1 theme was implemented but has visual inconsistencies with the approved Figma design. Users expect a professional, polished landing page that exactly matches the design system specifications to build trust and credibility.

## Design Goals
- Achieve pixel-perfect alignment with Figma design specifications
- Maintain consistent design tokens across all components
- Ensure responsive behavior matches design breakpoints
- Preserve existing functionality while updating visual presentation
- Implement proper glassmorphism effects and gradients as designed

## Figma Design Analysis

### Design System Tokens (from Figma)

| Token Category | Specification |
|---|---|
| **Typography** | Primary: Manrope (weights: 400, 500, 600, 700) |
| **Font Sizes** | H1: 48px, H2: 32px, Body Large: 18px, Body Medium: 16px, Body Small: 12px |
| **Line Heights** | Headlines: 1.4, Body: 1.4-1.5 |
| **Letter Spacing** | Headlines: -4%, Body: -2% |
| **Colors - Primary** | Primary/50: #81FFEC, Primary/100: #3AFFE2, Primary/600: #007263 |
| **Colors - Neutral** | Black: #121212, White: #FDFDFD |
| **Colors - Greyscale** | 0: #F8FAFB, 100: #DFE1E7, 200: #C1C7D0, 300: #A4ACB9, 500: #666D80 |
| **Gradients** | Linear Primary: 180deg, #1AFFD5 → #0D9488 |
| **Gradients - Text** | Linear: 179deg, #FFFFFF 50% → #41A492 100% |
| **Border Radius** | Cards: 12px, Buttons: 12px, Pills: 999px |
| **Spacing** | 8px base grid system, Sections: 100px vertical padding |
| **Container** | Max-width: 1344px (48px horizontal padding) |
| **Effects** | Backdrop blur: 44px, Glow shadows, Inset shadows |

### Section-Specific Specifications

#### 1. Hero Section (Node: 1-32)

**Layout Structure**
- Container: Full viewport height, centered alignment
- Vertical padding: 100px top, content centered
- Background: #121212 with radial gradient glow effects
- Grid layout: Single column, centered content

**Navbar Component**
- Position: Fixed top with glass morphism background
- Container: Glass card with 38.55px border radius
- Background: Linear gradient (180deg, rgba(29,29,29,1) → rgba(131,131,131,1))
- Backdrop filter: blur(60px)
- Border: 1.2px solid rgba(255,255,255,0) with gradient
- Height: 81px
- Spacing: 24px gap between menu items, 12px padding

**Menu Items Structure**
- Font: Manrope 18px weight 500
- Line height: 1.366em
- Color: #FFFFFF
- Active state: Background rgba(255,255,255,0.05)
- Dropdown indicator: CaretDown SVG icon

**Badge Component**
- Background: Glass effect rgba(255,255,255,0.2)
- Border: 1.2px rgba(255,255,255,0.16)
- Border radius: 120.47px
- Padding: 10px 16px 10px 10px
- Box shadow: Multiple layers with backdrop blur(30px)

**Avatar Group**
- Layout: Overlapping circles with -13.25px gap
- Size: 28.91px × 28.91px per avatar
- Border: 1px solid #FFFFFF
- Background: #9A9A9A placeholder

**Heading Typography**
- Font: Manrope 64px weight 700
- Line height: 1.2em
- Letter spacing: -1%
- Gradient: Linear 180deg, #FFFFFF → #2D7A7A
- Max width: 879.42px
- Alignment: Center

**Subtitle Typography**
- Font: Manrope 20px weight 400
- Line height: 1.366em
- Color: #F6F6F6 (Cod Gray/50)
- Max width: 730.04px
- Alignment: Center

**CTA Button**
- Background: Linear gradient 180deg, #1AFFD5 → #0D9488
- Padding: 14.456px 28.912px
- Border radius: 28.912px
- Font: Manrope 18px weight 700
- Box shadow: Inset shadows for depth
- Height: 57.82px

**Trusted By Section**
- Position: Below hero content
- Font: Manrope 24px weight 500
- Color: #FFFFFF
- Alignment: Center
- Logo container: Horizontal flex with 12px gap

**Background Effects**
- SVG vector shapes with gradient strokes
- Radial gradient glow: Center positioned, rgba(255,255,255,1) → transparent
- Light effects: Positioned absolute with blur filters
- Grid pattern overlay with opacity

#### 2. Why Choose Us Section (Node: 1-1440)

**Section Layout**
- Padding: 100px 48px
- Background: #121212
- Container: Centered, max-width 1344px
- Gap: 48px between header and content

**Section Header**
- Title font: Manrope 48px weight 600
- Title gradient: Linear 179deg, #FFFFFF 50% → #41A492 100%
- Subtitle font: Manrope 20px weight 400
- Subtitle color: #F6F6F6
- Letter spacing: Title -4%, Body -2%
- Line height: 1.4em
- Alignment: Center
- Gap: 16px between title and subtitle

**Feature Cards Grid**
- Layout: 2 columns
- Gap: 32px between cards
- Card min-height: Variable based on content

**Feature Card Specifications**
- Background: Radial gradient (circle at 19% 202%, #FFF6E8 31% → transparent)
- Additional background: Glass effect with opacity
- Border: 3px gradient (top 3px, sides 1px, bottom 1px)
- Border gradient: Linear 180deg, #6FFFE5 → rgba(111,255,229,0.24)
- Border radius: 24px
- Padding: 24px 0px
- Box shadow: Inset 0px 0px 55.5px rgba(185,246,230,0.15)
- Backdrop filter: blur(44px)

**Card Content Structure**
- Header section: Horizontal flex, gap 16px, padding 0px 24px
- Icon size: 48px × 48px
- Icon background: Linear gradient with #1A4C48
- Title font: Manrope 32px weight 600
- Title gradient: Linear 179deg, #FFFFFF 50% → #41A492 100%
- Description font: Manrope 18px weight 400
- Description color: #DFE1E7 (Greyscale/100)
- Gap between title and description: 8px

**Learn More Button**
- Layout: Horizontal flex, centered
- Gap: 6px
- Padding: 12px 0px
- Font: Manrope 18px weight 700
- Color: #FFFFFF
- Icon: arrow-right-line SVG 24px × 24px
- Border radius: 100px

**Card Visual Elements**
- Portfolio Insights: Circular button grid with glassmorphism
- Event Forecast: Calendar table UI with data rows
- Market Headlines: News feed with image thumbnails
- Trend Analysis Tools: Chart visualization elements

**Button Group Layout** (for Portfolio card)
- Arrangement: 3 rows of buttons
- Button background: #000000
- Button border: Linear gradient #6FFFE5
- Button padding: 9.31px 18.63px 9.31px 13.97px
- Button border radius: 1397.07px
- Button gap: 9.31px
- Font: Manrope 20.96px weight 600
- Text color: #D6D7D6
- SVG icons: 23.28px × 23.28px

#### 3. Trade Sections (Node: 1-399)

**Section Layout**
- Padding: 100px 48px
- Background: #121212
- Gradient overlay: Radial pattern with opacity

**Section Header**
- Title: Manrope 48px weight 600
- Title gradient: Linear 179deg, #FFFFFF 50% → #1AFFD5 100%
- Subtitle: Manrope 20px weight 400
- Letter spacing: Title -4%, Body -4%
- Max width: Title 720px, Subtitle 786px
- Alignment: Center

**Market Cards Grid**
- Layout: 3 columns
- Gap: 24px between rows and columns
- Card width: 432px fixed

**Market Card Design**
- Background: Radial gradient (circle at -9% 213%)
- Secondary background: #011D1C
- Border: 1px gradient Linear 180deg, transparent → #6FFFE5
- Border radius: 12px
- Padding: 24px
- Backdrop filter: blur(44px)
- Gap: 24px between internal sections

**Flag Component**
- Layout: Overlapping circles with -8px gap
- Size: 32px × 32px per flag
- Border radius: Full circle
- Visual: Country flag SVG graphics

**Pair Information**
- Pair name: Manrope 32px weight 600
- Pair color: #FFFFFF
- Full name: Manrope 18px weight 400
- Full name color: #F6F6F6
- Alignment: Left
- Gap: 12px between pair and full name

**Price Data Layout**
- Layout: Horizontal flex
- Gap: 40px
- Alignment: Center

**Price Block**
- Label: Manrope 16px weight 400
- Label color: #FFFFFF
- Value: Manrope 16px weight 600
- Value color: #F8FAFB (Greyscale/0)
- Letter spacing: -4% for value
- Gap: 7px between label and value
- Alignment: Center

**Change Indicator**
- Positive color: #3AFFE2 (Primary/100)
- Negative color: #FF1D48 (Alert/Error/100)
- Font: Manrope 16px weight 600

**Graph Visualization**
- Position: Absolute positioned in card
- Size: 31.15px × 115.72px
- Background: Union shape with gradient
- Ellipse: #D9D9D9 base
- Line stroke: #000000 3px
- Rectangle indicator: Width 1.31px, height varies
- Border: rgba(255,255,255,0.15) 0.5px
- Border radius: 4999.79px

**Decorative Dots**
- Size: 2.76px × 2.76px
- Color: #C5B9F6
- Filter: blur(10px)
- Position: Scattered pattern in background
- Purpose: Visual interest and depth

#### 4. Plans Section (Node: 1-1926)

**Section Layout**
- Container padding: 100px 48px
- Background: Layered gradients
- Primary gradient: rgba(18,18,18,0.2) + Linear (180deg)
- Secondary gradient: #121212 23% → #002726 45% → #00382F 68% → #0B2721 99%

**Section Header**
- Title: Manrope 48px weight 600
- Title gradient: Linear 179deg, #FFFFFF 50% → #41A492 100%
- Description: Manrope 20px weight 400
- Description color: #F6F6F6
- Letter spacing: -4% / -2%
- Line height: 1.4em
- Gap: 16px

**Pricing Grid**
- Layout: 3 columns horizontal flex
- Gap: 24px between cards
- Justify content: Center
- Alignment: Center stretch

**Pricing Card Container**
- Outer padding: 8px 4px 4px
- Gap: 12px
- Border radius: 12px

**Card Borders and Backgrounds**
- Basic/Premium: Radial gradient (circle at 45% -49%)
- Basic border: Radial gradient with rgba(26,255,213,1) center
- Featured (ECN Pro): Different radial gradient focus
- Featured background: Brighter gradient intensity
- Stroke weight: 1px

**Popular Badge** (Featured card only)
- Position: Top center
- Layout: Horizontal flex
- Gap: 4px
- Background: None
- Icon: fire-fill SVG 20px × 20px
- Text: Manrope 16px weight 600
- Text color: #121212 (Neutral/Black)
- Alignment: Center

**Card Inner Container**
- Background: #000000
- Padding: 24px 12px 12px (Basic/Premium)
- Padding: Content varies for Featured
- Gap: 32px between sections
- Width: 396px fixed
- Box shadow: Inset 0px 0px 55.5px rgba(197,185,246,0.15)

**Account Icon**
- Size: 48px × 48px
- Background: Layered gradients + #1A4C48
- Border radius: Varies by account type

**Account Name and Description**
- Name: Manrope 32px weight 600
- Name gradient: Linear 179deg, #FFFFFF 50% → #41A492 100%
- Additional color: #FDFDFD
- Description: Manrope 16px weight 400
- Description color: Basic #D1D1D1, Premium #F6F6F6
- Letter spacing: -4% / -2%
- Gap: 8px

**Features List Layout**
- Gap: varies by row
- Each row: Horizontal flex space-between

**Feature Row Design**
- Label font: Manrope 16px weight 400
- Label color: #A4ACB9 (Greyscale/300)
- Value font: Manrope 16px weight 500
- Value color: #FDFDFD (Neutral/White)
- Separator: 1px height, #313131 color
- Letter spacing: -2%

**CTA Button**
- Basic/Premium: Background #C0FFF4 (Linear/50)
- Featured: Gradient Linear 180deg, #1AFFD5 → #0D9488
- Padding: 12px 16px
- Border radius: 12px
- Font: Manrope 18px weight 700
- Text color: Basic/Premium #007263, Featured #FFFFFF
- Box shadow: Inset multi-layer
- Width: Fill container

#### 5. Blog CTA Section (Node: 1-1886)

**Section Layout**
- Padding: 50px 48px
- Background: #121212
- Container: Full width

**Content Card**
- Background: Radial gradient (circle at 50% 138%)
- Gradient colors: #FFF6E8 → #1AFFD5 20% → transparent 54%
- Border: 1px Linear gradient 180deg, #39E8C8 → #000000
- Border radius: 12px
- Height: 364px fixed
- Display: Relative positioning

**Heading Container**
- Position: Absolute (40px, 85px)
- Size: 685px × 195px
- Layout: Column flex
- Gap: 32px
- Justify: Center

**Heading Text**
- Font: Manrope 48px weight 600
- Gradient: Linear 179deg, #FFFFFF 50% → #41A492 100%
- Letter spacing: -4%
- Line height: 1.4em
- Max width: 768px
- Alignment: Left

**CTA Button**
- Background: Linear gradient 180deg, #1AFFD5 → #0D9488
- Padding: 8px 16px
- Border radius: 100px
- Width: 162px fixed
- Gap: 6px
- Font: Manrope 18px weight 700
- Color: #FFFFFF
- Box shadow: Inset multi-layer for depth

**Visual Chart Graphic**
- Position: Absolute (759px, 0px)
- Size: 585px × 572px
- SVG chart lines with gradient strokes
- Glow card element: 271px × 156.45px
- Card background: Multiple gradients including radial #00FFF6 glow
- Card border: 4.79px with gradient + glow shadow
- Card border radius: Based on Figma specifications
- Inner content: Icon with gradient background

#### 6. Footer + CTA Section (Node: 1-2162)

**Banner Section**
- Padding: 40px horizontal
- Gap: 48px vertical

**Banner Heading**
- Font: Manrope 48px weight 600
- Gradient: Linear 179deg, #FFFFFF 50% → #41A492 100%
- Letter spacing: -4%
- Line height: 1.4em
- Alignment: Center

**CTA Interactive Visual**
- Container: 1440px × 628px (negative margin -62px top)
- Background: Complex gradient Linear 178deg
- Background layers: #121212 50% → #002726 72% → #00382F 90% → rgba(0,68,55,0.42)

**Network Diagram Lines**
- SVG paths with gradient strokes
- Gradient: Linear 90deg, #1AFFD5 → rgba(26,255,213,0.2) → transparent
- Stroke weight: 1px
- Positions: Calculated from Figma coordinates

**Icon Nodes**
- Size: 62px × 62px
- Background: #094641
- Border: 1px Linear gradient (270deg)
- Border gradient: rgba(26,255,213,0.5) → rgba(26,255,213,0.1) → rgba(26,255,213,0.5)
- Icons: SVG 32px × 32px centered
- Icon color: #FFFFFF

**Central Network Node**
- Container: 282px × 276px
- Glow effect: #3C9CAD blur(154px)
- Card: 264px × 264px
- Card background: #0B3130
- Card shadow: Multiple inset shadows + glow
- Card border radius: 20px

**Inner Icon Container**
- Background: rgba(0,40,47,0.1)
- Border: 1px rgba(255,255,255,0.2)
- Backdrop filter: blur(14px)
- Inset shadows for glassmorphism
- Icon: Multi-layered SVG with #51EEDC color

**Connection Dots**
- Size: 15px × 16px (varies)
- Background: #FFFFFF
- Filter: blur(10px)
- Purpose: Visual connection points

**Gradient Lines** (decorative)
- Background: Linear 180deg, #FFFFFF → transparent
- Filter: blur(4px)
- Size: 106px × 6px
- Positions: Calculated spacing

**Footer Container**
- Padding: 40px 40px 24px
- Gap: 48px vertical
- Background: Gradient overlay #000000

**Gradient Background Overlay**
- Position: Absolute (0, 0)
- Size: 1440px × 526px
- Gradient: Linear 180deg
- Colors: rgba(0,68,55,0.42) → #00382F → #002726 49% → #121212
- Additional: #000000 base

**Top Footer Section**
- Container: Glass card effect
- Background: rgba(26,255,213,0.09)
- Border: 1px rgba(26,255,213,0.15)
- Border radius: 12px
- Padding: 48px 40px
- Gap: 96px
- Box shadow: Inset 0px 0px 20px rgba(26,255,213,0.25)

**Company Info Column**
- Width: 503px
- Gap: 24px vertical
- Logo: 186px × 41px SVG

**Address Blocks**
- Label: Manrope 16px weight 500
- Label color: #C1C7D0 (Greyscale/200)
- Address: Manrope 16px weight 500
- Address color: #FDFDFD (Neutral/White)
- Gap: 12px between label and address

**Social Media Icons**
- Container: Horizontal flex, gap 12px
- Icon size: 32px × 32px
- Border radius: Circular
- Background: Various platform colors

**Menu Columns**
- Layout: Horizontal flex row
- Gap: 48px between logo section and menu
- Gap: 32px between menu columns

**Menu Column Structure**
- Width: Varies (126px, 162px, 170px)
- Gap: 16px between heading and list
- Gap: 24px between columns

**Menu Headings**
- Font: Manrope 16px weight 400
- Color: #D1D1D1 (Cod Gray/200)
- Letter spacing: -2%
- Line height: 1.4em

**Menu Links**
- Font: Manrope 16px weight 500
- Color: #FFFFFF
- Letter spacing: -2%
- Line height: 1.4em
- Gap: 8px between links
- Hover: Color change to primary

**Vertical Separator**
- Width: 1px
- Color: #717171
- Full height of menu section

**Bottom Footer**
- Layout: Horizontal flex space-between
- Width: 1360px
- Copyright: Manrope 12px weight 400
- Copyright color: #FFFFFF

## Technical Implementation Requirements

### File Structure
All modifications should be made to existing theme files:
- Views: `main/resources/views/frontend/trading-v1/`
- Widgets: `main/resources/views/frontend/trading-v1/widgets/`
- CSS: `main/public/asset/frontend/trading-v1/css/main.css`
- JavaScript: `main/public/asset/frontend/trading-v1/js/main.js`

### CSS Token System Implementation

CSS custom properties must be defined based on Figma tokens:

| Token Name | CSS Variable | Value |
|---|---|---|
| Font Family Heading | --tv-font-heading | 'Manrope', sans-serif |
| Font Family Body | --tv-font-body | 'Inter', sans-serif |
| Font Size H1 | --tv-text-6xl | 3rem (48px) |
| Font Size H2 | --tv-text-4xl | 2rem (32px) |
| Font Size Body Large | --tv-text-lg | 1.125rem (18px) |
| Font Size Body | --tv-text-base | 1rem (16px) |
| Line Height Heading | --tv-lh-heading | 1.4 |
| Letter Spacing Tight | --tv-ls-tight | -0.04em |
| Letter Spacing Base | --tv-ls-base | -0.02em |
| Primary Color | --tv-primary | #1AFFD5 |
| Primary Dark | --tv-primary-dark | #0D9488 |
| Background | --tv-bg | #121212 |
| Text Primary | --tv-text | #FFFFFF |
| Greyscale 100 | --tv-grey-100 | #DFE1E7 |
| Border Radius Card | --tv-radius-card | 12px |
| Border Radius Button | --tv-radius-button | 12px |
| Border Radius Pill | --tv-radius-pill | 999px |
| Spacing Unit | --tv-space | 8px |
| Section Padding | --tv-section-padding | 100px |
| Container Max Width | --tv-container | 1344px |
| Container Padding | --tv-container-padding | 48px |
| Backdrop Blur | --tv-blur | 44px |

### Layout Implementation

**Container Structure**
- Use CSS Grid for section layouts
- Flexbox for component-level layouts
- Max-width constraint with horizontal padding
- Responsive breakpoints using media queries

**Spacing System**
- Base 8px grid for all spacing
- Consistent gap values using calc() with base unit
- Section padding of 100px vertical
- Component internal padding as specified per component

### Component Specifications

#### Navbar Component
- Fixed positioning with z-index layering
- Glass morphism background using backdrop-filter
- Smooth scroll behavior and sticky navigation
- Active state management for menu items
- Dropdown functionality for Markets and Trading Tools
- Mobile hamburger menu with slide-in panel

#### Hero Section
- Full viewport height with flexbox centering
- Gradient text using background-clip technique
- Animated background glow using CSS animations
- Badge component with overlapping avatars
- CTA button with gradient and hover states
- Partner logo section with grayscale filter effects

#### Why Choose Us Section
- CSS Grid 2-column layout
- Card hover effects with transform and shadow
- Glassmorphism card backgrounds
- Gradient borders using pseudo-elements
- Icon containers with gradient backgrounds
- Dynamic content loading from database

#### Market Trends Section
- 3-column responsive grid
- Flag icon positioning with overlapping
- Positive/negative color states
- Mini chart visualizations using SVG
- Real-time data binding structure
- Hover interactions for additional data

#### Pricing Cards
- 3-column grid with equal heights
- Featured card highlighting with different gradient
- Popular badge positioning
- Feature list with separator lines
- CTA buttons with different states
- Responsive collapse to single column on mobile

#### CTA Section
- Full-width background gradient
- Absolute positioned chart graphic
- Glassmorphism card with complex gradients
- Responsive text sizing
- Button hover effects

#### Footer
- Network diagram using SVG positioning
- Interactive node elements
- Multi-column menu layout
- Social media icon styling
- Copyright and legal links
- Responsive collapse on mobile

### Responsive Breakpoints

| Breakpoint | Width | Layout Changes |
|---|---|---|
| Desktop Large | 1440px+ | Full design, 3-column grids |
| Desktop | 1024px-1439px | Container max-width 1200px, maintain 3 columns |
| Tablet | 768px-1023px | 2-column grids, reduced spacing |
| Mobile Large | 640px-767px | Single column, stacked layout |
| Mobile | <640px | Single column, compressed spacing, mobile menu |

### Animation and Interactions

**Required Animations**
- Hero background glow pulse (8s infinite)
- Button hover lift effect (transform translateY -2px)
- Card hover with shadow expansion
- Gradient border fade-in on hover
- Menu slide transitions
- Smooth scroll behavior

**Interaction States**
- Hover: Transform, color, shadow changes
- Active: Border highlight, background change
- Focus: Outline for accessibility
- Loading: Skeleton screens for dynamic content

### Accessibility Requirements

- Semantic HTML5 elements throughout
- ARIA labels for interactive elements
- Keyboard navigation support
- Focus visible styles
- Color contrast ratios meet WCAG AA standards
- Alt text for all images and icons
- Screen reader friendly structure

### Browser Compatibility

- Modern browsers: Chrome, Firefox, Safari, Edge (last 2 versions)
- Graceful degradation for older browsers
- CSS feature detection using @supports
- Polyfills for backdrop-filter if needed
- Fallback gradients for older browsers

## Implementation Phases

### Phase 1: Design Tokens and Base Styles
- Update CSS custom properties with exact Figma values
- Implement typography system
- Set up color palette variables
- Configure spacing and layout tokens

### Phase 2: Navbar and Hero Section
- Fix navbar glass morphism effect
- Correct hero typography and spacing
- Implement badge component with avatars
- Add background gradient animations
- Update CTA button styling

### Phase 3: Why Choose Us Section
- Rebuild card grid layout
- Implement glassmorphism cards with correct gradients
- Add gradient borders using pseudo-elements
- Create card visual elements (buttons, tables, charts)
- Fix spacing and typography

### Phase 4: Market Trends Section
- Correct grid layout to 3 columns
- Implement flag icon overlapping
- Add SVG chart visualizations
- Style positive/negative indicators
- Fix card hover states

### Phase 5: Pricing Section
- Rebuild 3-column pricing grid
- Add popular badge to featured card
- Implement different card backgrounds
- Create feature list with separators
- Style CTA buttons per card type

### Phase 6: CTA and Footer
- Build CTA section with chart graphic
- Create network diagram SVG layout
- Implement footer multi-column menu
- Add social media icons
- Style copyright section

### Phase 7: Responsive Optimization
- Add media queries for all breakpoints
- Implement mobile menu
- Adjust spacing for tablet/mobile
- Test touch interactions
- Optimize for performance

### Phase 8: Polish and Testing
- Cross-browser testing
- Accessibility audit
- Performance optimization
- Animation smoothness
- Final visual QA against Figma

## Quality Assurance Checklist

### Visual Accuracy
- [ ] Typography matches Figma specifications (font, size, weight, line-height, letter-spacing)
- [ ] Colors match exact hex values from design system
- [ ] Spacing follows 8px grid system consistently
- [ ] Border radius values match specifications
- [ ] Gradients render correctly with proper color stops and angles
- [ ] Shadows and glows match Figma layer effects
- [ ] Glassmorphism effects achieve proper blur and transparency
- [ ] Icons are correctly sized and positioned

### Layout Precision
- [ ] Container max-width and padding match specifications
- [ ] Grid layouts use correct column counts and gaps
- [ ] Flexbox alignments match design intent
- [ ] Absolute positioning matches Figma coordinates where used
- [ ] Overlapping elements (avatars, flags) have correct negative margins
- [ ] Section padding is consistent at 100px vertical
- [ ] Component internal spacing matches specifications

### Interactive Elements
- [ ] Buttons have correct hover states and transitions
- [ ] Cards lift on hover with proper transform and shadow
- [ ] Navbar remains fixed with proper scroll behavior
- [ ] Dropdown menus function smoothly
- [ ] Links have appropriate hover effects
- [ ] Form inputs match design specifications
- [ ] Active states are visually distinct

### Responsive Behavior
- [ ] Layout adapts properly at all breakpoints
- [ ] Mobile menu functions correctly
- [ ] Text remains readable at all sizes
- [ ] Images scale appropriately
- [ ] Touch targets are minimum 44px × 44px on mobile
- [ ] Horizontal scroll is prevented
- [ ] Spacing reduces proportionally on smaller screens

### Performance
- [ ] CSS file size is optimized
- [ ] Images are compressed and properly sized
- [ ] Fonts are loaded efficiently
- [ ] Animations are GPU-accelerated
- [ ] No layout shifts during page load
- [ ] Lighthouse performance score > 90
- [ ] First Contentful Paint < 1.5s

### Accessibility
- [ ] Semantic HTML structure is used
- [ ] ARIA labels are present where needed
- [ ] Keyboard navigation works throughout
- [ ] Focus indicators are visible
- [ ] Color contrast meets WCAG AA (4.5:1 for text)
- [ ] Alt text provided for images
- [ ] Form labels are properly associated

### Cross-Browser Compatibility
- [ ] Chrome: All features work correctly
- [ ] Firefox: All features work correctly
- [ ] Safari: All features work correctly
- [ ] Edge: All features work correctly
- [ ] Fallbacks work in older browsers
- [ ] CSS features degrade gracefully

## Success Criteria

The implementation will be considered successful when:

1. **Visual Fidelity**: Landing page matches Figma design with 95%+ accuracy
2. **Responsive**: All breakpoints render correctly without horizontal scroll
3. **Performance**: Lighthouse score above 90 for performance, accessibility, best practices
4. **Cross-Browser**: Works in all modern browsers without visual bugs
5. **Accessibility**: WCAG AA compliant for color contrast and keyboard navigation
6. **Maintainability**: Code is well-organized with clear comments and reusable components
7. **User Approval**: Stakeholder approval of final visual presentation

## Risk Mitigation

### Potential Risks

| Risk | Impact | Mitigation Strategy |
|---|---|---|
| Browser compatibility issues with backdrop-filter | Medium | Implement fallback backgrounds, test early |
| Complex SVG charts performance | Low | Optimize SVG code, use CSS animations sparingly |
| Responsive layout breaking at edge cases | Medium | Comprehensive testing at all breakpoints |
| Typography rendering differences across browsers | Low | Use web-safe fallback fonts, test rendering |
| Gradient color banding | Low | Use high-quality gradients, test on different screens |
| Dynamic content breaking layout | Medium | Use flexible layouts, test with various content lengths |

### Mitigation Actions
- Early browser testing throughout development
- Performance monitoring with Lighthouse
- Visual regression testing tools
- Code reviews for best practices
- Accessibility audits during development

## Constraints

- Must maintain existing Laravel Blade template structure
- Cannot modify core framework files
- Must support existing database schema for dynamic content
- Must preserve existing routing and functionality
- Should reuse existing helper functions and utilities
- Must be compatible with current build process

## Dependencies

- Existing Laravel application structure
- Current theme system and Helper::theme() function
- Database configuration content system
- Asset compilation pipeline (Laravel Mix or Vite)
- Font Awesome icons library
- Google Fonts API

## Notes

- All measurements converted from Figma's px to rem where appropriate
- Gradient angles in Figma may need conversion to CSS syntax
- SVG assets should be optimized before implementation
- Consider lazy loading for below-fold content
- Implement critical CSS for above-the-fold content
- Use CSS Grid for major layouts, Flexbox for components
- Maintain existing translation system for multilingual support
- Gap: 16px between heading and list
- Gap: 24px between columns

**Menu Headings**
- Font: Manrope 16px weight 400
- Color: #D1D1D1 (Cod Gray/200)
- Letter spacing: -2%
- Line height: 1.4em

**Menu Links**
- Font: Manrope 16px weight 500
- Color: #FFFFFF
- Letter spacing: -2%
- Line height: 1.4em
- Gap: 8px between links
- Hover: Color change to primary

**Vertical Separator**
- Width: 1px
- Color: #717171
- Full height of menu section

**Bottom Footer**
- Layout: Horizontal flex space-between
- Width: 1360px
- Copyright: Manrope 12px weight 400
- Copyright color: #FFFFFF

## Technical Implementation Requirements

### File Structure
All modifications should be made to existing theme files:
- Views: `main/resources/views/frontend/trading-v1/`
- Widgets: `main/resources/views/frontend/trading-v1/widgets/`
- CSS: `main/public/asset/frontend/trading-v1/css/main.css`
- JavaScript: `main/public/asset/frontend/trading-v1/js/main.js`

### CSS Token System Implementation

CSS custom properties must be defined based on Figma tokens:

| Token Name | CSS Variable | Value |
|---|---|---|
| Font Family Heading | --tv-font-heading | 'Manrope', sans-serif |
| Font Family Body | --tv-font-body | 'Inter', sans-serif |
| Font Size H1 | --tv-text-6xl | 3rem (48px) |
| Font Size H2 | --tv-text-4xl | 2rem (32px) |
| Font Size Body Large | --tv-text-lg | 1.125rem (18px) |
| Font Size Body | --tv-text-base | 1rem (16px) |
| Line Height Heading | --tv-lh-heading | 1.4 |
| Letter Spacing Tight | --tv-ls-tight | -0.04em |
| Letter Spacing Base | --tv-ls-base | -0.02em |
| Primary Color | --tv-primary | #1AFFD5 |
| Primary Dark | --tv-primary-dark | #0D9488 |
| Background | --tv-bg | #121212 |
| Text Primary | --tv-text | #FFFFFF |
| Greyscale 100 | --tv-grey-100 | #DFE1E7 |
| Border Radius Card | --tv-radius-card | 12px |
| Border Radius Button | --tv-radius-button | 12px |
| Border Radius Pill | --tv-radius-pill | 999px |
| Spacing Unit | --tv-space | 8px |
| Section Padding | --tv-section-padding | 100px |
| Container Max Width | --tv-container | 1344px |
| Container Padding | --tv-container-padding | 48px |
| Backdrop Blur | --tv-blur | 44px |

### Layout Implementation

**Container Structure**
- Use CSS Grid for section layouts
- Flexbox for component-level layouts
- Max-width constraint with horizontal padding
- Responsive breakpoints using media queries

**Spacing System**
- Base 8px grid for all spacing
- Consistent gap values using calc() with base unit
- Section padding of 100px vertical
- Component internal padding as specified per component

### Component Specifications

#### Navbar Component
- Fixed positioning with z-index layering
- Glass morphism background using backdrop-filter
- Smooth scroll behavior and sticky navigation
- Active state management for menu items
- Dropdown functionality for Markets and Trading Tools
- Mobile hamburger menu with slide-in panel

#### Hero Section
- Full viewport height with flexbox centering
- Gradient text using background-clip technique
- Animated background glow using CSS animations
- Badge component with overlapping avatars
- CTA button with gradient and hover states
- Partner logo section with grayscale filter effects

#### Why Choose Us Section
- CSS Grid 2-column layout
- Card hover effects with transform and shadow
- Glassmorphism card backgrounds
- Gradient borders using pseudo-elements
- Icon containers with gradient backgrounds
- Dynamic content loading from database

#### Market Trends Section
- 3-column responsive grid
- Flag icon positioning with overlapping
- Positive/negative color states
- Mini chart visualizations using SVG
- Real-time data binding structure
- Hover interactions for additional data

#### Pricing Cards
- 3-column grid with equal heights
- Featured card highlighting with different gradient
- Popular badge positioning
- Feature list with separator lines
- CTA buttons with different states
- Responsive collapse to single column on mobile

#### CTA Section
- Full-width background gradient
- Absolute positioned chart graphic
- Glassmorphism card with complex gradients
- Responsive text sizing
- Button hover effects

#### Footer
- Network diagram using SVG positioning
- Interactive node elements
- Multi-column menu layout
- Social media icon styling
- Copyright and legal links
- Responsive collapse on mobile

### Responsive Breakpoints

| Breakpoint | Width | Layout Changes |
|---|---|---|
| Desktop Large | 1440px+ | Full design, 3-column grids |
| Desktop | 1024px-1439px | Container max-width 1200px, maintain 3 columns |
| Tablet | 768px-1023px | 2-column grids, reduced spacing |
| Mobile Large | 640px-767px | Single column, stacked layout |
| Mobile | <640px | Single column, compressed spacing, mobile menu |

### Animation and Interactions

**Required Animations**
- Hero background glow pulse (8s infinite)
- Button hover lift effect (transform translateY -2px)
- Card hover with shadow expansion
- Gradient border fade-in on hover
- Menu slide transitions
- Smooth scroll behavior

**Interaction States**
- Hover: Transform, color, shadow changes
- Active: Border highlight, background change
- Focus: Outline for accessibility
- Loading: Skeleton screens for dynamic content

### Accessibility Requirements

- Semantic HTML5 elements throughout
- ARIA labels for interactive elements
- Keyboard navigation support
- Focus visible styles
- Color contrast ratios meet WCAG AA standards
- Alt text for all images and icons
- Screen reader friendly structure

### Browser Compatibility

- Modern browsers: Chrome, Firefox, Safari, Edge (last 2 versions)
- Graceful degradation for older browsers
- CSS feature detection using @supports
- Polyfills for backdrop-filter if needed
- Fallback gradients for older browsers

## Implementation Phases

### Phase 1: Design Tokens and Base Styles
- Update CSS custom properties with exact Figma values
- Implement typography system
- Set up color palette variables
- Configure spacing and layout tokens

### Phase 2: Navbar and Hero Section
- Fix navbar glass morphism effect
- Correct hero typography and spacing
- Implement badge component with avatars
- Add background gradient animations
- Update CTA button styling

### Phase 3: Why Choose Us Section
- Rebuild card grid layout
- Implement glassmorphism cards with correct gradients
- Add gradient borders using pseudo-elements
- Create card visual elements (buttons, tables, charts)
- Fix spacing and typography

### Phase 4: Market Trends Section
- Correct grid layout to 3 columns
- Implement flag icon overlapping
- Add SVG chart visualizations
- Style positive/negative indicators
- Fix card hover states

### Phase 5: Pricing Section
- Rebuild 3-column pricing grid
- Add popular badge to featured card
- Implement different card backgrounds
- Create feature list with separators
- Style CTA buttons per card type

### Phase 6: CTA and Footer
- Build CTA section with chart graphic
- Create network diagram SVG layout
- Implement footer multi-column menu
- Add social media icons
- Style copyright section

### Phase 7: Responsive Optimization
- Add media queries for all breakpoints
- Implement mobile menu
- Adjust spacing for tablet/mobile
- Test touch interactions
- Optimize for performance

### Phase 8: Polish and Testing
- Cross-browser testing
- Accessibility audit
- Performance optimization
- Animation smoothness
- Final visual QA against Figma

## Quality Assurance Checklist

### Visual Accuracy
- [ ] Typography matches Figma specifications (font, size, weight, line-height, letter-spacing)
- [ ] Colors match exact hex values from design system
- [ ] Spacing follows 8px grid system consistently
- [ ] Border radius values match specifications
- [ ] Gradients render correctly with proper color stops and angles
- [ ] Shadows and glows match Figma layer effects
- [ ] Glassmorphism effects achieve proper blur and transparency
- [ ] Icons are correctly sized and positioned

### Layout Precision
- [ ] Container max-width and padding match specifications
- [ ] Grid layouts use correct column counts and gaps
- [ ] Flexbox alignments match design intent
- [ ] Absolute positioning matches Figma coordinates where used
- [ ] Overlapping elements (avatars, flags) have correct negative margins
- [ ] Section padding is consistent at 100px vertical
- [ ] Component internal spacing matches specifications

### Interactive Elements
- [ ] Buttons have correct hover states and transitions
- [ ] Cards lift on hover with proper transform and shadow
- [ ] Navbar remains fixed with proper scroll behavior
- [ ] Dropdown menus function smoothly
- [ ] Links have appropriate hover effects
- [ ] Form inputs match design specifications
- [ ] Active states are visually distinct

### Responsive Behavior
- [ ] Layout adapts properly at all breakpoints
- [ ] Mobile menu functions correctly
- [ ] Text remains readable at all sizes
- [ ] Images scale appropriately
- [ ] Touch targets are minimum 44px × 44px on mobile
- [ ] Horizontal scroll is prevented
- [ ] Spacing reduces proportionally on smaller screens

### Performance
- [ ] CSS file size is optimized
- [ ] Images are compressed and properly sized
- [ ] Fonts are loaded efficiently
- [ ] Animations are GPU-accelerated
- [ ] No layout shifts during page load
- [ ] Lighthouse performance score > 90
- [ ] First Contentful Paint < 1.5s

### Accessibility
- [ ] Semantic HTML structure is used
- [ ] ARIA labels are present where needed
- [ ] Keyboard navigation works throughout
- [ ] Focus indicators are visible
- [ ] Color contrast meets WCAG AA (4.5:1 for text)
- [ ] Alt text provided for images
- [ ] Form labels are properly associated

### Cross-Browser Compatibility
- [ ] Chrome: All features work correctly
- [ ] Firefox: All features work correctly
- [ ] Safari: All features work correctly
- [ ] Edge: All features work correctly
- [ ] Fallbacks work in older browsers
- [ ] CSS features degrade gracefully

## Success Criteria

The implementation will be considered successful when:

1. **Visual Fidelity**: Landing page matches Figma design with 95%+ accuracy
2. **Responsive**: All breakpoints render correctly without horizontal scroll
3. **Performance**: Lighthouse score above 90 for performance, accessibility, best practices
4. **Cross-Browser**: Works in all modern browsers without visual bugs
5. **Accessibility**: WCAG AA compliant for color contrast and keyboard navigation
6. **Maintainability**: Code is well-organized with clear comments and reusable components
7. **User Approval**: Stakeholder approval of final visual presentation

## Risk Mitigation

### Potential Risks

| Risk | Impact | Mitigation Strategy |
|---|---|---|
| Browser compatibility issues with backdrop-filter | Medium | Implement fallback backgrounds, test early |
| Complex SVG charts performance | Low | Optimize SVG code, use CSS animations sparingly |
| Responsive layout breaking at edge cases | Medium | Comprehensive testing at all breakpoints |
| Typography rendering differences across browsers | Low | Use web-safe fallback fonts, test rendering |
| Gradient color banding | Low | Use high-quality gradients, test on different screens |
| Dynamic content breaking layout | Medium | Use flexible layouts, test with various content lengths |

### Mitigation Actions
- Early browser testing throughout development
- Performance monitoring with Lighthouse
- Visual regression testing tools
- Code reviews for best practices
- Accessibility audits during development

## Constraints

- Must maintain existing Laravel Blade template structure
- Cannot modify core framework files
- Must support existing database schema for dynamic content
- Must preserve existing routing and functionality
- Should reuse existing helper functions and utilities
- Must be compatible with current build process

## Dependencies

- Existing Laravel application structure
- Current theme system and Helper::theme() function
- Database configuration content system
- Asset compilation pipeline (Laravel Mix or Vite)
- Font Awesome icons library
- Google Fonts API

## Notes

- All measurements converted from Figma's px to rem where appropriate
- Gradient angles in Figma may need conversion to CSS syntax
- SVG assets should be optimized before implementation
- Consider lazy loading for below-fold content
- Implement critical CSS for above-the-fold content
- Use CSS Grid for major layouts, Flexbox for components
- Maintain existing translation system for multilingual support
