<?php

class PatreonCreatorUtil
{
    public static function creator_has_tiers()
    {
        $value = get_option('patreon-creator-has-tiers', true);

        // Handle legacy 'yes'/'no' string values from older installs
        return $value && 'no' !== $value;
    }
}
