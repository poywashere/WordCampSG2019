<?php
/**
 * Heroes plugin
 *
 * @since             1.0.0
 * @package           heroes_plugin
 *
 * @wordpress-plugin
 * Plugin Name:       Heroes Plugin
 * Plugin URI:        #
 * Description:       Plugin to create post type and enable a custom endpoint by extending WordPress default REST API
 * Version:           1.0.0
 * Author:            iYan Labao <iandotht.ml>
 * Author URI:        http://www.iandotht.ml
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       Heroes-plugin
 */
namespace Heroes_Plugin;
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
class Heroes {
    const PLUGIN_NAME = 'heroes-plugin';
    const POST_TYPE_SLUG = 'heroes';
    const TAXONOMY_SLUG = 'heroes-team';
    /*
    Register our Heroes CPT
    */
    public function register_post_type() {
        $args = array(
            'label' => esc_html('Hero', 'heroes-plugin'), 
            'public' => true, 
            'menu_position' => 47, 
            'menu_icon' => 'dashicons-id', '
            supports' => array('title', 'editor', 'revisions', 'thumbnail'), 
            'has_archive' => false, 
            'show_in_rest' => true, // Option to include this on WP's REST
            'publicly_queryable' => false
        );
        register_post_type(self::POST_TYPE_SLUG, $args);
    }
    /*
    Register our Team Tag for our Heroes CPT
    */
    public function register_taxonomy() {
        $args = array(
            'hierarchical' => false,
            'label' => esc_html('Team', 'heroes-plugin'), 
            'show_ui' => true, 
            'show_admin_column' => true, 
            'update_count_callback' => '_update_post_term_count', 
            'show_in_rest' => true, 
            'query_var' => true
        );
        register_taxonomy(self::TAXONOMY_SLUG, [self::POST_TYPE_SLUG], $args);
    }
    /*
    Create our custom endpoint
    */
    public function custom_endpoint_for_heroes() {
        register_rest_route(
            self::PLUGIN_NAME . '/v1', '/my-heroes(?:/(?P<team>\d+))?', 
            array(
                'methods' => 'GET', 
                'callback' => [$this, 'get_all_heroes']
            )
        );
        
    }
    /*
    Create a callback for the custom endpoint
    */
    public function get_all_heroes() {
        
        
        $heroes_args = array(
            'post_type' => self::POST_TYPE_SLUG, 
            'post_status' => 'publish', 
            'perm' => 'readable' // Some permission checks can be added here.
        );

        if( !empty($_GET['team']) ) {
            $heroes_args['tax_query'] = array(
                array (
                    'taxonomy' => 'heroes-team',
                    'field' => 'id',
                    'terms' => $_GET['team'],
                )
            );
        }
        


        $query = new \WP_Query($heroes_args);
        $response = [];
        $counter = 0;
        // The Loop
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_tags = get_the_terms($post_id, self::TAXONOMY_SLUG);

                $response[$counter]['title'] = get_the_title(); // add post_title to response

                foreach ($post_tags as $tags_key => $tags_value) {
                    $response[$counter]['tags'][] = $tags_value->name; // add selected tag/s to response
                }
                $counter++; // increment index
            }
        } else {
            $response = esc_html__('Oopps! Looks like your heroes are missing in action?', 'heroes-plugin');
        }
        /* Restore original Post Data */
        wp_reset_postdata();

        // Return only documentation name and tag name.
        return rest_ensure_response($response);
    }
}

// run functions under the class for each WP hooks
$initializedHeroes = new Heroes();
add_action('init', [$initializedHeroes, 'register_post_type']);
add_action('init', [$initializedHeroes, 'register_taxonomy']);
add_action('rest_api_init', [$initializedHeroes, 'custom_endpoint_for_heroes']);
