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

    $view = $request->get_param('view');

    if(!is_string($view) || trim($view) === ''){
        return new WP_Error(
            'reactwp_route_missing_view',
            __('The route endpoint requires a view parameter.', 'reactwp'),
            ['status' => 400]
        );
    }

    $payload = \ReactWP\Runtime\RouteResolver::from_path(
        wp_unslash($view)
    );
    $status = !empty($payload['is404']) ? 404 : 200;

    return new WP_REST_Response(
        \ReactWP\Runtime\PublicPayload::route($payload),
        $status
    );

}
