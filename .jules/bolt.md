## 2024-05-23 - [Prevent Redundant Eager Loading of Current User]
**Learning:** In user-scoped dashboard queries (e.g., `$user->transactions()`), eager loading `user` (`->with('user')`) is wasteful. It triggers an extra query to fetch the user record we already have in memory.
**Action:** Always verify if the relationship being eager loaded is already available in the current context (e.g., `auth()->user()`). If so, remove the `with()` clause.

## 2025-05-20 - [Optimizing Rolling Window Aggregations]
**Learning:** Aggregating user data for charts (e.g., "last 12 months") without a date filter causes full-table scans and merges historical data incorrectly (e.g., Jan 2023 + Jan 2024). Using `whereYear` is also incorrect as it cuts off data at year boundaries.
**Action:** Explicitly calculate the start date (e.g., `now()->subMonths(11)`) and add `where('created_at', '>=', $startDate)` to all aggregation queries. This ensures correctness and leverages `created_at` indexes.

## 2025-05-23 - [Optimize Date Loop Instantiations]
**Learning:** Instantiating `Carbon` objects inside a loop (e.g., `Carbon::today()->subMonth($i)`) is expensive because it re-initializes the date object and internal helpers multiple times.
**Action:** Instantiate `Carbon` once outside the loop and modify it iteratively (e.g., `$date->addMonth()`) to reduce object creation overhead.

## 2025-05-23 - [Cache Auth User ID in Loops/Closures]
**Learning:** Repeatedly calling `auth()->id()` or `auth()->user()` inside loops or closures adds overhead from the container and auth driver.
**Action:** Assign `$userId = auth()->id()` to a local variable before the loop/closure and use that variable instead.
