<?php

    class helloChamp{

        function __construct(){
            
            if(!class_exists('rwp')) return;
            
            /*
            * str_replace your return when you use rwp::field(), ReactWP\Utils\Field::get() or ACF REST API;
            */
            add_action('acf/init', function(){

                ReactWP\Utils\Field::replace([
                    '{Y}',
                    '{SITE_NAME}'
                ], [
                    date('Y'),
                    ReactWP\SEO\SEO::site_name()
                ]);

            });
            
            
            /*
            * Set defaults when you call rwp::cpt() or ReactWP\Utils\CustomPostType::get();
            */
            ReactWP\Utils\CustomPostType::default('posts_per_page', -1);
            ReactWP\Utils\CustomPostType::default('paged', 1);


            /*
            * Set defaults when you call rwp::menu() or ReactWP\Utils\Menu::get();
            */
            ReactWP\Utils\Menu::default('container', null);
            ReactWP\Utils\Menu::default('items_wrap', '<ul>%3$s</ul>');
            
            
            /*
            * Set defaults when you call rwp::button() or ReactWP\Utils\Button::get();
            *
            * ReactWP\Utils\Button::default('text', 'x');
            * ReactWP\Utils\Button::default('href', 'x');
            * ReactWP\Utils\Button::default('class', 'x');
            * ReactWP\Utils\Button::default('attr', 'x');
            * ReactWP\Utils\Button::default('before', 'x');
            * ReactWP\Utils\Button::default('after', 'x');
            */
            


            /*
            * Enqueue Scripts
            */
            add_action('wp_enqueue_scripts', function(){


                /*
                * Routes
                */

                $data = rwp::cpt(['page', 'post'])->posts;
                $routes = [];

                if($data){

                    foreach ($data as $k => $v) {
                        
                        $acf = get_fields($v->ID);

                        $routes[] = [
                            'id' => $v->ID,
                            'routeName' => get_the_title($v->ID),
                            'path' => str_replace(WP_HOME, '', get_permalink($v->ID)),
                            'type' => $v->post_type,
                            'seo' => (isset($acf['seo']) ? $acf['seo'] : []),
                            'componentName' => (isset($acf['component_name']) && !empty($acf['component_name']) ? $acf['component_name'] : 'DefaultPage')
                        ];


                        $unset = [
                            'seo',
                            'component_name'
                        ];
                        
                        foreach($unset as $u){

                            if(isset($acf[$u])) unset($acf[$u]);

                        }

                        $routes[$k]['acf'] = $acf;


                        if($routes[$k]['type'] === 'post'){

                            $routes[$k]['componentName'] = 'SinglePostPage';
                            $routes[$k]['seo']['og_type'] = 'article';

                            $routes[$k]['extraDatas'] = [
                                'date' => $v->post_date,
                                'modified' => $v->post_modified,
                                'author' => get_author_posts_url($v->post_author)
                            ];

                        } elseif($routes[$k]['type'] === 'author'){

                            $routes[$k]['seo']['og_type'] = 'profile';

                            $routes[$k]['extraDatas'] = [
                                'username' => '',
                                'name' => [
                                    'firstname' => '',
                                    'lastname' => ''
                                ]
                            ];

                        }

                    }

                }
                wp_localize_script('rwp-main', 'ROUTES', $routes);


                /*
                * medias
                *
                * $medias = [
                *     'home' => [
                *         [
                *             'type' => 'video',
                *             'target' => '',
                *             'src' => ''
                *         ],
                *         [
                *             'type' => 'image',
                *             'target' => '',
                *             'src' => ''
                *         ],
                *         [
                *             'type' => 'audio',
                *             'src' => ''
                *         ],
                *     ],
                *     'about' => [
                *         [
                *             'type' => 'video',
                *             'src' => ''
                *         ],
                *     ],
                * ];
                */

                $medias = [];

                wp_localize_script('rwp-main', 'MEDIAS', $medias);
                
            });


            /*
            * Rest API Requests
            */
            $this->restRequests();



            /*
            * Ajax Requests
            */
            $this->ajaxRequests();

        }



        function restRequests(){


            add_action('rest_api_init', function(){

                /*
                *
                register_rest_route('rwp/v1', '/custom/', [
                    'methods'  => 'GET',
                    'callback' => function(){

                        $data = [];

                        return new WP_REST_Response($data, 200);

                    },
                ]);
                */


            });

        }


        function ajaxRequests(){

            

        }

    }

    new helloChamp();
?>