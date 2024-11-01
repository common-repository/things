<?php
/*
Plugin Name: Things
Description: Custom post types, the object-oriented way
Version: 0.1
Author: mitcho (Michael Yoshitaka Erlewine)
Author URI: http://mitcho.com/
Donate link: http://tinyurl.com/donatetomitcho
*/

class Thing {
	public $name = 'Thing';
	protected $post;
	
	// @todo handle comment_status, ping_status, mime_type, comment_count ?
	protected $properties = array('author', 'date', 'date_gmt', 'content', 'title', 'excerpt', 'status', 'name', 'modified', 'modified_gmt', 'content_filtered', 'type');
	protected $taxonomies = array();
	protected $meta = array();
	
	function __construct( $post ) {
		// @todo check to make sure it's a post object
		$this->post = $post;

		$post_type_obj = get_post_type_object($this->post->post_type);
		if ( isset($post_type_obj->meta) )
			$this->meta = $post_type_obj->meta;

		$this->taxonomies = get_object_taxonomies($this->post->post_type);
	}
	
	function __toString() {
		return "[{$this->name} {$this->post->post_type} {$this->post->ID}]";
	}
	
	function __get( $property ) {
		if ( 'ID' == $property ) {
			return $post->ID;
		} else if ( method_exists( $this, "get_$property" ) ) {
			return call_user_func( array( $this, "get_$property" ) );
		} else if ( in_array($property, $this->properties) ) {
			return $this->post->{"post_$property"};
		} else if ( in_array($property, $this->taxonomies) ) {
			$taxonomy = get_taxonomy($property);
			$terms = wp_get_object_terms( $this->post->ID, $property );
			// @todo wrap in Thing_Term(s) wrapper?
			if ( isset($taxonomy->single_value) && $taxonomy->single_value )
				return $terms[0];
			return $terms;
		}

        return new WP_Error('things_missing_property', sprintf("The property %s doesn't exist in %s.", $property, $this->post->post_type));
	}
	
	// @todo override in WritableThing or somesuch?
	function __set( $property, $value ) {
	}
	
	function __call( $method, $arguments ) {
		if ( substr($method, 0, 4) == 'get_' ) {
			return $this->{substr($method, 4)};
		} else if ( substr($method, 0, 4) == 'the_' ) {
			if ( method_exists( $this, $method ) )
				return call_user_func_array( array( $this, $method ), $arguments );

			$value = $this->{substr($method, 4)};
			if ( is_wp_error($value) )
				return $value; // pass back error
			// @filter <post_type>_the_<property>
			echo apply_filters( $this->post->post_type . '_' . $method, $value );
		}
		
        return new WP_Error('things_missing_method', sprintf("The method %s doesn't exist in %s.", $method, $this->post->post_type));
	}
	
	protected function get_parent() {
		if ( 0 == $this->post->post_parent )
			return null;
		else
			return Things::thingify( $this->post->post_parent );
	}
	
	protected function get_author() {
		// @todo return a better wrapper for the user
		return new WP_User($this->post->post_author);
	}
	
	// Just an alias for the slug. Allows "name" to be overloaded for other purposes.
	protected function get_slug() {
		return $this->post->post_name;
	}
}

final class Things extends ArrayIterator {
	/**
	 * Things singleton
	 */
	static $version = 0.1;
	static $custom_thing_types = array();
	static public function register_type( $post_type, $thing_class ) {
		// @todo error
		if ( is_null( get_post_type_object($post_type) ) )
			return new WP_Error();

		// @todo error
		if ( !is_subclass_of( $thing_class, 'Thing' ) )
			return new WP_Error();
		
		// @todo check for collisions
		static::$custom_thing_types[$post_type] = $thing_class;
	}
	static public function thingify( $post ) {
		// @todo check for post object or int
		if ( is_int($post) )
			$post = get_post( $post );
		if ( isset( static::$custom_thing_types[$post->post_type] ) )
			return new static::$custom_thing_types[$post->post_type]($post);
		return new Thing($post);
	}
	static public function registered_post_type( $post_type, $args ) {
		if ( isset( $args->thing_class ) )
			self::register_type( $post_type, $args->thing_class );
	}

	/**
	 * Things collection class
	 */
	private $query;
	private $things = array();
	
	public function __construct( $query = '' ) {
		if ( !empty( $query ) ) {
			$this->query = new WP_Query( $query );
		} else {
			global $wp_the_query;
			// @todo check if $wp_the_query is set
			$this->query = $wp_the_query;
		}
		
		foreach ( $this->query->posts as $key => $post ) {
			$this->things[$key] = Things::thingify($post);
		}
		
		// construct ArrayIterator
		parent::__construct( &$this->things );
	}
}
add_action( 'registered_post_type', array('Things', 'registered_post_type'), 10, 2 );

function get_things( $query = '' ) {
	return new Things( $query );
}

