<?php

/**
 * A generic Widget class.
**/
class AlephWidget {
	var $name;
	var $valid_content;
	var $class_name;
	var $wrap_start;
	var $wrap_end;
	var $display_title;

	function AlephWidget( $name ) {
		$this->name = $name;
		$this->class_name = get_class(&$this);
		$this->wrap_start = '';
		$this->wrap_end   = '';
		$this->display_title = true;
		register_sidebar_widget( $this->name, array(&$this, 'display'));
	}

	function display( $args ) {
		$this->configure();
		if ( ! $this->validContent() ) {
			return;
		}
		extract($args);

		echo $this->wrap_start, $before_widget;
		if ( $this->display_title )
			echo $before_title . $this->name . $after_title;

		$this->displayContent();
		echo $after_widget, $this->wrap_end;
	}

	function setDisplayTitle( $bool ) {
		$this->display_title = $bool;
	}
	function setTitle( $title ) {
		$this->name = $title;
	}
	function setWrapping( $start, $end ) {
		$this->wrap_start = $start;
		$this->wrap_end = $end;
	}

	function validContent() {
		return $this->valid_content;
	}

	function displayContent() {
		// overwrite this method
	}
	function configure() {
		// overwrite this method to set internal vars
	}
}

/**
 *	RACE Subclasses
**/
class RACE_Widget  	extends AlephWidget {

	function RACE_Widget( $name, $valid_pattern ) {
		$this->AlephWidget( $name );
		$this->display_title = false;
		$this->valid_pattern = $valid_pattern;
	}
	function configure() {
		$this->configure_instance();
	}
	function displayContent() {
		$this->display_instance();
	}

	function validate( $page ) {
		$this->valid_content = preg_match( $this->valid_pattern, $page->slug );
	}
	function set_args( $args ) {
		$this->instance_args = $args;
	}
	function set_user( $user ) {
		if ( ! is_object( $user ) ) {
			$user = get_userdatabylogin( $user );
		}
		$this->user_ID = intval($user->ID);
		$this->user = $user;
		$this->profile = $user->race_profile;
	}
}

class RACE_ProfileMenu  	extends RACE_Widget {

	function RACE_ProfileMenu( $name ) {
		$pattern = "/^author-(list|profile)$/";
		$this->RACE_Widget( $name, $pattern );
	}

	function configure_instance() {
		$is_profile = is_profile();
		if ( is_user_list() || $is_profile ) {
			global $post;
			$this->validate( $post );

			if ( $this->valid_content && $is_profile ) {
				global $user;
				$this->set_user( $user );
			}

			if ( $this->valid_content ) {
				$this->set_args( array(
					'path' => $is_profile ? 'donations/online' : 'warriors/current',
					'key'  => $is_profile ? 'ID'               : 'post_parent'
				) );
				// add filter for customization
				add_filter('wp_list_pages', array( &$this, 'filter_menu' ), 9);
			}
		}
	}

	function build_menu( $echo = true ) {
		if ( empty( $this->instance_args ) ) {
			return '';
		}
		extract( $this->instance_args );
		$child = array_shift( query_posts( "pagename=$path" ) );
		$menu  = race_build_submenu( (int) $child->{$key} );

		if ( $echo ) echo $menu; else return $menu;
	}

	function filter_menu( $menu ) {
		if ( $this->user_ID ) {
			// attach userid queryvar to donate link
			$re = '/(donations\/online\/warrior\/)/';
			$qv = $this->user->display_name;
			$menu = preg_replace( $re, "$1?runner=$qv", $menu, 1 );
		}
		return $menu;
	}

	function display_instance() {
		$this->build_menu();
	}
}

class RACE_ProgressMeter 	extends RACE_Widget {

	function RACE_ProgressMeter( $name ) {
		$pattern = "/^(author-profile)$/";
		$this->RACE_Widget( $name, $pattern );
	}

	function configure_instance() {
		global $post, $user;
		$this->validate( $post );

		if ( array_key_exists( 'runner', $_GET ) ) {
			$user = $_GET['runner'];
			$this->valid_content = true;
		}

		if ( $this->valid_content ) {
			global $user;
			$this->set_user( $user );

			if ( $this->user ) {
				$progress = race_sum_donations( $this->user );
				$this->set_args( array(
					'progress' => $progress,
					'goal' => $this->profile['goal'],
					'ticks' => array(
						100, 50, 75, 25
					)
				) );
			}
		}
	}

	function build_meter( $echo = true ) {
		// http://meyerweb.com/eric/css/edge/bargraph/demo.html
		/*
			<ul id="progress">
				<li class="goal">$GOAL
					<ul>
						<li class="bar" style="height: 110px;"><p>$CURRENT_PROGRESS</p></li>
					</ul>
				</li>
				<li id="ticks">
					<div class="tick" style="height: 59px;"><p>$50,000</p></div>
					<div class="tick" style="height: 59px;"><p>$40,000</p></div>
					<div class="tick" style="height: 59px;"><p>$30,000</p></div>
					<div class="tick" style="height: 59px;"><p>$20,000</p></div>
					<div class="tick" style="height: 59px;"><p>$10,000</p></div>
				</li>
			</ul>
		*/
		if ( empty( $this->instance_args ) ) {
			return '';
		}
		extract( $this->instance_args );
		$goal = (int) $goal;
		$progress = (int) $progress;
		$complete = ( $goal && $progress ) ? 100 * round( ( $progress / $goal ), 2) : 0;
		$class = '';
		if ( $complete > 100 ) {
			$complete = 100;
			$class = ' class="complete"';
		}

		$meter = array(
			"<ul id=\"progress\"$class>",
			"\t<li class=\"goal\"><p>\$$goal</p>",
			"\t\t<ul>",
			"\t\t\t<li class=\"bar\" style=\"height:$complete%;\"><p>\$$progress</p></li>",
			"\t\t</ul>",
			"\t</li>",
			"\t<li id=\"ticks\">",
			"\t</li>",
			"</ul>"
		);

		$interval = 100 / count( $ticks );
		rsort( $ticks );
		foreach ( $ticks as $tick ) {
			array_splice( $meter, -2, 0,
			"\t\t<div class=\"tick\" style=\"height:$interval%;\"><p>$tick<span>%</span></p></div>"
			);
		}

		$glue  = "\n" . str_repeat( "\t", 4 );
		$meter = implode( $glue, $meter );

		if ( $echo ) echo $glue . $meter; else return $meter;
	}

	function display_instance() {
		$this->build_meter();
	}
}

?>