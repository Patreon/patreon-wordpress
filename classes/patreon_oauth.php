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
        // TODO: Can this be used for non-creator token? Should false/treu
        return $this->__update_token(
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

    public function refresh_token($refresh_token, $redirect_uri, $is_creator_token)
    {
        if (PatreonApiUtil::is_credentials_broken()) {
            return;
        }

        $result = $this->__update_token([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ], $is_creator_token);

        return $result;
    }

    private function __update_token($params, $is_creator_token)
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

        if ($is_creator_token && 401 == $status_code) {
            update_option('patreon-wordpress-app-credentials-failure', true);
        }

        $response_decoded = json_decode($response['body'], true);

        // Log the connection as having error if the return is not 200

        if (isset($response['response']['code']) and '200' != $response['response']['code']) {
            Patreon_Wordpress::log_connection_error('Response code: '.$response['response']['code'].' Response :'.$response['body']);
        }

        if (is_array($response_decoded)) {
            return $response_decoded;
        }

        // Commented out to address issues caused by Patreon's maintenance in between 01 - 02 Feb 2019 - the plugin was showing Patreon's maintenance page at WP sites yin certain cases
        // echo $response['body'];
        // wp_die();
    }
}
