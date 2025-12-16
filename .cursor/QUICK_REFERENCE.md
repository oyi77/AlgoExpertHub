# SDD Quick Reference Card

## 🚀 Most Common Workflow

```
/sdd [feature-name] [description]
```
Generates requirements + design + tasks → Asks for verification

```
/build [feature-name]
```
Implements after you say "yes" → Uses bd or Cursor tasks automatically

---

## 📋 All Commands

### Primary
- `/sdd` - Generate complete specs (requirements, design, tasks)
- `/build` - Implement after verification

### Optional (Granular Control)
- `/requirements` - Generate only requirements
- `/design` - Generate only design  
- `/tasks` - Generate only tasks
- `/evolve` - Update specs when requirements change
- `/analyze` - Analyze existing code

### Deprecated
- `/implement` → Use `/build` instead

---

## 📁 Output Location

All specs in `.kiro/specs/[feature-name]/`:
- `requirements.md` - What to build
- `design.md` - How to build it
- `tasks.md` - Steps to build it

---

## 🔧 Task Tracking

**Auto-detected**:
1. Checks for `bd` (beads) - Uses if available ✅
2. Falls back to Cursor tasks if not 🔄

No configuration needed!

---

## ✅ Verification Flow

1. Run `/sdd` → Generates specs
2. AI shows summary and asks: "Ready to build?"
3. You review `.kiro/specs/[feature-name]/`
4. You say "yes" → Run `/build`
5. Implementation begins with task tracking

**NEVER builds without your approval!**

---

## 📖 Integration

Specs automatically reference patterns from `.kiro/steering/`:
- Laravel architecture
- Business domain
- Addon system
- Database models
- Payment gateways
- Queues & jobs

---

## 🔄 When Things Change

```
/evolve [feature-name] [changes]
```

Updates requirements, design, and tasks automatically.
Maintains change history.

---

## 💡 Examples

### Basic Feature
```
/sdd user-profile Add user profile editing with avatar upload
```

### Complex Feature
```
/sdd payment-recurring Implement recurring subscriptions with Stripe webhooks and invoice generation
```

### Update Feature
```
/evolve payment-recurring Add support for proration and billing cycles
```

---

## 🎯 Key Principles

1. **Specs First** - Always generate specs before code
2. **Verify Always** - Never skip user verification
3. **Track Everything** - Tasks auto-created and tracked
4. **Evolve Naturally** - Update specs when needed
5. **Stay Consistent** - Follow project patterns

---

## 🆘 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| "Beads not found" | System auto-falls back to Cursor tasks |
| "Old commands not working" | Use `/sdd` and `/build` instead |
| "Specs in wrong place" | New location: `.kiro/specs/` |
| "Need to update specs" | Use `/evolve [feature] [changes]` |

---

**Remember**: One command (`/sdd`) generates everything you need. Just verify and build! 🚀
