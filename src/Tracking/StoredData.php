<?php

namespace Ref247\Tracking;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class StoredData
 *
 * Reprents the affiliate tracking data stored in the cookie.
 * Centralizes sanitization for affiliate ID, link URI, and click ID.
 *
 * @package Ref247\Tracking
 */
class StoredData
{
    /** @var string|null */
    public $affId = null;

    /** @var string|null */
    public $linkUri = null;

    /** @var string|null */
    public $clickId = null;

    /**
     * StoredData constructor.
     *
     * @param array $data Raw data from cookie or source.
     */
    public function __construct(array $data = [])
    {
        if (isset($data['affId'])) {
            $this->affId = sanitize_text_field($data['affId']);
        }
        if (isset($data['linkUri'])) {
            $this->linkUri = sanitize_text_field($data['linkUri']);
        }
        if (isset($data['clickId'])) {
            $this->clickId = sanitize_text_field($data['clickId']);
        }
    }

    /**
     * Convert properties to an associative array for storage or API calls.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'affId' => $this->affId,
            'linkUri' => $this->linkUri,
            'clickId' => $this->clickId,
        ]);
    }
}
