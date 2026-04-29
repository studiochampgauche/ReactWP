<?php

namespace ReactWP\Runtime;

class PreviewToken {

    const DEFAULT_TTL = 600;

    public static function create($post_id, $ttl = null) {

        $post_id = (int)$post_id;

        if($post_id < 1){
            return '';
        }

        $ttl = $ttl !== null ? max(60, (int)$ttl) : self::DEFAULT_TTL;
        $payload = [
            'postId' => $post_id,
            'expires' => time() + $ttl,
        ];
        $encoded = self::base64url_encode(wp_json_encode($payload));
        $signature = hash_hmac('sha256', $encoded, self::secret());

        return $encoded . '.' . $signature;

    }

    public static function validate($token, $post_id = null) {

        $token = is_string($token) ? trim($token) : '';
        $parts = explode('.', $token);

        if(count($parts) !== 2){
            return new \WP_Error(
                'reactwp_preview_token_invalid',
                __('Invalid preview token.', 'reactwp'),
                ['status' => 403]
            );
        }

        [$encoded, $signature] = $parts;
        $expected = hash_hmac('sha256', $encoded, self::secret());

        if(!hash_equals($expected, $signature)){
            return new \WP_Error(
                'reactwp_preview_token_invalid',
                __('Invalid preview token.', 'reactwp'),
                ['status' => 403]
            );
        }

        $payload = json_decode(self::base64url_decode($encoded), true);

        if(!is_array($payload) || empty($payload['postId']) || empty($payload['expires'])){
            return new \WP_Error(
                'reactwp_preview_token_invalid',
                __('Invalid preview token.', 'reactwp'),
                ['status' => 403]
            );
        }

        if((int)$payload['expires'] < time()){
            return new \WP_Error(
                'reactwp_preview_token_expired',
                __('Preview token expired.', 'reactwp'),
                ['status' => 403]
            );
        }

        $resolved_post_id = (int)$payload['postId'];

        if($post_id !== null && $resolved_post_id !== (int)$post_id){
            return new \WP_Error(
                'reactwp_preview_token_mismatch',
                __('Preview token does not match the requested post.', 'reactwp'),
                ['status' => 403]
            );
        }

        return $resolved_post_id;

    }

    private static function secret() {

        return wp_salt('auth') . '|reactwp-headless-preview';

    }

    private static function base64url_encode($value) {

        return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '=');

    }

    private static function base64url_decode($value) {

        $value = strtr((string)$value, '-_', '+/');
        $padding = strlen($value) % 4;

        if($padding){
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value);

    }

}
