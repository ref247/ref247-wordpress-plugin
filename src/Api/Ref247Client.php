<?php

namespace Ref247\Api;

class Ref247Client
{
    private $apiUrl = "https://ref247.io/api";
    private $apiKey;
    private $apiSecret;

    public function __construct()
    {
        $this->apiKey = get_option('ref247_api_key');
        $this->apiSecret = get_option('ref247_api_secret');
    }

    private function getHeaders()
    {
        return [
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->apiKey,
            'X-API-SECRET' => $this->apiSecret,
        ];
    }

    public function sendConversion($data)
    {
        return wp_remote_post($this->apiUrl . '/conversion', [
            'headers' => $this->getHeaders(),
            'body' => json_encode($data)
        ]);
    }

    public function getOrganizations()
    {
        $response = wp_remote_get($this->apiUrl . '/organizations', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }

    public function getAllCampaignsOfOrganization($orgId)
    {
        $response = wp_remote_get($this->apiUrl . '/marketing/' . $orgId . '/campaign?withDeleted=false', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }

    public function getRoles()
    {
        $response = wp_remote_get($this->apiUrl . '/role', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }

    public function addUserToOrganization($orgId, $email, $roleId, $linkUri, $parentAffiliateId)
    {
        $data = [
            'email' => $email,
            'roleId' => $roleId,
        ];
        if ($linkUri !== null) {
            $data['linkUri'] = $linkUri;
        }
        if ($parentAffiliateId !== null) {
            $data['parentAffiliateId'] = $parentAffiliateId;
        }
        $response = wp_remote_post($this->apiUrl . '/organizations/' . $orgId . '/users', [
            'headers' => $this->getHeaders(),
            'body' => json_encode($data)
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }

    public function getOrganizationEventTypes($orgId)
    {
        $response = wp_remote_get($this->apiUrl . '/organizations/' . $orgId . '/eventTypes', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function addOrganizationEventType($orgId, $name)
    {
        $response = wp_remote_post($this->apiUrl . '/organizations/' . $orgId . '/eventTypes', [
            'headers' => $this->getHeaders(),
            'body' => json_encode(['name' => $name])
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function createReferralAction($referralActionData)
    {
        // Notice it takes an array of actions, so we wrap it in an array
        $response = wp_remote_post($this->apiUrl . '/tracking/referral-actions', [
            'headers' => $this->getHeaders(),
            'body' => json_encode([$referralActionData])
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getOrganizationCurrencies($orgId)
    {
        $response = wp_remote_get($this->apiUrl . '/organizations/' . $orgId . '/currency', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getOrganizationAffiliationStats($orgId)
    {
        $response = wp_remote_get($this->apiUrl . '/affiliate/' . $orgId . '/organization/stats', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getOrganizationCommissionStats($orgId)
    {
        $response = wp_remote_get($this->apiUrl . '/commission/organization/' . $orgId . '/summary', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getOrganizationGenericStats($orgId)
    {
        $response = wp_remote_get($this->apiUrl . '/organizations/' . $orgId . '/genericStats', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getOrganizationPrivateStats($orgId)
    {
        $response = wp_remote_get($this->apiUrl . '/organizations/' . $orgId . '/privateStats', [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getOrgCommissionsChartData($orgId, $startDate = null, $endDate = null)
    {
        $url = $this->apiUrl . '/commission/organization/' . $orgId . '/chart/commissions';
        $params = [];
        if ($startDate instanceof \DateTime) {
            $params['startDate'] = $startDate->format('c');
        }
        if ($endDate instanceof \DateTime) {
            $params['endDate'] = $endDate->format('c');
        }
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = wp_remote_get($url, [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function getOrgReferralsChartData($orgId, $startDate = null, $endDate = null)
    {
        $url = $this->apiUrl . '/affiliate/organization/' . $orgId . '/chart/referrals';
        $params = [];
        if ($startDate instanceof \DateTime) {
            $params['startDate'] = $startDate->format('c');
        }
        if ($endDate instanceof \DateTime) {
            $params['endDate'] = $endDate->format('c');
        }
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = wp_remote_get($url, [
            'headers' => $this->getHeaders()
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
