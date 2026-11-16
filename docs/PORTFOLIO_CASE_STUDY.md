# ShopAdmin — Engineering Case Study & Quality Roadmap

**Portfolio:** [Digital Solution9](https://github.com/shakaleita/teamforge-saas/blob/main/PORTFOLIO.md) · **Repository:** [Source](https://github.com/shakaleita/shopadmin)  
**Stack:** PHP, Laravel, MySQL · **Status:** Local portfolio MVP; not production-certified.

## Implemented domain workflow
Order and catalogue management with integer-cent totals, fulfilment states and order history.

## Domain invariants
Captured prices must be immutable order snapshots; status transition graph must be valid; cancelled orders must not become shipped.

## Verification backlog (future work)
- Prices in order lines remain unchanged after catalogue edits
- Invalid fulfilment transition rejected
- Concurrent status updates preserve a single coherent lifecycle

## High-value engineering upgrades
1. Add authenticated staff roles and order audits
2. Add return/refund and stock-reservation workflows
3. Add browser tests, indexed queries and production migration scripts

## Production release gate

Pass permission-boundary tests, validate concurrency and financial or domain invariants, verify database migrations, run CI and browser tests, deploy synthetic-data staging, measure performance, test accessibility, and document threat mitigations. The open issues in this file are not completed features.

**Scope:** Engineering roadmap, not a certification of readiness.
