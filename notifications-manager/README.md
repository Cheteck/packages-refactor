# IJIDeals Notifications Manager Package

This package will be responsible for managing and centralizing user notification preferences and potentially the dispatch of notifications across various channels (e.g., email, SMS, push, in-app).

It aims to provide a unified system for users to control how they receive notifications from different parts of the IJIDeals ecosystem and for developers to easily manage notification types and templates.

## Core Features (Proposed)

-   **User Notification Preferences:** Allow users to configure preferences for various notification types (e.g., new message, order update, new follower, promotion).
-   **Channel Management:** Enable/disable notification delivery through different channels (email, SMS, push, in-app/database).
-   **Notification Templates:** (Optional) Manage templates for various notifications.
-   **Centralized Dispatch (Optional):** Could act as a central point for dispatching notifications, or simply manage preferences that other packages then respect.
-   **Grouping & Categorization:** Group notification types for easier management by users.
-   **Digest Notifications:** Support for daily/weekly digest emails for certain notification types.
-   **Do Not Disturb (DND) Settings:** Allow users to set DND periods.
