<?php

namespace ReactWP\Runtime;

class PreviewToken {

    const DEFAULT_TTL = 600;
    const MAX_TTL = 3600;
    const MAX_TOKEN_BYTES = 2048;

    public static function create($post_id, $ttl = null) {

        $post_id = (int)$post_id;

        if($post_id < 1){
            return '';
        }

        $post = get_post($post_id);
        $authorized = $post instanceof \WP_Post
            && $post->post_status !== 'trash'
            && current_user_can('edit_post', $post_id);
        $authorized = (bool)apply_filters(
            'rwp_preview_token_authorized',
            $authorized,
            $post_id,
            $post
        );

        if(!$authorized){
            return '';
        }

        $max_ttl = min(86400, max(60, (int)apply_filters('rwp_preview_token_max_ttl', self::MAX_TTL)));
        $ttl = $ttl !== null ? max(60, (int)$ttl) : self::DEFAULT_TTL;
        $ttl = min($ttl, $max_ttl);
        $issued_at = time();
        $payload = [
            'version' => 1,
            'postId' => $post_id,
            'issuedAt' => $issued_at,
            'expires' => $issued_at + $ttl,
        ];
        $encoded = self::base64url_encode(wp_json_encode($payload));
        $signature = hash_hmac('sha256', $encoded, self::secret());

        return $encoded . '.' . $signature;

    }

    public static function validate($token, $post_id = null) {

        $token = is_string($token) ? trim($token) : '';
        $parts = explode('.', $token);

        if(
            $token === ''
            || strlen($token) > self::MAX_TOKEN_BYTES
            || count($parts) !== 2
            || !preg_match('/^[A-Za-z0-9_-]+$/', $parts[0] ?? '')
            || !preg_match('/^[a-f0-9]{64}$/', $parts[1] ?? '')
        ){
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

        $decoded = self::base64url_decode($encoded);
        $payload = is_string($decoded)
            ? json_decode($decoded, true, 8)
            : null;

        if(
            !is_array($payload)
            || (int)($payload['version'] ?? 0) !== 1
            || empty($payload['postId'])
            || empty($payload['issuedAt'])
            || empty($payload['expires'])
        ){
            return new \WP_Error(
                'reactwp_preview_token_invalid',
                __('Invalid preview token.', 'reactwp'),
                ['status' => 403]
            );
        }

        $issued_at = (int)$payload['issuedAt'];
        $expires = (int)$payload['expires'];
        $now = time();
        $max_ttl = min(86400, max(60, (int)apply_filters('rwp_preview_token_max_ttl', self::MAX_TTL)));

        if(
            $issued_at > $now + 60
            || $expires <= $issued_at
            || ($expires - $issued_at) > $max_ttl
        ){
            return new \WP_Error(
                'reactwp_preview_token_invalid',
                __('Invalid preview token.', 'reactwp'),
                ['status' => 403]
            );
        }

        if($expires < $now){
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

        return base64_decode($value, true);

    }

}
