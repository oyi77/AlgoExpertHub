# OpenCode Agents for AI Trading Platform

This directory contains specialized OpenCode agents designed for the AI trading platform development team. The agents are organized into two main divisions: **Engineering Team** and **Trading Division**, with supporting specialists.

## Agent Overview

### Primary Agents (Engineering Team)
These are the main development agents you can switch between using the **Tab** key:

- **`backend-engineer`** - Laravel/PHP backend development, APIs, database design
- **`frontend-engineer`** - Modern web interfaces, React/Vue.js, responsive design
- **`trading-analyst`** - Algorithmic trading, signal processing, risk management
- **`ai-specialist`** - Machine learning, AI models, signal intelligence
- **`devops-engineer`** - Infrastructure, deployment, monitoring, scaling

### Subagents (Specialists)
These agents can be invoked with `@` mentions or automatically by primary agents:

- **`@qa-engineer`** - Testing, quality assurance, test automation
- **`@risk-manager`** - Risk controls, compliance, regulatory requirements
- **`@data-engineer`** - Data pipelines, real-time processing, databases
- **`@security-specialist`** - Security, encryption, vulnerability assessment
- **`@product-manager`** - Requirements, coordination, feature planning
- **`@ui-ux-designer`** - User experience, interface design, usability

## Usage Examples

### Switch Between Primary Agents
```bash
# Use Tab key to cycle through primary agents
# Or mention them directly:
@backend-engineer help me implement the trading API
@frontend-engineer create a real-time dashboard
@trading-analyst optimize the signal processing algorithm
```

### Invoke Specialist Subagents
```bash
@qa-engineer write tests for the new trading feature
@risk-manager review the position sizing logic
@security-specialist audit the authentication system
@product-manager define requirements for copy trading
```

### Team Collaboration Examples
```bash
# Backend + Frontend collaboration
@backend-engineer create the API for signal management
@frontend-engineer build the UI for the signal management API

# Trading + AI collaboration
@trading-analyst design the risk management system
@ai-specialist create ML models for signal scoring

# Full team coordination
@product-manager define the copy trading requirements
@backend-engineer implement the copy trading backend
@frontend-engineer build the copy trading interface
@qa-engineer test the complete copy trading flow
```

## Agent Specializations

### Engineering Division

#### Backend Engineer
- Laravel framework and PHP development
- RESTful API design and implementation
- Database design and optimization
- Authentication and authorization
- Payment gateway integrations
- Real-time communication systems

#### Frontend Engineer
- Modern JavaScript/TypeScript development
- React, Vue.js, Alpine.js frameworks
- Responsive and mobile-first design
- Real-time UI updates and WebSocket integration
- Trading dashboard and chart implementations
- User experience optimization

#### DevOps Engineer
- Cloud infrastructure management
- CI/CD pipeline automation
- Container orchestration (Docker, Kubernetes)
- Monitoring and observability
- Database administration
- Security and compliance

### Trading Division

#### Trading Analyst
- Algorithmic trading strategy development
- Signal processing and filtering systems
- Risk management and position monitoring
- Backtesting and strategy validation
- Copy trading logic implementation
- Performance analytics and optimization

#### AI Specialist
- Machine learning model development
- Natural language processing for signals
- Market prediction and forecasting
- Signal intelligence and scoring
- AI model deployment and monitoring
- Integration with AI providers (OpenAI, etc.)

### Supporting Specialists

#### QA Engineer
- Test automation and framework development
- Financial system testing methodologies
- API testing and validation
- Performance and security testing
- Continuous integration testing

#### Risk Manager
- Financial risk assessment and controls
- Regulatory compliance management
- Position limits and exposure monitoring
- Stress testing and scenario analysis
- Fraud detection and prevention

#### Data Engineer
- Real-time data pipeline development
- Time-series database optimization
- ETL/ELT process implementation
- Data quality and validation
- Analytics infrastructure

## Configuration

The agents are configured in `opencode.json` with:
- **Temperature settings** optimized for each role
- **Tool permissions** appropriate for responsibilities
- **Model selection** (Claude Sonnet for most agents)
- **Bash permissions** tailored to each agent's needs

## Best Practices

1. **Use the right agent for the task** - Each agent is optimized for specific responsibilities
2. **Leverage cross-team collaboration** - Agents are designed to work together
3. **Follow the agent's expertise** - Trust their specialized knowledge and recommendations
4. **Use subagents for specialized tasks** - Invoke specialists when needed
5. **Coordinate complex features** - Use multiple agents for comprehensive implementations

## Project Context

This AI trading platform includes:
- Multi-channel signal processing (Telegram, Discord, webhooks)
- AI-powered signal analysis and filtering
- Copy trading with risk management
- Real-time trade execution
- Subscription and billing system
- Comprehensive analytics and reporting

The agents understand this context and will provide relevant, specialized assistance for building and maintaining this complex financial technology platform.

## Getting Started

1. Start OpenCode in your project directory
2. Use **Tab** to switch between primary agents
3. Use `@agent-name` to invoke specific agents
4. Combine agents for complex tasks requiring multiple specialties

Each agent has detailed expertise and will guide you through their specific domain while collaborating effectively with other team members.