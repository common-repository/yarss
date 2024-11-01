<?php
/**
* Plugin Name: YARSS
* Plugin URI: http://wordpress.org/plugins/yarss/
* Description: You can add a shortcode into your posts to display RSS feeds that may include bbcode.
* Version: 1.1.1
* Author: Alexander Jungwirth
* Author URI: http://www.webdesgn-manching.de
* License: GPLv2
*/

/*  Copyright 2016 Alexander Jungwirth (email : alex.jungwirth [at] gmail [dot] com)

    THIS PROGRAM IS FREE SOFTWARE; YOU CAN REDISTRIBUTE IT AND/OR MODIFY
    IT UNDER THE TERMS OF THE GNU GENERAL PUBLIC LICENSE AS PUBLISHED BY
    THE FREE SOFTWARE FOUNDATION; EITHER VERSION 2 OF THE LICENSE, OR
    (AT YOUR OPTION) ANY LATER VERSION.

    THIS PROGRAM IS DISTRIBUTED IN THE HOPE THAT IT WILL BE USEFUL,
    BUT WITHOUT ANY WARRANTY; WITHOUT EVEN THE IMPLIED WARRANTY OF
    MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE.  SEE THE
    GNU GENERAL PUBLIC LICENSE FOR MORE DETAILS.

    YOU SHOULD HAVE RECEIVED A COPY OF THE GNU GENERAL PUBLIC LICENSE
    ALONG WITH THIS PROGRAM; IF NOT, WRITE TO THE FREE SOFTWARE
    FOUNDATION, INC., 51 FRANKLIN ST, FIFTH FLOOR, BOSTON, MA  02110-1301  USA

    Supported shortcodes: 
    - [img arc="" <attributes>]
    - [b]...[/b]
    - [i]...[/i]
    - [u]...[/u]
    - [code]...[/code]

*/

// initialize
function init_yarss() {
    // languages
    load_plugin_textdomain( 'yarss', false, dirname( plugin_basename(__FILE__) ).'/lang/' );
    // stylesheet
    wp_register_style( 'yarss-style', plugins_url('css/style.css', __FILE__) );
    wp_enqueue_style( 'yarss-style' );
}

// the shortcode for posts and pages
if ( !function_exists( 'yarss_shortcode' ) ) {
    function yarss_shortcode( $atts ) {
        include_once(ABSPATH . WPINC . '/rss.php');
        $attributes = shortcode_atts( array( 
            "url" => "/rss/rss.txt",
            "bbcode" => "false",
            "items" => 20,
            "date" => "true",
            "summary" => "true",
            "first_lines" => "false",
            "height" => ""
        ), $atts );
        $data = "";
        $rss = fetch_feed( $attributes["url"] );
        if ( is_wp_error( $rss ) ) {
            return __( "The RSS Feed is unreachable.", "yarss" );
        }
        
        $items = $rss->get_item_quantity( $attributes["items"] );
        $rss_feed = $rss->get_items( 0, $items );

        $data .= "<ul class=\"yarss\">";

        if ( $items == 0 ) {
            $data .= "<li>" . __( 'No items found.', 'yarss' ) . "</li>";
        } else {
            foreach( $rss_feed as $item ) {
                if ( $attributes["bbcode"] == "true") {
                    $description = preg_replace("/\[img(.*?)\]/", "<img $1>", $item->get_description() );
                    $description = preg_replace("/\[b\](.*?)\[\/b]/", "<strong>$1</strong>", $description );
                    $description = preg_replace("/\[i\](.*?)\\[\/i\]/", "<em>$1</em>", $description );
                    $description = preg_replace("/\[u\](.*?)\[\/u\]/", "<u>$1</u>", $description );
                    $description = preg_replace("/\[code\](.*?)\[\/code\]/", "<pre>$1</pre>", $description );
                } else {
                    $description = preg_replace("/\[img(.*?)\]/", "", $item->get_description() );
                    $description = preg_replace("/\[b\](.*?)\[\/b]/", "$1", $description );
                    $description = preg_replace("/\[i\](.*?)\\[\/i\]/", "$1", $description );
                    $description = preg_replace("/\[u\](.*?)\[\/u\]/", "$1", $description );
                    $description = preg_replace("/\[code\](.*?)\[\/code\]/", "$1", $description );
                }
                
                $data .= "<li>";
                $data .= "<a href=\"" . esc_url( $item->get_permalink() ) . "\" title=\"\" target=\"_blank\">" . esc_html( $item->get_title() ) . "</a>";
                if ($attributes["date"] == "true") $data .= "<span class=\"yarss-date\">" . sprintf( __( "%s o'clock",  "yarss" ), $item->get_date('d.m.Y H:i') )  . "</span>";
                if ($attributes["summary"] == "true") $data .= "<div class=\"yarss-summary\">" . $description . "</div>"; 
                $data .= "</li>";

            }
        }

        $data .= "</ul>";
        
        if ($attributes["height"] != "" && (int)$attributes["height"] > 0) {
            $data .= "<script>jQuery(document).ready( function() { jQuery( \".yarss_frame\" ).cycle(); jQuery( \".cycle-carousel-wrap\" ).css( \"top\", \"0\" ); });</script>";            
        }
        
        if ($attributes["first_lines"] == "true") {
            $data .= "<script>jQuery(document).ready( function() { jQuery( '.yarss-summary' ).css({ height: ( parseInt( jQuery( '.yarss-summary' ).css( 'line-height' ).split('px')[0] ) * 2).toString() + 'px', overflow: 'hidden'  }); });</script>";
        }
        
        return $data;
    }
}

function add_cycle_scripts() {
    wp_enqueue_script( 'cycle2', plugins_url( 'js/jquery.cycle2.min.js', __FILE__ ), array( 'jquery' ), '1.1' , true );
    wp_enqueue_script( 'cycle2_carousel', plugins_url( 'js/jquery.cycle2.carousel.js', __FILE__ ), array( 'jquery' ), '1.1' , true );
}


// Creating the widget 
if ( !class_exists( 'yarss_widget' ) ) {
    class yarss_widget extends WP_Widget {

        function __construct() {
            parent::__construct(
                'yarss_widget', 
                __('YARSS', 'yarss'), 
                array( 'description' => __( 'Add RSS Feeds to the Sidebar.', 'yarss' ), ) 
            );
        }

        // Creating widget front-end
        // This is where the action happens
        public function widget( $args, $instance ) {
            $title = apply_filters( 'widget_title', $instance['title'] );
            // before and after widget arguments are defined by themes
            echo $args['before_widget'];
            if ( ! empty( $title ) ) echo $args['before_title'] . $title . $args['after_title'];

            if ( trim($instance["height"]) != "" && (int)$instance["height"] > 0 ) {
                ?>
                <div class="yarss_frame" style="height: <?php echo trim($instance["height"]); ?>px" data-cycle-slides="li" data-cycle-fx="carousel" data-cycle-timeout="5000"  data-cycle-carousel-vertical="true">
                    <?php echo yarss_shortcode($instance); ?>
                </div>
                <?php                
            } else {
                echo yarss_shortcode($instance);    
            }
            
            echo $args['after_widget'];
        }

        // Widget Backend 
        public function form( $instance ) {
            $title = ( isset( $instance[ 'title' ] ) ) ? $instance[ 'title' ] : $title = __( 'New title', 'yarss' );
            $url = ( isset( $instance[ 'url' ] ) ) ? $instance[ 'url' ] : $url = __( 'URL to the RSS feed', 'yarss' );
            $items = ( isset( $instance[ 'items' ] ) ) ? $instance[ 'items' ] : $items = '10';
            $height = ( isset( $instance[ 'height' ] ) ) ? $instance[ 'height' ] : $height = '';
            ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'yarss' ); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'url' ); ?>"><?php _e( 'URL to the RSS feed:', 'yarss' ); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" type="text" value="<?php echo esc_attr( $url ); ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'items' ); ?>"><?php _e( 'Number of items:', 'yarss' ); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'items' ); ?>" name="<?php echo $this->get_field_name( 'items' ); ?>" type="number" value="<?php echo esc_attr( $items ); ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'max. widget height in px:', 'yarss' ); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="number" value="<?php echo esc_attr( $height ); ?>" /><br />
                <em><?php _e( 'leave empty for no limitation', 'yarss' ); ?></em>
            </p>
            <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance[ 'date' ], 'true' ); ?> id="<?php echo $this->get_field_id( 'date' ); ?>" name="<?php echo $this->get_field_name( 'date' ); ?>" /> 
                <label for="<?php echo $this->get_field_id( 'date' ); ?>"><?php _e( 'display the date', 'yarss' ); ?></label>
            </p>
            <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance[ 'summary' ], 'true' ); ?> id="<?php echo $this->get_field_id( 'summary' ); ?>" name="<?php echo $this->get_field_name( 'summary' ); ?>" /> 
                <label for="<?php echo $this->get_field_id( 'summary' ); ?>"><?php _e( 'display the summary', 'yarss' ); ?></label>
            </p>
            <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance[ 'first_lines' ], 'true' ); ?> id="<?php echo $this->get_field_id( 'first_lines' ); ?>" name="<?php echo $this->get_field_name( 'first_lines' ); ?>" /> 
                <label for="<?php echo $this->get_field_id( 'first_lines' ); ?>"><?php _e( 'show first 2 lines only', 'yarss' ); ?></label>
            </p>
            <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance[ 'bbcode' ], 'true' ); ?> id="<?php echo $this->get_field_id( 'bbcode' ); ?>" name="<?php echo $this->get_field_name( 'bbcode' ); ?>" /> 
                <label for="<?php echo $this->get_field_id( 'bbcode' ); ?>"><?php _e( 'enable bbcode in summary', 'yarss' ); ?></label>
            </p>
            

            <?php 
        }

        // Updating widget replacing old instances with new
        public function update( $new_instance, $old_instance ) {
            $instance = array();
            $instance[ 'title' ] = ( ! empty( $new_instance[ 'title' ] ) ) ? strip_tags( $new_instance['title'] ) : '';
            $instance[ 'url' ] = ( ! empty( $new_instance[ 'url' ] ) ) ? strip_tags( $new_instance['url'] ) : '';
            $instance[ 'items' ] = ( ! empty( $new_instance[ 'items' ] ) ) ? strip_tags( $new_instance['items'] ) : '10';
            $instance[ 'height' ] = ( ! empty( $new_instance[ 'height' ] ) ) ? strip_tags( $new_instance['height'] ) : '';
            $instance[ 'bbcode' ] = ( $new_instance[ 'bbcode' ] == "on" ) ? "true" : "false";
            $instance[ 'date' ] = ( $new_instance[ 'date' ] ) ? "true" : "false";
            $instance[ 'summary' ] = ( $new_instance[ 'summary' ] ) ? "true" : "false";
            $instance[ 'first_lines' ] = ( $new_instance[ 'first_lines' ] ) ? "true" : "false";
            return $instance;
        }

    } 
}

// Register and load the widget
if ( !function_exists( 'yarss_load_widget' ) ) {
    function yarss_load_widget() {
        load_plugin_textdomain( 'yarss', false, dirname( plugin_basename(__FILE__) ).'/lang/' );
        register_widget( 'yarss_widget' );
    }
}

add_action( 'wp_enqueue_scripts', 'init_yarss' );
add_action( 'wp_enqueue_scripts', 'add_cycle_scripts' );
add_shortcode( 'yarss', 'yarss_shortcode' );
add_action( 'widgets_init', 'yarss_load_widget' );
