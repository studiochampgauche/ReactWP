<?php

	class helloChamp{

		function __construct(){
            
			if(!class_exists('scg')) return;
			
            /*
            * str_replace your return when you use scg::field(), StudioChampGauche\Utils\Field::get() or ACF REST API;
            *
            * StudioChampGauche\Utils\Field::replace(['{MAIN_EMAIL}'], [scg::field('contact_email_main')]);
			*
			* You need to use ::replace Method in acf/init hook if you play with acf Field
            */
            
            
            /*
            * Set defaults when you call scg::cpt() or StudioChampGauche\Utils\CustomPostType::get();
            */
            StudioChampGauche\Utils\CustomPostType::default('posts_per_page', -1);
            StudioChampGauche\Utils\CustomPostType::default('paged', 1);
			


            /*
            * Shot events on template_redirect
            */
            add_action('template_redirect', function(){

            	if(is_admin()) return;

            	wp_redirect(admin_url());

            	exit;

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


            add_filter( 'acf/settings/rest_api_format', function () {
                return 'standard';
            });



            add_filter('acf/rest/format_value_for_rest', function ($value_formatted, $post_id, $field, $value, $format){

                $return = $value_formatted;

                if($return && is_array($return) && \StudioChampGauche\Utils\Field::$elementsToReplace)
                    \StudioChampGauche\Utils\Field::recursive(\StudioChampGauche\Utils\Field::$elementsToReplace[0], \StudioChampGauche\Utils\Field::$elementsToReplace[1], $return);
                elseif($return && is_string($return) && \StudioChampGauche\Utils\Field::$elementsToReplace)
                    $return = str_replace(\StudioChampGauche\Utils\Field::$elementsToReplace[0], \StudioChampGauche\Utils\Field::$elementsToReplace[1], $return);


                return $return;
                
            }, 10, 5);



            add_action('rest_api_init', function(){

                /*
                * Get Medias
                */
                register_rest_route('scg/v1', '/medias/', [
                    'methods'  => 'GET',
                    'callback' => function(){


                        /*
                        *    $data = [
                        *        'home' => [
                        *            [
                        *                'type' => 'video',
                        *                'target' => '',
                        *                'src' => ''
                        *            ],
                        *            [
                        *                'type' => 'image',
                        *                'target' => '',
                        *                'src' => ''
                        *            ],
                        *            [
                        *                'type' => 'audio',
                        *                'target' => '',
                        *                'src' => ''
                        *            ],
                        *        ],
                        *        'about' => [
                        *            [
                        *                'type' => 'video',
                        *                'target' => '',
                        *                'src' => ''
                        *            ],
                        *        ],
                        *    ];
                        */

                        $data = [
                            'home' => [
                                [
                                    'type' => 'video',
                                    'src' => scg::field('audio_file', 8),
                                    'target' => '#myaudio'
                                ]
                            ]
                        ];

                        return new WP_REST_Response($data, 200);

                    },
                ]);


            });

        }


        function ajaxRequests(){

            

        }

	}

	new helloChamp();
?>