<?php

namespace ReactWP\Runtime;

class StaticRegenerator {

    private const QUEUE_OPTION = 'rwp_static_regeneration_queue';
    private const CRON_HOOK = 'rwp_regenerate_static_routes';
    private const RUN_LOCK_OPTION = 'rwp_static_regeneration_lock';
    private const MAX_QUEUE_SIZE = 5000;
    private const MAX_MANIFEST_BYTES = 2 * 1024 * 1024;
    private const MAX_MANIFEST_ENTRIES = 50000;

    public static function boot() {

        add_action('rwp_render_cache_invalidated', [self::class, 'schedule'], 10, 2);
        add_action(self::CRON_HOOK, [self::class, 'run']);

    }

    public static function schedule($tags) {

        $tags = array_values(array_filter(array_map('strval', (array)$tags)));

        if(!$tags){
            return;
        }

        $queue = get_option(self::QUEUE_OPTION, []);
        $queue = is_array($queue) ? $queue : [];

        foreach(InitialRender::manifest_entries() as $key => $entry){
            $entry_tags = is_array($entry['tags'] ?? null) ? $entry['tags'] : [];

            if(!array_intersect($tags, $entry_tags)){
                continue;
            }

            $path = (string)($entry['path'] ?? '/');
            $search = (string)($entry['search'] ?? '');

            if(!RestAccess::is_safe_view($path . $search)){
                continue;
            }

            $queue[$key] = [
                'key' => (string)$key,
                'path' => $path,
                'search' => $search,
                'lang' => (string)($entry['lang'] ?? ''),
                'attempts' => (int)($queue[$key]['attempts'] ?? 0),
            ];
        }

        if(!$queue){
            return;
        }

        if(count($queue) > self::MAX_QUEUE_SIZE){
            $queue = array_slice($queue, -self::MAX_QUEUE_SIZE, null, true);
        }

        update_option(self::QUEUE_OPTION, $queue, false);

        if(!wp_next_scheduled(self::CRON_HOOK)){
            wp_schedule_single_event(time() + 5, self::CRON_HOOK);
        }

    }

    public static function run() {

        $lock = get_option(self::RUN_LOCK_OPTION, []);

        if(is_array($lock) && (float)($lock['createdAt'] ?? 0) >= microtime(true) - 300){
            return;
        }

        if($lock){
            delete_option(self::RUN_LOCK_OPTION);
        }

        $token = function_exists('wp_generate_uuid4')
            ? wp_generate_uuid4()
            : uniqid('rwp-', true);

        if(!add_option(self::RUN_LOCK_OPTION, [
            'token' => $token,
            'createdAt' => microtime(true),
        ], '', false)){
            return;
        }

        try{
            self::run_queue();
        } finally {
            $current_lock = get_option(self::RUN_LOCK_OPTION, []);

            if(is_array($current_lock) && hash_equals((string)($current_lock['token'] ?? ''), $token)){
                delete_option(self::RUN_LOCK_OPTION);
            }
        }

    }

    private static function run_queue() {

        $queue = get_option(self::QUEUE_OPTION, []);
        $queue = is_array($queue) ? $queue : [];
        $limit = max(1, (int)apply_filters('rwp_static_regeneration_batch_size', 10));
        $batch = array_slice($queue, 0, $limit, true);

        foreach($batch as $key => $item){
            unset($queue[$key]);

            if(!self::regenerate($item)){
                $item['attempts'] = (int)($item['attempts'] ?? 0) + 1;

                if($item['attempts'] < 3){
                    $queue[$key] = $item;
                }
            }
        }

        update_option(self::QUEUE_OPTION, $queue, false);

        if($queue && !wp_next_scheduled(self::CRON_HOOK)){
            wp_schedule_single_event(time() + 30, self::CRON_HOOK);
        }

    }

    private static function regenerate($item) {

        $language = sanitize_key((string)($item['lang'] ?? ''));

        if($language !== '' && function_exists('pll_switch_language')){
            pll_switch_language($language);
        }

        if($language !== ''){
            do_action('wpml_switch_language', $language);
        }

        $view = (string)($item['path'] ?? '/') . (string)($item['search'] ?? '');

        if(!RestAccess::is_safe_view($view)){
            return self::remove($item);
        }

        $route = RouteResolver::from_path($view);
        $config = RenderStrategy::normalize($route['render'] ?? []);

        if($config['mode'] !== 'static' || !empty($route['is404'])){
            return self::remove($item);
        }

        $payload = Bootstrap::payload($route);
        $result = ServerRenderer::render_result($payload, [
            'mode' => 'server',
            'cache' => [
                'html' => false,
                'scope' => 'private',
                'ttl' => 0,
            ],
        ]);

        if(!is_array($result) || !is_string($result['html'] ?? null)){
            return false;
        }

        return self::write($route, $result);

    }

    private static function remove($item) {

        $key = (string)($item['key'] ?? '');

        if($key === ''){
            return true;
        }

        foreach(self::manifest_paths() as $manifest_path){
            if(!is_file($manifest_path) || !is_readable($manifest_path)){
                continue;
            }

            $manifest = self::read_manifest($manifest_path);

            if(!is_array($manifest) || !is_array($manifest['entries'] ?? null)){
                continue;
            }

            $entry = $manifest['entries'][$key] ?? null;

            if(!is_array($entry)){
                continue;
            }

            self::delete_fragment($manifest_path, $entry);
            unset($manifest['entries'][$key]);
            $manifest['generatedAt'] = gmdate('c');
            $manifest['generatedAtUnix'] = microtime(true);
            $manifest['cacheVersion'] = ClientCache::version();

            if(!self::write_manifest($manifest_path, $manifest)){
                return false;
            }
        }

        return true;

    }

    private static function manifest_paths() {

        $paths = [
            get_stylesheet_directory() . '/assets/render/static/manifest.json',
        ];
        $uploads = wp_upload_dir(null, false);

        if(empty($uploads['error']) && !empty($uploads['basedir'])){
            array_unshift(
                $paths,
                trailingslashit($uploads['basedir']) . 'reactwp/render/static/manifest.json'
            );
        }

        return array_values(array_unique($paths));

    }

    private static function delete_fragment($manifest_path, $entry) {

        $relative_file = str_replace('\\', '/', (string)($entry['file'] ?? ''));

        if($relative_file === '' || strpos($relative_file, '..') !== false || $relative_file[0] === '/'){
            return;
        }

        $base_directory = realpath(dirname($manifest_path));
        $fragment_path = realpath(dirname($manifest_path) . '/' . $relative_file);

        if(
            !$base_directory
            || !$fragment_path
            || strpos($fragment_path, $base_directory . DIRECTORY_SEPARATOR) !== 0
            || !is_file($fragment_path)
        ){
            return;
        }

        @unlink($fragment_path);

    }

    private static function write($route, $result) {

        $uploads = wp_upload_dir(null, false);

        if(!empty($uploads['error']) || empty($uploads['basedir'])){
            return false;
        }

        $base = trailingslashit($uploads['basedir']) . 'reactwp/render/static';
        $fragments = $base . '/fragments';

        if(!wp_mkdir_p($fragments)){
            return false;
        }

        self::protect_directories($uploads['basedir'], $base, $fragments);

        $key = RenderStrategy::route_key($route);
        $filename = hash('sha256', $key) . '.html';
        $fragment_path = $fragments . '/' . $filename;

        if(!self::atomic_write($fragment_path, $result['html'])){
            return false;
        }

        $manifest_path = $base . '/manifest.json';
        $manifest = self::read_manifest($manifest_path) ?: [];
        $manifest = is_array($manifest) ? $manifest : [];
        $manifest['version'] = 1;
        $manifest['theme'] = get_stylesheet();
        $manifest['siteUrl'] = home_url('/');
        $manifest['generatedAt'] = gmdate('c');
        $manifest['generatedAtUnix'] = microtime(true);
        $manifest['cacheVersion'] = ClientCache::version();
        $manifest['entries'] = is_array($manifest['entries'] ?? null) ? $manifest['entries'] : [];

        if(count($manifest['entries']) >= self::MAX_MANIFEST_ENTRIES && !isset($manifest['entries'][$key])){
            array_shift($manifest['entries']);
        }

        $manifest['entries'][$key] = [
            'key' => $key,
            'path' => $route['path'] ?? '/',
            'search' => $route['search'] ?? '',
            'lang' => $route['lang'] ?? '',
            'template' => $route['template'] ?? 'Default',
            'file' => 'fragments/' . $filename,
            'generatedAt' => gmdate('c'),
            'generatedAtUnix' => microtime(true),
            'cacheVersion' => ClientCache::version(),
            'tags' => array_values(array_unique(array_filter(array_map(function($tag){
                $tag = strtolower(trim((string)$tag));
                return preg_match('/^[a-z0-9_-]+:[a-z0-9_.-]+$/', $tag) ? $tag : '';
            }, array_slice((array)($result['tags'] ?? []), 0, 200))))),
        ];

        return self::write_manifest($manifest_path, $manifest);

    }

    private static function read_manifest($path) {

        if(
            !is_string($path)
            || !is_file($path)
            || !is_readable($path)
            || filesize($path) > self::MAX_MANIFEST_BYTES
        ){
            return null;
        }

        $manifest = json_decode((string)file_get_contents($path), true, 64);

        return is_array($manifest)
            && is_array($manifest['entries'] ?? null)
            && count($manifest['entries']) <= self::MAX_MANIFEST_ENTRIES
            ? $manifest
            : null;

    }

    private static function write_manifest($path, $manifest) {

        $contents = wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if(!is_string($contents) || strlen($contents) > self::MAX_MANIFEST_BYTES){
            return false;
        }

        return self::atomic_write($path, $contents . "\n");

    }

    private static function atomic_write($path, $contents) {

        $temporary_path = $path . '.' . wp_generate_password(12, false, false) . '.tmp';

        if(file_put_contents($temporary_path, $contents, LOCK_EX) === false){
            return false;
        }

        if(is_file($path)){
            @unlink($path);
        }

        if(!@rename($temporary_path, $path)){
            @unlink($temporary_path);
            return false;
        }

        return true;

    }

    private static function protect_directories($uploads_base, $static_base, $fragments) {

        $directories = [
            trailingslashit($uploads_base) . 'reactwp',
            trailingslashit($uploads_base) . 'reactwp/render',
            $static_base,
            $fragments,
        ];

        foreach($directories as $directory){
            wp_mkdir_p($directory);
            $index_path = $directory . '/index.php';

            if(!is_file($index_path)){
                file_put_contents($index_path, "<?php\n// Silence is golden.\n", LOCK_EX);
            }
        }

        $access_path = $static_base . '/.htaccess';

        if(!is_file($access_path)){
            file_put_contents($access_path, "Require all denied\n", LOCK_EX);
        }

        $web_config_path = $static_base . '/web.config';

        if(!is_file($web_config_path)){
            file_put_contents(
                $web_config_path,
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<configuration><system.webServer><security><authorization><remove users="*" roles="" verbs="" />'
                . '<add accessType="Deny" users="*" /></authorization></security></system.webServer></configuration>' . "\n",
                LOCK_EX
            );
        }

    }

}
