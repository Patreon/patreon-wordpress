<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Patreon_OAuth
{
    public $client_id;
    public $client_secret;

    public function __construct()
    {
        $this->client_id = get_option('patreon-client-id', false);
        $this->client_secret = get_option('patreon-client-secret', false);
    }

    public function get_tokens($code, $redirect_uri, $params = [])
    {
        return $this->__get_or_update_token(
            array_merge(
                [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'redirect_uri' => $redirect_uri,
                ],
                $params
            ), false
        );
    }

    public function refresh_token($refresh_token, $redirect_uri, $disable_app_on_auth_err)
    {
        $result = $this->__get_or_update_token([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ], $disable_app_on_auth_err);

        return $result;
    }

    private function __get_or_update_token($params, $disable_app_on_auth_err)
    {
        $api_endpoint = 'https://'.PATREON_HOST.'/api/oauth2/token';

        $headers = PatreonApiUtil::get_default_headers();
        $api_request = [
            'method' => 'POST',
            'body' => $params,
            'headers' => $headers,
        ];

        $response = wp_remote_post($api_endpoint, $api_request);

        if (is_wp_error($response)) {
            $result = ['error' => $response->get_error_message()];
            $GLOBALS['patreon_notice'] = $response->get_error_message();

            Patreon_Wordpress::log_connection_error($GLOBALS['patreon_notice']);

            return $result;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($disable_app_on_auth_err && 401 == $status_code) {
            // Token refresh failed. Mark the app integration credentials as
            // bad. This is done for creator access token to prevent spamming
            // Patreon's API with token refresh requests using invalid or
            // expired credentials. Add a cooldown period when the token refresh
            // could be retried.
            update_option('patreon-wordpress-app-credentials-failure', true);
            set_transient('patreon-wordpress-app-creator-token-refresh-cooldown', true, PATREON_CREATOR_TOKEN_REFRESH_ATTEMPT_COOLDOWN_S);

            Patreon_Wordpress::log_connection_error('Failed get/update creator token. HTTP '.$status_code.', Response: '.$response['body']);
        } elseif (200 != $status_code) {
            Patreon_Wordpress::log_connection_error('Failed get/update token. HTTP '.$status_code.', Response: '.$response['body']);
        }

        $response_decoded = json_decode($response['body'], true);
        if (!is_array($response_decoded) || !isset($response_decoded['access_token'], $response_decoded['refresh_token'])) {
            Patreon_Wordpress::log_connection_error('Invalid token refresh response '.$response['body']);
        }

        $result = [];

        if (isset($response_decoded['access_token'])) {
            $result['access_token'] = $response_decoded['access_token'];
        }

        if (isset($response_decoded['refresh_token'])) {
            $result['refresh_token'] = $response_decoded['refresh_token'];
        }

        if (isset($response_decoded['expires_in'])) {
            $result['expires_in'] = $response_decoded['expires_in'];
        }

        if (isset($response_decoded['scope'])) {
            $result['scope'] = $response_decoded['scope'];
        }

        $result['http_status_code'] = $status_code;

        return $result;
    }
}
