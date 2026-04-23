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

    $path = \ReactWP\Runtime\RouteResolver::normalize_path(
        rwp::sanitize('text_field', ['value' => $request->get_param('view')])
    );
    $payload = \ReactWP\Runtime\RouteResolver::from_path($path);
    $status = !empty($payload['is404']) ? 404 : 200;

    return new WP_REST_Response($payload, $status);

}
