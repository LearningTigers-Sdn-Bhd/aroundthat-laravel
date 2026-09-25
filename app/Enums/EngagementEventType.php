<?php

namespace App\Enums;

/**
 * What a visitor did with a place on a partner's site or app.
 */
enum EngagementEventType: string
{
    /** The place was shown in a list or on a map. */
    case PlaceImpression = 'place_impression';

    /** The visitor opened the place's page. */
    case PlaceView = 'place_view';

    /** The visitor followed one of the place's links, such as its phone number or website. */
    case OutboundClick = 'outbound_click';
}
