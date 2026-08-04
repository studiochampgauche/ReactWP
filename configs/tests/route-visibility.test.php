<?php

class WP_Post {
    public $ID = 1;
    public $post_status = 'publish';
    public $post_password = '';
}

class WP_User {
    public $ID;

    public function __construct($id) {
        $this->ID = $id;
    }

    public function exists() {
        return $this->ID > 0;
    }
}

class WP_Query {
    public $posts = [];

    public function __construct($args) {
        $this->posts = (int)($args['author'] ?? 0) === 1 ? [10] : [];
    }
}

function is_post_publicly_viewable($post) {
    return $post instanceof WP_Post && $post->post_status === 'publish';
}

function get_post_types() {
    return ['post', 'page', 'attachment'];
}

function apply_filters($hook, $value) {
    return $value;
}

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/RouteResolver.php';

use ReactWP\Runtime\RouteResolver;

$assert_same = static function($expected, $actual, $message){
    if($expected === $actual){
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

$published = new WP_Post();
$assert_same(true, RouteResolver::is_public_object($published), 'Published content must remain public.');

$draft = new WP_Post();
$draft->post_status = 'draft';
$assert_same(false, RouteResolver::is_public_object($draft), 'Draft content must not be public.');

$private = new WP_Post();
$private->post_status = 'private';
$assert_same(false, RouteResolver::is_public_object($private), 'Private content must not be public.');

$password_protected = new WP_Post();
$password_protected->post_password = 'secret';
$assert_same(false, RouteResolver::is_public_object($password_protected), 'Password-protected content must not enter public payloads.');

$assert_same(true, RouteResolver::is_public_author(new WP_User(1)), 'Authors with public posts may appear in public payloads.');
$assert_same(false, RouteResolver::is_public_author(new WP_User(2)), 'Users without public posts must not be enumerable as public authors.');

fwrite(STDOUT, "Route visibility tests passed.\n");
