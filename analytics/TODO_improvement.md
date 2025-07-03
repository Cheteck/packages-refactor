# TODO for Analytics Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Refine `AggregateAnalyticsJob`:**
    -   [ ] **Engagement Score Calculation**: Implement a more robust `calculateEngagementScore()` method in `TrackableStatsDaily` or within the job, considering various interaction types and their weights (configurable via `config/analytics.php`).
    -   [ ] **Specific Interaction Columns**: Decide if `TrackableStatsDaily` should have dedicated columns for common interaction types (e.g., `custom_interaction_type_count`) instead of or in addition to `interaction_summary` JSON, for easier querying. If so, update migration and aggregation logic.
    -   [ ] **Batching/Chunking Robustness**: Further test and optimize chunking in `AggregateAnalyticsJob` for very large datasets.
    -   [ ] **Error Handling**: Enhance error handling within the job for individual record processing failures.
    -   [ ] **Configurable Frequency**: Ensure the job scheduling frequency (currently hardcoded as `everyMinute()` in `AuctionSystemServiceProvider`'s example, but this job belongs to `Analytics`) is configurable via `config/analytics.php` and set up in `AnalyticsServiceProvider` or Kernel.
-   **Decouple `TrackableStats` Trait:** (From original TODO)
    -   [ ] **Option B (Loose Coupling - Recommended):** Modify the trait to dispatch generic events (e.g., `AnalyticsInteractionRecorded`, `AnalyticsHighEngagementDetected`) that this package defines. Other packages (`social`, `commerce`) can then listen to these generic events if needed.
    -   [ ] For `instanceof Shop` check, consider using a common interface like `HighlyEngageable` or a configurable mapping that relevant models can implement or be part of.
    -   [ ] If Option A (tighter coupling) is kept, ensure `ijideals/social` and `ijideals/commerce` are added to `suggests` in `composer.json`.
-   **Implement "Event Tracking" API (`Analytics::track(...)`):** (From original TODO)
    -   [ ] Create an `Analytics` Facade and underlying service (`AnalyticsService`).
    -   [ ] Implement `AnalyticsService::track(string $eventName, array $properties = [], ?Model $trackable = null, ?User $user = null)` method to log custom events to `ActivityLog`.
    -   [ ] This service could also be responsible for dispatching `RecordViewJob` or directly creating `TrackableView`/`TrackableInteraction` records if synchronous processing is sometimes desired.
-   **User Cohort Analysis:**
    -   [ ] Explore adding functionality for basic cohort analysis (e.g., tracking user retention or feature adoption over time based on registration date or first interaction). This would likely require new models and aggregation jobs.
-   **Data Export & Reporting Features:** (From original TODO)
    -   [ ] Define scope: What kind of custom reports or data exports are needed?
    -   [ ] If simple CSV exports, add basic export functionality to a controller.
    -   [ ] For complex reports, consider integration with a reporting tool or a dedicated reporting service within the package.

## 🔧 API & Configuration

-   **API Endpoints for Analytics Data:** (From original TODO)
    -   [ ] If analytics data (e.g., `TrackableStatsDaily` for a specific item, popular items) needs to be exposed via API, create `AnalyticsController` and routes.
    -   [ ] Implement API Resources for formatting responses.
    -   [ ] Add Policies for authorization.
-   **Refine `config/analytics.php`:** (From original TODO)
    -   [ ] Add cache TTLs for stats (e.g., used in `TrackableStats` trait: `view_dedupe_minutes`, `interaction_cache_hours`).
    -   [ ] Add configuration for `RecordViewJob` (e.g., queue connection/name).
    -   [ ] Add configuration for `AggregateAnalyticsJob` (frequency, batch size, specific interaction types to aggregate into columns if that path is chosen).
    -   [ ] Add feature flags (e.g., enable/disable view tracking, interaction tracking, activity logging globally or per type).
    -   [ ] Define configurable weights for different interactions for `engagement_score`.
-   **Model Configuration:**
    -   [ ] `ActivityLog::getParentModel()`: Make parent model detection more flexible (e.g., via an interface on loggable models or a configurable mapping in `analytics.php`) rather than hardcoded relationship names.

## 🧹 Code Quality & Maintenance

-   **Address Internal Model TODOs:** (From original TODO)
    -   [ ] Review `// TODO:` comments in `TrackableInteraction`, `TrackableStatsDaily`, `TrackableView` regarding refactoring/moving (some might be obsolete now that they are in the package).
-   **Standardize Trait Naming:** (From original TODO)
    -   [ ] `TrackableStats` (actual trait name). Choose one and update README and code comments. (`TrackableStats` seems more descriptive of its current function).
-   **Improve `HasHistory` Trait:**
    -   [ ] Review its current implementation (if any beyond basic logging) and enhance its flexibility for tracking model changes. Ensure it logs to `ActivityLog` effectively.
-   **PHPStan/Static Analysis:**
    -   [ ] Run static analysis and fix reported issues.

## 📚 Documentation & Testing

-   **README Update:**
    -   [ ] Rewrite "Models" and "Features" sections to accurately reflect the package's capabilities.
    -   [ ] Document how to make a model "trackable" (using `TrackableStats` trait).
    -   [ ] Document the `Analytics::track()` facade/service for custom event logging.
    -   [ ] Explain how `AggregateAnalyticsJob` works and how to schedule it.
    -   [ ] Detail configuration options from `analytics.php`.
    -   [ ] Provide clear examples of how other packages should dispatch events for `ActivityLog` consumption.
-   **Testing Strategy:** (From original TODO)
    *   [ ] Write unit tests for all models, traits, and jobs.
    *   [ ] Test `RecordViewJob` (ensuring it creates `TrackableView` records).
    *   [ ] Test `AggregateAnalyticsJob` functionality with various data scenarios.
    *   [ ] Test caching mechanisms in `TrackableStats` trait.
    *   [ ] Test `HasHistory` trait logic.

## 💡 Remodularization Suggestions

*   **`ActivityLog` to `ijideals/audit-trail`?**: If `ActivityLog` becomes very complex and is used for more than just analytics (e.g., security audits, detailed user action history for support), it could potentially be spun off into its own `ijideals/audit-trail` package. For now, its place in `analytics` is reasonable if focused on user behavior analytics.
*   **Reporting Module**: If complex reporting UIs or data export/transformation features are envisioned, they could form a distinct sub-module or even a separate `ijideals/reporting` package that consumes data from `ijideals/analytics`.

This provides a more focused list of improvements for the `Analytics` package.
