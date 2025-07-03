# Social Package

The Social package is the heart of the community and content creation experience on the IJIDeals platform. It provides the core features of a modern social network, enabling users to connect, share, and interact in a rich and engaging environment.

## Core Features

-   **User Profiles & Feeds**: Extends user profiles with social attributes and provides a content feed.
-   **Content Creation**: Users can create various types of `Post`s (text, images, videos).
-   **Follow System**: Users can follow each other to see their content.
-   **Reactions & Comments**: Rich interaction system with configurable reactions (like, love, etc.) and threaded comments.
-   **Hashtags**: Automatic parsing and linking of #hashtags in posts for content discovery.
-   **Groups & Events**: Functionality for users to create and join interest-based groups and organize events.
-   **Reporting System**: Users can report inappropriate content for moderation.

## Key Components

### Models

-   `Post`: The central model for any piece of user-generated content.
-   `Comment`: Represents a comment on a `Post` or other commentable models.
-   `Reaction`: Stores a user's reaction to a `Reactable` model.
-   `Follow`: Represents the relationship between a follower and the user being followed.
-   `Hashtag`: Represents a single #hashtag.
-   `Group` & `Event`: Models for community groups and events.

### Traits

A suite of traits makes it easy to add social features across the platform:
-   `HasFollowers`: Add to the `User` model.
-   `HasPosts`: Add to the `User` model.
-   `HasComments`: Add to any model to make it commentable.
-   `HasReactions`: Add to any model to allow user reactions.

## How It Works

This package forms the social graph of the platform. It generates a rich stream of events and data that can be used to build user feeds, generate notifications, and understand user interests. It is the foundation upon which other community packages like `Review System` and `Sponsorship` are built.

## Dependencies

-   **`ijideals/user-management`**: Essential for the concept of users, profiles, and identity.
-   **`ijideals/file-management`**: For handling media uploads in posts and comments.

## Installation

The Social package is a core component of the IJIDeals application and is installed as part of the main application setup.

Key setup points relevant to this package include:

1.  **Service Provider:**
    The package's service provider is `IJIDeals\Social\Providers\SocialServiceProvider`. This is typically registered in `config/app.php` or auto-discovered by Laravel.

2.  **Database Migrations:**
    The package includes migrations for creating tables such as `posts`, `comments`, `likes`, `follows`, and `notifications`. These are run with the main application migrations:
    ```bash
    php artisan migrate
    ```

## API Documentation

Full API documentation, including detailed endpoint descriptions, request parameters, and example responses, is available through our Scribe-generated documentation.

Please visit [API Documentation](/docs) and navigate to sections related to "Social", "Posts", "Comments", "Likes", "Follows", and "Notifications".

## Key Features

*   **Post Management:** Create, read, update, and delete posts.
*   **Commenting System:** Allow users to comment on posts.
*   **Likes/Reactions:** Enable users to like posts.
*   **Follow System:** Allow users to follow and unfollow each other, and view follower/following lists.
*   **Notifications:** Provide a system for user notifications related to social activities.
*   **Authentication:** API endpoints are protected by Sanctum authentication.
*   **Authorization:** Policies are used to control access to social features and content.
```
