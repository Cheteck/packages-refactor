# TODO for Social Package (Improvements)

## 🚀 Core Functionality Enhancements

-   **Post Model (`Post.php`) Enhancements:**
    -   [ ] **Media Handling**:
        -   Clarify use of Spatie MediaLibrary vs. `ijideals/file-management`. `Post` uses `HasMedia` and `InteractsWithMedia` (Spatie) AND has an `attachments()` MorphMany to `Attachment` (presumably `file-management`). Decide on a primary strategy.
        -   If Spatie MediaLibrary is primary for images/videos in posts: Define media collections (e.g., 'post_images', 'post_videos'). Ensure uploads via `PostController` use these collections. Remove `attachments()` relation if redundant or repurpose for non-Spatie managed files (e.g., documents attached to a post).
    -   [ ] **Content Processing**: Implement hashtag parsing/linking (trait `HasHashtags` exists, ensure it's robust and links to `Hashtag` model). Implement @mention parsing and notification (`NotifyMentionedUsers` job exists, ensure it's triggered).
    -   [ ] **Polls (`PostPoll`, `PostPollVote` models exist):** Implement API and logic for creating posts with polls, and for users to vote. Broadcast updates.
    -   [ ] **Visibility/Privacy**: Implement post visibility settings (public, friends_only, private) and ensure queries respect these.
    -   [ ] **Post Types (`PostTypeEnum` exists):** Ensure different post types (text, image, video, poll, shared_post) are handled correctly in creation, display, and feed generation.
-   **Comment System (`Comment.php`):**
    -   [ ] Implement threaded comments (parent/child relationships).
    -   [ ] Add support for comment reactions (model `Reaction` can target `Comment`).
    -   [ ] Implement comment editing/deletion with appropriate permissions (`CommentPolicy` exists).
    -   [ ] Notifications for comment replies and mentions.
-   **Groups (`Group.php`, `GroupMember.php`, `GroupInvitation.php`):**
    -   [ ] Implement full CRUD for groups (create, view, update, delete).
    -   [ ] Membership management: join, leave, invite, accept/decline invitation, manage roles (admin, moderator, member) using Spatie permissions scoped to group instance.
    -   [ ] Group privacy (public, private, secret).
    -   [ ] Posts within groups (link `Post` to `Group`).
    -   [ ] Group-specific notifications.
-   **Events (`Event.php` - Social Events):**
    -   [ ] Implement CRUD for social events.
    -   [ ] RSVP system (going, interested, not_going).
    -   [ ] Event visibility, invitations, reminders (`EventReminder` notification exists).
    -   [ ] Link events to location, hosts (users/groups).
-   **Stories (`Story.php` - Ephemeral Content):**
    -   [ ] Implement story creation (image/video).
    -   [ ] Logic for story expiration (e.g., after 24 hours, via scheduled job).
    -   [ ] Viewing stories, seen status.
    -   [ ] Story replies/reactions.
-   **Feed Generation:**
    -   [ ] Develop algorithm/service for generating user feeds (posts from followed users/entities, group posts, potentially recommended content).
    -   [ ] Implement `UserFeedPreference.php` to allow users to customize their feed.
    -   [ ] Optimize feed queries for performance.
-   **Notifications (`Notification.php` model, `NotificationController`):**
    -   [ ] This seems to be an in-app social notification system (distinct from Laravel's core notifications).
    -   [ ] Ensure it correctly logs all relevant social events (new follower, new comment, post reaction, group join request, event invitation).
    -   [ ] Implement marking notifications as read/unread.
    -   [ ] API for fetching user's social notifications.
-   **Reporting System (`Report.php`):**
    -   [ ] Implement API for users to report content (Posts, Comments, Users, Groups).
    -   [ ] Admin interface/API for reviewing and acting on reports.
    -   [ ] Link reports to `ReportReasonEnum` or similar configurable reasons.

## 🔧 API & Controller Enhancements

-   **Standardize Controllers:**
    -   [ ] Review all controllers (`PostController`, `CommentController`, `FollowController`, `LikeController` (to be removed/refactored to `ReactionController`), `NotificationController`).
    -   [ ] Create controllers for `Group`, `Event`, `Story`, `Report`, `Friendship`.
    -   [ ] Use services for business logic. Use API Resources for responses.
-   **Refine API Routes:**
    -   [ ] If `Reaction` system is adopted, change `LikeController` routes (e.g., `posts/{post}/likes`) to more generic reaction routes (e.g., `reactables/{type}/{id}/reactions` or `posts/{post}/reactions`).
-   **Policies:**
    -   [ ] `PostPolicy`, `CommentPolicy` exist. Review and complete.
    -   [ ] Create and implement Policies for `Group`, `Event`, `Reaction`, `Follow`, `Friendship`, `Story`, `Report`. Enforce in controllers.

## ⚙️ Configuration & Setup

-   **Create `config/social.php`:** (From original TODO in SP)
    -   [ ] Add settings for: default post visibility, configurable reaction types, feed algorithm parameters, content moderation flags, Group/Event default settings, Story duration.
    -   [ ] Update `SocialServiceProvider` to merge and publish this config.
-   **Spatie MediaLibrary Configuration for `Post`:**
    -   [ ] Define media collections, conversions for images/videos in `Post` model if Spatie ML is chosen as primary.

## 🧹 Code Quality & Model Refinements

-   **Namespace Standardization:** (From original TODO)
    -   [P] Correct internal `use` statements in all files (e.g., `App\Enums\*` in `Post.php` should be local or from `ijideals/support`). (Partially done, needs full sweep)
-   **Trait `HasFollowers.php`:**
    -   [X] Hardcoded `\App\Models\Shop` was fixed by unifying to polymorphic `follows`.
-   **Redundant `LikeController` and `LikeResource`:**
    -   [ ] Remove if "Likes" are fully merged into "Reactions".
-   **Enums (`ActivityTypeEnum`, `PostTypeEnum` exist):**
    -   [ ] Ensure they are used consistently. Create Enums for other status/type fields (e.g., `FriendshipStatus`, `ReactionType`, `GroupPrivacy`, `EventRsvpStatus`).

## 📚 Documentation & Testing

-   **README Update:** (Many items from original TODO)
    -   [ ] Document all social features, models, and their interactions.
    -   [ ] Detail all API endpoints.
    -   [ ] Explain real-time features and Echo setup.
    -   [ ] List and explain configuration options.
-   **Testing Strategy:**
    -   [ ] Write feature tests for all social interactions (posts, comments, reactions, follows, friendships, groups, events).
    -   [ ] Test all API endpoints, policies, and validation.
    -   [ ] Test real-time event broadcasting.

## 💡 Remodularization Suggestions

*   **`FeedService`**: Feed generation can be complex and resource-intensive. A dedicated service or even a sub-package might be warranted if multiple feed types or complex algorithms are involved.
*   **`ContentModerationPackage`**: If reporting and content moderation (for posts, comments, user profiles, groups) becomes a large, cross-cutting concern, it could be extracted into a dedicated `ijideals/content-moderation` package.
*   **`GroupsPackage` / `EventsPackage`**: If Group or Event functionalities grow significantly beyond basic social interactions (e.g., complex event ticketing, advanced group permission systems), they could be candidates for their own packages.

This package is very large. Focus on solidifying core interactions like Posts, Comments, Reactions, and Follows first.
