=== Things ===
Contributors: mitchoyoshitaka
Author: mitcho (Michael Yoshitaka Erlewine)
Author URI: http://mitcho.com/
Donate link: http://tinyurl.com/donatetomitcho
Tags: infrastructure, objects, object-oriented, things, post type, custom post type, CPT
Requires at least: 3.3
Tested up to: 3.4
Stable tag: 0.1

An object-oriented approach to WordPress queries and custom post types

== Description ==

An object-oriented approach to WordPress queries and custom post types. Un(der)documented beta.

So, here's the idea: anywhere where you need a loop, use `get_things()`, which will return an array-like collection of Things. `get_things()` will return Things from the main query; `get_things($query_args)` will run a new query for you. Each Thing in the collection is a magical object that will make your life better.

	foreach ( get_things() as $thing ):
		// get an attribute (object or string)
		$author = $thing->author;
		// echo an attribute with the_* methods
		$thing->the_title();
		// get another Thing
		$parent = $thing->parent;
	endforeach;

Each Thing automatically has properties and associated `the_*` methods for the standard WordPress data and taxonomies associated with that post type. Meta (custom field) properties are created when you specify `meta` in your custom post type arguments. If you want to add custom methods or properties, create a subclass of `Thing` and register that with a particular post type.

== Installation ==

...

== Frequently Asked Questions ==

...

== Changelog ==

= 0.1 =
* Initial upload

== Upgrade Notice ==
