## 2025-12-27 - [Redundant Eager Loading]
**Learning:** Codebase convention explicitly advises against eager loading `user` on user-scoped queries. Verified this applies to `UserDashboardService`.
**Action:** Look for similar patterns in other user-scoped controllers/services.
