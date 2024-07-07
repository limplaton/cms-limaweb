<?php
 

namespace Modules\Documents\App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case LOST = 'lost';

    /**
     * Get the status color.
     */
    public function color(): string
    {
        return match ($this) {
            DocumentStatus::DRAFT => '#64748b',
            DocumentStatus::SENT => '#3b82f6',
            DocumentStatus::ACCEPTED => '#22c55e',
            DocumentStatus::LOST => '#f43f5e',
        };
    }

    /**
     * Get the status icon.
     */
    public function icon(): string
    {
        return match ($this) {
            DocumentStatus::DRAFT => 'LightBulb',
            DocumentStatus::SENT => 'Mail',
            DocumentStatus::ACCEPTED => 'Check',
            DocumentStatus::LOST => 'XSolid',
        };
    }

    /**
     * Get the status displayable name.
     */
    public function displayName(): string
    {
        return __('documents::document.status.'.$this->value) ?: $this->value;
    }
}
