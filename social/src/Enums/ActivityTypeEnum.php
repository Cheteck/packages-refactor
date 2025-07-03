<?php

namespace IJIDeals\Social\Enums;

enum ActivityTypeEnum: string
{
    case USER_CREATED = 'user_created';
    case POST_CREATED = 'post_created';
    case COMMENT_CREATED = 'comment_created';
    case REACTION_CREATED = 'reaction_created';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
