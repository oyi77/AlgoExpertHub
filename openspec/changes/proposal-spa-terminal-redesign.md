# User Panel SPA & Pro Terminal Redesign Proposal

## 1. Executive Summary
**Objective**: Transition the "User Panel" from server-rendered Blade templates to a high-performance **Single Page Application (SPA)** and completely redesign the "Trading Terminal" to match professional industry standards (Binance/TradingView style).

**Approach**:
- **Architecture**: Adopt **Inertia.js + React**. This allows us to keep the existing robust Laravel Controllers and Routes (minimizing backend rewrite) while delivering a seamless SPA experience.
- **Terminal UI**: Replace the static widgets with a **React Grid Layout** system, enabling a customizable, modular workspace.
- **Styling**: Transition from Bootstrap 4 to a "Professional Dark Mode" system using **TailwindCSS** with a specific financial-grade color palette.

## 2. Technical Architecture

### 2.1 SPA Framework: Inertia.js (React)
Why Inertia? It bridges Laravel and React without building a separate API-only backend.
- **Routing**: Use existing `web.php` routes.
- **Controllers**: Return `Inertia::render('Component')` instead of `view('file')`.
- **State**: standard React hooks + Context for global user state.

### 2.2 Terminal Architecture (React + WebSocket)
The new terminal will be a single React page (`/trade/{pair}`) composed of draggable/resizable widgets:
- **Chart Widget**: TradingView Lightweight Charts (Canvas-based, high perf).
- **Order Book**: Virtualized list (React Window) for high-frequency updates.
- **Positions/Orders**: Data grid with sorting/filtering.
- **Trade Form**: Complex form with validation and leverage sliders.

**Data Layer**:
- **Initial Load**: Inertia props.
- **Real-time**: Custom `useWebSocket` hook connecting to the existing Soketi/Pusher setup.

## 3. UI/UX Redesign (Professional Standard)

### 3.1 Layout
- **Dashboard**: Sidebar navigation (collapsible), top bar with global search and wallet summary.
- **Terminal**: 
  - **3-Column Layout** (default): Market List (Left) | Chart + Order Book (Center) | Trade Form (Right).
  - **Modular**: Users can drag/resize panels.

### 3.2 Visual Language
- **Typography**: `Inter` or `Roboto Mono` for numbers.
- **Colors**:
  - Background: `#0b0e11` (Deep Dark)
  - Surface: `#181a20` (Card Background)
  - Buy/Up: `#0ecb81` (Binance Green)
  - Sell/Down: `#f6465d` (Binance Red)
  - Text: `#eaecef` (Primary), `#848e9c` (Secondary)

## 4. Migration Plan

### Phase 1: Infrastructure Setup
- Install Inertia.js server-side and client-side adapters.
- Configure Vite for React HMR.
- Create the root `app.blade.php` for React mounting.
- Set up TailwindCSS with the "Pro" color palette.

### Phase 2: Component Library (Design System)
- Create atomic components: `Button`, `Input`, `Card`, `Modal`, `Table`.
- Create specific financial components: `Ticker`, `OrderBookRow`, `DepthBar`.

### Phase 3: User Panel Migration
- Port "Dashboard" (`user.index`) to React.
- Port "Settings" and "Wallet" pages.
- **Strategy**: One page at a time. Hybrid app is possible (Blade and React co-existing).

### Phase 4: The Pro Terminal
- Build the `TerminalLayout` (Grid system).
- Implement `TradingViewWidget` (Chart).
- Implement `OrderBookWidget` (WebSocket driven).
- Implement `TradeFormWidget` (API integration).

## 5. Next Steps
1. **Approve Proposal**: Confirm this architectural direction.
2. **Setup**: I will begin Phase 1 immediately upon approval.
