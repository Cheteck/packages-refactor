<?php

namespace IJIDeals\Social\Enums;

enum PostTypeEnum: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case CAROUSEL = 'carousel';
    case POLL = 'poll';
    case PRODUCT = 'product';
    case ARTICLE = 'article';
    case EVENT = 'event';
    case LIVE = 'live';
    case STORY = 'story'; // Expire après 24h
}
