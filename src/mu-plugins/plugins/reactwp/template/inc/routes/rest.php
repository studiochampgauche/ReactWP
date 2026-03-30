<?php

add_filter('rwp_allowed_rest_routes', function($routes){

    $routes[] = '/reactwp/v1/route';

    return $routes;

});


add_action('rest_api_init', function () {

    register_rest_route('reactwp/v1', '/route', [
        'methods'  => 'GET',
        'callback' => 'get_route',
        'permission_callback' => '__return_true',
    ]);

});


function get_route(WP_REST_Request $request){

    $path = rwp::sanitize('text_field', ['value' => $request->get_param('view')]);
    $path = '/' . trim((string) $path, '/') . '/';

    $obj = null;
    $url = site_url($path);

    $post_id = url_to_postid($url);

    if($post_id){
        $obj = get_post($post_id);
    } else {

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if(($segments[0] ?? null) === 'author' && !empty($segments[1])){
            $obj = get_user_by('slug', $segments[1]);
        }

        if(!$obj){
            foreach(get_taxonomies(['public' => true], 'names') as $taxonomy){

                $term_slug = end($segments);

                if(!$term_slug){
                    continue;
                }

                $term = get_term_by('slug', $term_slug, $taxonomy);

                if(!$term instanceof \WP_Term){
                    continue;
                }

                $term_link = get_term_link($term);

                if(is_wp_error($term_link)){
                    continue;
                }

                $term_path = wp_parse_url($term_link, PHP_URL_PATH) ?: '/';

                if(untrailingslashit($term_path) === untrailingslashit($path)){
                    $obj = $term;
                    break;
                }

            }
        }

    }

    if($obj instanceof \WP_Post){
        $id = $obj->ID;
        $type = $obj->post_type;
        $url = get_the_permalink($obj->ID);
        $pageName = rwp::field('page_title', $id) ?? get_the_title($id);
        $acfGroups = acf_get_field_groups(['post_id' => $id]);
    } elseif($obj instanceof \WP_User){
        $id = 'user_' . $obj->ID;
        $type = 'user';
        $url = get_author_posts_url($obj->ID);
        $pageName = rwp::field('page_title', $id) ?? 'Post(s) of ' . $obj->user_firstname;
        $acfGroups = acf_get_field_groups(['user_id' => $obj->ID, 'rest' => true]);
    } elseif($obj instanceof \WP_Term){
        $id = 'term_' . $obj->term_id;
        $type = 'term';
        $url = get_term_link($obj->term_id);
        $pageName = rwp::field('page_title', $id) ?? $obj->name;
        $acfGroups = acf_get_field_groups(['term_id' => $obj->ID, 'rest' => true]);
    } else {
        return new WP_REST_Response(null, 404);
    }

    $acf = [];
    $seo = [];
    $mediaGroups = '';

    if($acfGroups){

        foreach($acfGroups as $group){

            if(!$group['active'] || !$group['show_in_rest']) continue;

            $fields = acf_get_fields($group['key']);

            if(!$fields) continue;

            foreach($fields as $field){
                $acf[$field['name']] = rwp::field($field['name'], $id);
            }

        }

    }

    if(isset($acf['seo'])){
        $seo = $acf['seo'];
        unset($acf['seo']);
    }

    if(isset($acf['media_groups'])){
        $mediaGroups = $acf['media_groups'];
        unset($acf['media_groups']);
    }

    return new WP_REST_Response([
        'id' => $id,
        'type' => $type,
        'pageName' => $pageName,
        'path' => wp_parse_url($url, PHP_URL_PATH),
        'seo' => [
            ...$seo,
            'pageName' => $pageName
        ],
        'mediaGroups' => $mediaGroups ?? '',
        'data' => $acf
    ], 200);

}