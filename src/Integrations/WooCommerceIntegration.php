<?php

namespace Ref247\Integrations;

use Ref247\Api\Ref247Client;
use Ref247\Tracking\ReferralTracker;

class WooCommerceIntegration
{
    public function __construct()
    {
        // Fires when payment is completed (gateways)
        add_action('woocommerce_payment_complete', [$this, 'handleOrder'], 10);
        
        // Fires when status changes to completed (manual or automated)
        add_action('woocommerce_order_status_completed', [$this, 'handleOrder'], 10);
    }

    /**
     * @param int $order_id
     * @param array $posted_data
     * @param \WC_Order $order
     */
    public function handleOrder($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Prevent duplicate processing
        if ($order->get_meta('_ref247_processed')) {
            return;
        }

        // Get stored affiliate data
        $affiliateData = ReferralTracker::getStoredData();
        $affId = isset($affiliateData['affId']) ? $affiliateData['affId'] : null;
        $linkUri = isset($affiliateData['linkUri']) ? $affiliateData['linkUri'] : null;

        if (!$affId && !$linkUri) {
            // Only add note if we haven't tried before to avoid clutter
            if (!$order->get_meta('_ref247_not_found_logged')) {
                $order->add_order_note('Ref247: No affiliate cookie data found.');
                $order->update_meta_data('_ref247_not_found_logged', '1');
                $order->save();
            }
            return;
        }

        $orgId = get_option('ref247_org_id');
        $eventTypeId = get_option('ref247_wc_event_type_id');

        $error_text = '';

        if (!$orgId) {
            $error_text .= 'No organization ID found.\n';
        }

        if (!$eventTypeId) {
            $error_text .= 'No event type ID found.\n';
        }

        $currencies = get_option('ref247_currencies');
        if (!$currencies || empty($currencies)) {
            $error_text .= 'No currencies found.\n';
        }

        if ($error_text) {
            $order->add_order_note('Ref247 Error: ' . $error_text);
            return;
        }
        $order->add_order_note('Ref247: Affiliate ID: ' . $affId . ' Link URI: ' . $linkUri);

        // automatically assign first currency if no match is found
        $currencyId = $currencies[0]['id'];
        foreach ($currencies as $currency) {
            if (strtolower($currency['name']) === strtolower($order->get_currency())) {
                $currencyId = $currency['id'];
                break;
            }
        }

        $client = new Ref247Client();
        
        $referralActionData = [
            'amount' => (float) $order->get_total(),
            'eventTypeId' => $eventTypeId,
            'affiliationId' => $affId,
            'linkUri' => $linkUri,
            'orgId' => $orgId,
            'currencyId' => $currencyId
        ];

        $result = $client->createReferralAction($referralActionData);

        if ($result && !is_wp_error($result)) {
            // Mark as processed regardless of cookie clearing
            $order->update_meta_data('_ref247_processed', '1');
            $order->save();

            // Check if we should clear the cookie
            $clearCookie = get_option('ref247_clear_cookie_on_purchase', '1');
            if ($clearCookie === '1') {
                ReferralTracker::clear();
            }
            
            // Log the referral action ID if needed or just mark the order
            if (isset($result['inserted']) && $result['inserted'] > 0) {
                $order->add_order_note('Ref247: Referral action tracked successfully.');
            }
        } else {
            $order->add_order_note('Ref247: Failed to track referral action.');
        }
    }
}
