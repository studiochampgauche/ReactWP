<?php

namespace ReactWP\Runtime;

class ClientCache {

    private const OPTION_NAME = 'rwp_client_cache_version';
    private const DEFAULT_VERSION = '1';

    public static function version() {

        $version = get_option(self::OPTION_NAME, self::DEFAULT_VERSION);
        $version = is_scalar($version) ? (string)$version : self::DEFAULT_VERSION;
        $version = preg_replace('/[^a-zA-Z0-9._-]/', '-', $version);

        return $version !== '' ? $version : self::DEFAULT_VERSION;

    }

    public static function bust() {

        $version = sprintf(
            '%s-%s',
            time(),
            strtolower(wp_generate_password(8, false, false))
        );

        update_option(self::OPTION_NAME, $version, false);
        do_action('rwp_client_cache_busted', $version);

        return $version;

    }

}
