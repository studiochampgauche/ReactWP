<?php

class WP_Post {
    public $ID;
    public $post_status;

    public function __construct($id, $status = 'draft') {
        $this->ID = (int)$id;
        $this->post_status = $status;
    }
}

class WP_Error {
    private $code;
    private $message;
    private $data;

    public function __construct($code, $message = '', $data = []) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function get_error_code() {
        return $this->code;
    }
}

$GLOBALS['rwp_preview_can_edit'] = false;

function get_post($post_id) {
    return (int)$post_id === 99 ? null : new WP_Post($post_id);
}

function current_user_can($capability, $post_id = null) {
    return $capability === 'edit_post' && !empty($GLOBALS['rwp_preview_can_edit']);
}

function apply_filters($hook, $value) {
    return $value;
}

function wp_json_encode($value) {
    return json_encode($value, JSON_UNESCAPED_SLASHES);
}

function wp_salt($scheme = 'auth') {
    return 'test-secret-' . $scheme;
}

function __($message) {
    return $message;
}

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/PreviewToken.php';

use ReactWP\Runtime\PreviewToken;

$assert = static function($condition, $message){
    if($condition){
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

$assert(PreviewToken::create(7) === '', 'Preview tokens must not be issued without edit permission.');
$GLOBALS['rwp_preview_can_edit'] = true;
$assert(PreviewToken::create(99) === '', 'Preview tokens must not be issued for missing posts.');

$token = PreviewToken::create(7, 120);
$assert(is_string($token) && $token !== '', 'An authorized editor must receive a preview token.');
$assert(PreviewToken::validate($token, 7) === 7, 'A valid preview token must resolve its post.');

$tampered = substr($token, 0, -1) . (substr($token, -1) === 'a' ? 'b' : 'a');
$result = PreviewToken::validate($tampered, 7);
$assert($result instanceof WP_Error && $result->get_error_code() === 'reactwp_preview_token_invalid', 'A modified signature must be rejected.');

$result = PreviewToken::validate($token, 8);
$assert($result instanceof WP_Error && $result->get_error_code() === 'reactwp_preview_token_mismatch', 'A token must not preview another post.');

$encode = static function(array $payload){
    $encoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', $encoded, wp_salt('auth') . '|reactwp-headless-preview');

    return $encoded . '.' . $signature;
};

$now = time();
$expired = $encode([
    'version' => 1,
    'postId' => 7,
    'issuedAt' => $now - 180,
    'expires' => $now - 60,
]);
$result = PreviewToken::validate($expired, 7);
$assert($result instanceof WP_Error && $result->get_error_code() === 'reactwp_preview_token_expired', 'Expired preview tokens must be rejected.');

$oversized_ttl = $encode([
    'version' => 1,
    'postId' => 7,
    'issuedAt' => $now,
    'expires' => $now + PreviewToken::MAX_TTL + 1,
]);
$result = PreviewToken::validate($oversized_ttl, 7);
$assert($result instanceof WP_Error && $result->get_error_code() === 'reactwp_preview_token_invalid', 'Tokens exceeding the maximum lifetime must be rejected.');

$result = PreviewToken::validate(str_repeat('a', PreviewToken::MAX_TOKEN_BYTES + 1), 7);
$assert($result instanceof WP_Error && $result->get_error_code() === 'reactwp_preview_token_invalid', 'Oversized preview tokens must be rejected.');

fwrite(STDOUT, "Preview token tests passed.\n");
