<?php

namespace App\Enums;

/**
 * Where an outbound click took the visitor.
 */
enum OutboundDestination: string
{
    case Map = 'map';
    case Phone = 'phone';
    case Website = 'website';
    case Whatsapp = 'whatsapp';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
}
