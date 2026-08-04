<?php

namespace {
    class WP_Post {
        public $ID;
        public $post_type = 'post';
        public $post_status = 'publish';
        public $post_password = '';
        public $post_parent = 0;

        public function __construct($id, $status = 'publish') {
            $this->ID = $id;
            $this->post_status = $status;
        }
    }

    class WP_Term {}

    class WP_User {
        public $ID;
        public $user_nicename = 'author';
        public $display_name = 'Author';

        public function __construct($id) {
            $this->ID = $id;
        }
    }

    function get_post_type($id) {
        return (int)$id >= 100 ? 'attachment' : 'post';
    }

    function get_post($id) {
        if((int)$id === 100){
            $attachment = new WP_Post(100);
            $attachment->post_type = 'attachment';
            $attachment->post_parent = 2;
            return $attachment;
        }

        return new WP_Post((int)$id, (int)$id === 2 ? 'private' : 'publish');
    }

    function wp_get_attachment_url() { return 'https://example.test/media.jpg'; }
    function wp_get_attachment_metadata() { return ['width' => 100, 'height' => 100]; }
    function get_post_meta() { return ''; }
    function get_post_mime_type() { return 'image/jpeg'; }
    function get_permalink($post) { return 'https://example.test/post-' . $post->ID . '/'; }
    function get_the_title($post) { return 'Post ' . $post->ID; }
    function get_author_posts_url($id) { return 'https://example.test/author-' . $id . '/'; }
    function is_wp_error() { return false; }
    function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
    function esc_url_raw($value) { return (string)$value; }
    function get_bloginfo($field) { return $field === 'name' ? 'Example' : ''; }
    function get_locale() { return 'en_CA'; }
    function home_url($path = '/') { return 'https://example.test' . $path; }
    function rest_url($path = '') { return 'https://example.test/wp-json/' . ltrim($path, '/'); }
    function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
}

namespace ReactWP\Runtime {
    class RouteResolver {
        public static function is_public_object($post) {
            return $post instanceof \WP_Post
                && $post->post_status === 'publish'
                && $post->post_password === '';
        }

        public static function is_public_author($user) {
            return $user instanceof \WP_User && $user->ID === 1;
        }

        public static function normalize_path($value) {
            return '/' . trim((string)$value, '/') . '/';
        }
    }

    class RenderStrategy {
        public static function normalize($config) {
            return [
                'mode' => 'client',
                'cache' => [
                    'html' => false,
                    'scope' => 'public',
                    'ttl' => 0,
                    'payload' => false,
                    'media' => false,
                    'tags' => [],
                ],
            ];
        }
    }
}

namespace {
    require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/PublicPayload.php';

    $method = new ReflectionMethod(ReactWP\Runtime\PublicPayload::class, 'value');
    $method->setAccessible(true);
    $payload = $method->invoke(null, [
        'publicPost' => new WP_Post(1),
        'privatePost' => new WP_Post(2, 'private'),
        'publicAuthor' => new WP_User(1),
        'privateUser' => new WP_User(2),
        'privateAttachment' => ['ID' => 100, 'url' => 'https://example.test/private.jpg'],
    ]);

    foreach(['privatePost', 'privateUser', 'privateAttachment'] as $key){
        if($payload[$key] !== null){
            fwrite(STDERR, "Private nested value leaked through PublicPayload: {$key}\n");
            exit(1);
        }
    }

    $route_method = new ReflectionMethod(ReactWP\Runtime\PublicPayload::class, 'route_object');
    $route_method->setAccessible(true);
    $route = $route_method->invoke(null, [
        'id' => 1,
        'template' => '../Unsafe',
        'pageName' => 'Example route',
        'path' => '/example/',
        'head' => ['<title>Example route</title>', ['invalid']],
    ]);

    if($route['template'] !== 'Default' || $route['head'] !== ['<title>Example route</title>']){
        fwrite(STDERR, "Route template or head normalization failed.\n");
        exit(1);
    }

    fwrite(STDOUT, "Public payload tests passed.\n");
}
