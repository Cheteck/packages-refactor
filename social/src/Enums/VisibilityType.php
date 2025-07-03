<?php

namespace IJIDeals\Social\Enums;

enum VisibilityType: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case FOLLOWERS = 'followers';
    // Add other visibility types if needed, e.g., FRIENDS_ONLY, CUSTOM
}
