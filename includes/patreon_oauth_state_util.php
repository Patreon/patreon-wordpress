<?php

class PatreonOauthStateUtil
{
    public const NONCE_ACTION = 'patreon_oauth_state';
    public const NONCE_KEY = 'patreon_nonce';

    // Encodes the state var sent to Patreon, binding it to the current user's session so the
    // /patreon-authorization/ callback can reject cross-site requests
    public static function encode_state($state)
    {
        if (!is_array($state)) {
            $state = [];
        }

        if (is_user_logged_in()) {
            $state[self::NONCE_KEY] = wp_create_nonce(self::NONCE_ACTION);
        }

        return urlencode(base64_encode(json_encode($state)));
    }

    // The callback only changes existing accounts or site settings when a user is logged in, so
    // that is when the nonce is required. Logged out users share a nonce, so it would not help them.
    public static function is_state_valid($state)
    {
        if (!is_user_logged_in()) {
            return true;
        }

        if (!is_array($state) or !isset($state[self::NONCE_KEY]) or !is_string($state[self::NONCE_KEY])) {
            return false;
        }

        return false !== wp_verify_nonce($state[self::NONCE_KEY], self::NONCE_ACTION);
    }
}
