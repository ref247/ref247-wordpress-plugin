<?php

namespace Ref247\Integrations;

use Ref247\Api\Ref247Client;
use Ref247\Tracking\ReferralTracker;

class ContactForm7Integration
{
    public function __construct()
    {
        add_action('wpcf7_mail_sent', [$this, 'handleSubmission']);
    }

    /**
     * @param \WPCF7_ContactForm $contact_form
     */
    public function handleSubmission($contact_form)
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
        
        // CF7 submissions usually don't have an amount, so we use 0
        $referralActionData = [
            'amount' => 0,
            'eventTypeId' => $eventTypeId,
            'affiliationId' => $affId,
            'linkUri' => $linkUri,
            'orgId' => $orgId
        ];

        $result = $client->createReferralAction($referralActionData);

        if ($result && !is_wp_error($result)) {
            // Check if we should clear the cookie
            $clearCookie = get_option('ref247_clear_cookie_on_purchase', '1');
            if ($clearCookie === '1') {
                ReferralTracker::clear();
            }
        }
    }
}
