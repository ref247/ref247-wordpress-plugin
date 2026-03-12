<?php

namespace Ref247\Integrations;

use Ref247\Api\Ref247Client;
use Ref247\Tracking\ReferralTracker;

class WPFormsIntegration
{
    public function __construct()
    {
        add_action('wpforms_process_complete', [$this, 'handleSubmission'], 10, 4);
    }

    /**
     * @param array $fields
     * @param array $entry
     * @param array $form_data
     * @param int $entry_id
     */
    public function handleSubmission($fields, $entry, $form_data, $entry_id)
    {
        // Get stored affiliate data
        $affiliateData = ReferralTracker::getStoredData();
        $affId = isset($affiliateData['affId']) ? $affiliateData['affId'] : null;
        $linkUri = isset($affiliateData['linkUri']) ? $affiliateData['linkUri'] : null;

        if (!$affId && !$linkUri) {
            return;
        }

        $orgId = get_option('ref247_org_id');
        $eventTypeId = get_option('ref247_form_event_type_id');

        if (!$orgId || !$eventTypeId) {
            return;
        }

        $client = new Ref247Client();
        
        $referralActionData = [
            'amount' => 0,
            'eventTypeId' => $eventTypeId,
            'affiliationId' => $affId,
            'linkUri' => $linkUri,
            'orgId' => $orgId
        ];

        $client->createReferralAction($referralActionData);

        // Check if we should clear the cookie
        $clearCookie = get_option('ref247_clear_cookie_on_purchase', '1');
        if ($clearCookie === '1') {
            ReferralTracker::clear();
        }
    }
}
