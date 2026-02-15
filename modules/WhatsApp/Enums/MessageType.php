<?php

namespace Modules\WhatsApp\Enums;

enum MessageType: string
{
    case TEXT = 'text';
    case TEMPLATE = 'template';
    case IMAGE = 'image';
    case DOCUMENT = 'document';
    case INTERACTIVE = 'interactive';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => __('Text Message'),
            self::TEMPLATE => __('Template Message'),
            self::IMAGE => __('Image'),
            self::DOCUMENT => __('Document'),
            self::INTERACTIVE => __('Interactive'),
        };
    }
}
