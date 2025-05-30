<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Switcher - this will conditionally include another file which has a API v2 version of this class - until all installs are upgraded to v2. If this install is old, this should default to false.

$load_api_version = get_option('patreon-installation-api-version', false);

// Override api version even if the site is v1 in case delete / reconnect actions are requested. This is temporary until we have something on API side which will allow v1 sites to just reconnect to v2

if (isset($_REQUEST['patreon_wordpress_action']) and 'disconnect_site_from_patreon' == $_REQUEST['patreon_wordpress_action']) {
    $load_api_version = '2';
}

if (isset($_REQUEST['patreon_wordpress_action']) and 'disconnect_site_from_patreon_for_reconnection' == $_REQUEST['patreon_wordpress_action']) {
    $load_api_version = '2';
}

// Added to catch setup wizard/connection cases when user lands back at patreon-authorization

// Check if code exists in request

if (false !== strpos($_SERVER['REQUEST_URI'], '/patreon-authorization/')) {
    if (isset($_REQUEST['code'])) {
        // Get state vars if they exist

        if (isset($_REQUEST['state']) and '' != $_REQUEST['state']) {
            $returned_state_var = json_decode(base64_decode(urldecode($_REQUEST['state'])), true);
        }
    }
}

if (isset($returned_state_var) and isset($returned_state_var['patreon_action']) and ('reconnect_site' == $returned_state_var['patreon_action'] or 'connect_site' == $returned_state_var['patreon_action'])) {
    $load_api_version = '2';
}

if ($load_api_version and '2' == $load_api_version) {
    // Include the v2 version of this class and return
    require 'patreon_api_v2.php';

    return;
} else {
    Patreon_Wordpress::log_connection_error('Either your site has an old connection version or it lost its connection info. Please reconnect your site at WP admin -> Patreon Settings -> (re)Connect');

    class Patreon_API
    {
        public $access_token;

        public function __construct($access_token)
        {
            $this->access_token = $access_token;
        }

        public function fetch_user($v2 = false)
        {
            // Only uses v2 starting from this version!

            // We construct the old return from the new returns by combining /me and pledge details

            $api_return = $this->__get_json('identity?include=memberships.currently_entitled_tiers&fields[user]=email,first_name,full_name,image_url,last_name,thumb_url,url,vanity,is_email_verified&fields[member]=currently_entitled_amount_cents,lifetime_support_cents,last_charge_status,patron_status,last_charge_date,pledge_relationship_start', true);

            $creator_id = get_option('patreon-creator-id', false);

            if (isset($api_return['included'][0]) and is_array($api_return['included'][0])) {
                $api_return['included'][0]['relationships']['creator']['data']['id'] = $creator_id;
                $api_return['included'][0]['type'] = 'pledge';
                $api_return['included'][0]['attributes']['amount_cents'] = $api_return['included'][0]['attributes']['currently_entitled_amount_cents'];
                $api_return['included'][0]['attributes']['created_at'] = $api_return['included'][0]['attributes']['pledge_relationship_start'];

                if ('Paid' != $api_return['included'][0]['attributes']['last_charge_status']) {
                    $api_return['included'][0]['attributes']['declined_since'] = $api_return['included'][0]['attributes']['last_charge_date'];
                }
            }

            return $api_return;
        }

        public function fetch_campaign_and_patrons($v2 = false)
        {
            // Below conditional and different endpoint can be deprecated to only use v2 api after transition period. Currently we are using v1 for creator/campaign related calls

            if ($v2) {
                // New call to campaigns doesnt return pledges in v2 api - currently this function is not used anywhere in plugin. If 3rd party devs are using it, it will need to be looked into

                // Requires having gotten permission for pledge scope during auth if used for a normal user instead of the creator

                return $this->__get_json('campaigns');
            }

            return $this->__get_json('current_user/campaigns?include=rewards,creator,goals,pledges');
        }

        public function fetch_creator_info($v2 = false)
        {
            // Below conditional and different endpoint can be deprecated to only use v2 api after transition period. Currently we are using v1 for creator/campaign related calls

            if ($v2) {
                // New call to campaigns doesnt return pledges in v2 api - currently this function is not used anywhere in plugin. If 3rd party devs are using it, it will need to be looked into

                $api_return = $this->__get_json('identity');
                $api_return['included'][0]['id'] = $api_return['data'][0]['id'];

                return $api_return;
            }

            return $this->__get_json('current_user/campaigns?include=creator');
        }

        public function fetch_campaign($v2 = false)
        {
            // Below conditional and different endpoint can be deprecated to only use v2 api after transition period

            if ($v2) {
                return $this->__get_json('campaigns?include=tiers,creator,goals', $v2);
            }

            return $this->__get_json('current_user/campaigns?include=rewards,creator,goals');
        }

        public function fetch_tiers($v2 = false)
        {
            // Below conditional and different endpoint can be deprecated to only use v2 api after transition period

            if ($v2) {
                return $this->__get_json('campaigns?include=tiers,creator,goals', $v2);
            }

            return $this->__get_json('current_user/campaigns?include=rewards');
        }

        public function __get_json($suffix, $v2 = false)
        {
            // v1 is deprecated. Return false
            $result = ['error' => 'Either your site has an old connection version or it lost its connection info. Please reconnect your site at WP admin -> Patreon Settings -> (re)Connect'];

            return $result;

            $api_endpoint = 'https://'.PATREON_HOST.'/api/oauth2/api/'.$suffix;

            if ($v2) {
                $api_endpoint = 'https://'.PATREON_HOST.'/api/oauth2/v2/'.$suffix;
            }

            $default_headers = PatreonApiUtil::get_default_headers();
            $headers = [
                'Authorization' => 'Bearer '.$this->access_token,
            ];
            $headers = array_merge($default_headers, $headers);
            $api_request = [
                'headers' => $headers,
                'method' => 'GET',
            ];

            $response = wp_remote_request($api_endpoint, $api_request);
            $result = $response;

            if (is_wp_error($response)) {
                $result = ['error' => $response->get_error_message()];
                $GLOBALS['patreon_notice'] = $response->get_error_message();

                $caller = 'none';
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

                if (isset($backtrace[1]['function'])) {
                    $caller = $backtrace[1]['function'];
                }

                Patreon_Wordpress::log_connection_error($caller.' - API v1 Class - '.$GLOBALS['patreon_notice']);

                return $result;
            }

            // Log the connection as having error if the return is not 200

            if (isset($response['response']['code']) and '200' != $response['response']['code'] and '201' != $response['response']['code']) {
                $caller = 'none';
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

                if (isset($backtrace[1]['function'])) {
                    $caller = $backtrace[1]['function'];
                }

                $uuid = wp_remote_retrieve_header($response, 'x-patreon-uuid');

                Patreon_Wordpress::log_connection_error($caller.' -  API v1 Class - UUID '.$uuid.' - Response code: '.$response['response']['code'].' Response :'.$response['body']);
            }

            return json_decode($response['body'], true);
        }
    }
}
