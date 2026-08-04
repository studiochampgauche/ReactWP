<?php

namespace ReactWP\Runtime;

class RenderCache {

    private const INVALIDATIONS_OPTION = 'rwp_render_invalidations';
    private const MAX_INVALIDATIONS = 500;
    private const STATE_VERSION = 2;
    private const LOCK_OPTION = 'rwp_render_invalidations_lock';
    private const FAILSAFE_OPTION = 'rwp_render_invalidation_failsafe';

    public static function boot() {

        add_action('save_post', [self::class, 'invalidate_post'], 20, 3);
        add_action('deleted_post', [self::class, 'invalidate_deleted_post'], 20, 2);
        add_action('wp_update_nav_menu', [self::class, 'invalidate_navigation']);
        add_action('created_term', [self::class, 'invalidate_term'], 20, 3);
        add_action('edited_term', [self::class, 'invalidate_term'], 20, 3);
        add_action('delete_term', [self::class, 'invalidate_term'], 20, 4);
        add_action('acf/save_post', [self::class, 'invalidate_acf'], 30);
        add_action('rwp_client_cache_busted', [self::class, 'invalidate_all']);

    }

    public static function invalidate($tags) {

        $tags = self::normalize_tags($tags);

        if(!$tags){
            return;
        }

        $lock = self::acquire_lock();
        $timestamp = microtime(true);

        if($lock === null){
            update_option(self::FAILSAFE_OPTION, $timestamp + 30, false);
            do_action('rwp_render_cache_invalidated', $tags, $timestamp);
            return;
        }

        $state = self::state();
        $tag_invalidations = array_values(array_diff($tags, ['render:all']));

        if(in_array('render:all', $tags, true)){
            $state['global'] = $timestamp;
        }

        foreach($tag_invalidations as $tag){
            $state['tags'][$tag] = $timestamp;
        }

        if(count($state['tags']) > self::MAX_INVALIDATIONS){
            asort($state['tags'], SORT_NUMERIC);
            $pruned_count = count($state['tags']) - self::MAX_INVALIDATIONS;
            $pruned = array_slice($state['tags'], 0, $pruned_count, true);

            if($pruned){
                $state['prunedBefore'] = max(
                    (float)$state['prunedBefore'],
                    max(array_map('floatval', $pruned))
                );
            }

            $state['tags'] = array_slice($state['tags'], -self::MAX_INVALIDATIONS, null, true);
        }

        try {
            update_option(self::INVALIDATIONS_OPTION, $state, false);
        } finally {
            self::release_lock($lock);
        }

        do_action('rwp_render_cache_invalidated', $tags, $timestamp);

    }

    public static function is_fresh($entry) {

        if(!is_array($entry)){
            return false;
        }

        $generated_at = isset($entry['generatedAtUnix'])
            ? (float)$entry['generatedAtUnix']
            : strtotime((string)($entry['generatedAt'] ?? ''));

        if(!$generated_at){
            return false;
        }

        $state = self::state();

        if(
            (float)$state['global'] > $generated_at
            || (float)$state['prunedBefore'] > $generated_at
        ){
            return false;
        }

        foreach(self::normalize_tags($entry['tags'] ?? []) as $tag){
            if(isset($state['tags'][$tag]) && (float)$state['tags'][$tag] > $generated_at){
                return false;
            }
        }

        return true;

    }

    public static function invalidate_post($post_id, $post, $update) {

        if(
            wp_is_post_revision($post_id)
            || wp_is_post_autosave($post_id)
            || !$post instanceof \WP_Post
        ){
            return;
        }

        self::invalidate([
            'post:' . (int)$post_id,
            'post-type:' . sanitize_key($post->post_type),
        ]);

    }

    public static function invalidate_deleted_post($post_id, $post) {

        self::invalidate([
            'post:' . (int)$post_id,
            'post-type:' . sanitize_key($post instanceof \WP_Post ? $post->post_type : 'unknown'),
        ]);

    }

    public static function invalidate_navigation() {

        self::invalidate('menu:all');

    }

    public static function invalidate_term($term_id, $term_taxonomy_id = null, $taxonomy = '') {

        self::invalidate([
            'term:' . (int)$term_id,
            'taxonomy:' . sanitize_key((string)$taxonomy),
        ]);

    }

    public static function invalidate_acf($post_id) {

        if(in_array($post_id, ['options', 'option'], true)){
            self::invalidate('settings:all');
        }

    }

    public static function invalidate_all() {

        self::invalidate('render:all');

    }

    private static function state() {

        $stored = get_option(self::INVALIDATIONS_OPTION, []);

        if(
            is_array($stored)
            && (int)($stored['version'] ?? 0) === self::STATE_VERSION
        ){
            $tags = [];

            foreach(array_slice(is_array($stored['tags'] ?? null) ? $stored['tags'] : [], -self::MAX_INVALIDATIONS, null, true) as $tag => $timestamp){
                if(self::normalize_tags([$tag]) && is_numeric($timestamp)){
                    $tags[$tag] = (float)$timestamp;
                }
            }

            return [
                'version' => self::STATE_VERSION,
                'global' => max(
                    (float)($stored['global'] ?? 0),
                    (float)get_option(self::FAILSAFE_OPTION, 0)
                ),
                'prunedBefore' => (float)($stored['prunedBefore'] ?? 0),
                'tags' => $tags,
            ];
        }

        $tags = [];

        foreach(is_array($stored) ? $stored : [] as $tag => $timestamp){
            if(
                is_string($tag)
                && self::normalize_tags([$tag])
                && is_numeric($timestamp)
            ){
                $tags[$tag] = (float)$timestamp;
            }
        }

        return [
            'version' => self::STATE_VERSION,
            'global' => max(
                (float)($tags['render:all'] ?? 0),
                (float)get_option(self::FAILSAFE_OPTION, 0)
            ),
            'prunedBefore' => 0.0,
            'tags' => array_diff_key($tags, ['render:all' => true]),
        ];

    }

    private static function normalize_tags($tags) {

        $tags = array_slice(is_array($tags) ? $tags : [$tags], 0, 1000);

        return array_values(array_unique(array_filter(array_map(function($tag){
            $tag = strtolower(trim((string)$tag));
            return preg_match('/^[a-z0-9_-]+:[a-z0-9_.-]+$/', $tag) ? $tag : '';
        }, $tags))));

    }

    private static function acquire_lock() {

        if(!function_exists('add_option') || !function_exists('delete_option')){
            return null;
        }

        $token = function_exists('wp_generate_uuid4')
            ? wp_generate_uuid4()
            : uniqid('rwp-', true);
        $deadline = microtime(true) + 2.0;

        do {
            if(add_option(self::LOCK_OPTION, [
                'token' => $token,
                'createdAt' => microtime(true),
            ], '', false)){
                return $token;
            }

            $existing = get_option(self::LOCK_OPTION, []);

            if(
                is_array($existing)
                && (float)($existing['createdAt'] ?? 0) < microtime(true) - 10
            ){
                delete_option(self::LOCK_OPTION);
                continue;
            }

            usleep(20000);
        } while(microtime(true) < $deadline);

        return null;

    }

    private static function release_lock($token) {

        if($token === null || !function_exists('delete_option')){
            return;
        }

        $existing = get_option(self::LOCK_OPTION, []);

        if(is_array($existing) && hash_equals((string)($existing['token'] ?? ''), (string)$token)){
            delete_option(self::LOCK_OPTION);
        }

    }

}
