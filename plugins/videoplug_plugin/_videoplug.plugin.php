<?php
/**
 * This file implements the Video Plug plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces Video Plug markup in HTML (not XML).
 *
 * @todo dh> Hook into AdminBeforeItemEditUpdate and validate provided video IDs
 *
 * @package plugins
 */
class videoplug_plugin extends Plugin
{
	var $code = 'evo_videoplug';
	var $name = 'Video Plug';
	var $priority = 65;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '5.0.0';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Video plug for a few popular video sites.');
		$this->long_desc = T_('This is a basic video plug pluigin. Use it by entering [video:youtube:123xyz] or [video:dailymotion:123xyz] into your post, where 123xyz is the ID of the video.');
	}


	/**
	 * Perform rendering
	 *
	 * @todo add more video sites, anyone...
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// fp> removed some embeds to make it xhtml compliant, using only object. (Hari style ;)
		// anyone, feel free to clean up the ones that have no object tag at all.

		$search_list = array(
				'#\[video:youtube:(.+?)]#',     // Youtube
				'#\[video:dailymotion:(.+?)]#', // Dailymotion
				'#\[video:vimeo:(.+?)]#',       // vimeo // blueyed> TODO: might want to use oEmbed (to get title etc separately and display it below video): http://vimeo.com/api/docs/oembed
				// Unavailable services. Keep them for backwards compatibility
				'#\[video:google:(.+?)]#',      // Google video
				'#\[video:livevideo:(.+?)]#',   // LiveVideo
				'#\[video:ifilm:(.+?)]#',       // iFilm

			);
		$replace_list = array(
				'<div class="videoblock"><iframe id="ytplayer" type="text/html" width="425" height="350" src="//www.youtube.com/embed/\\1" allowfullscreen="allowfullscreen" frameborder="0"></iframe></div>',
				'<div class="videoblock"><iframe src="//www.dailymotion.com/embed/video/\\1" width="425" height="335" frameborder="0" allowfullscreen></iframe></div>',
				'<div class="videoblock"><iframe src="//player.vimeo.com/video/$1" width="400" height="225" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>',
				// Unavailable services. Keep them for backwards compatibility
				'<div class="videoblock">The Google video service is not available anymore.</div>',
				'<div class="videoblock">The Live Video service is not available anymore.</div>',
				'<div class="videoblock">The iFilm video service is not available anymore.</div>',
			);

		$content = replace_content_outcode( $search_list, $replace_list, $content );

		return true;
	}



	/**
	 * Perform rendering for XML feeds
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function RenderItemAsXml( & $params )
	{
		return $this->RenderItemAsHtml( $params );
	}

	/**
	 * Display a toolbar in admin.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( !empty( $params['Item'] ) )
		{	// Item is set, get Blog from post
			$edited_Item = & $params['Item'];
			$Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$coll_setting_name = ( $params['target_type'] == 'Comment' ) ? 'coll_apply_comment_rendering' : 'coll_apply_rendering';
		$apply_rendering = $this->get_coll_setting( $coll_setting_name, $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' || $params['edit_layout'] == 'inskin' )
		{ // This is too complex for inskin mode, don't display it:
			return false;
		}

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );

		echo $this->get_template( 'toolbar_title_before' ).T_('Video').': '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" id="video_youtube" title="'.T_('Insert Youtube video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|youtube" value="YouTube" />';
		echo '<input type="button" id="video_vimeo" title="'.T_('Insert vimeo video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|vimeo" value="Vimeo" />';
		echo '<input type="button" id="video_dailymotion" title="'.T_('Insert DailyMotion video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|dailymotion" value="DailyMotion" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
			//<![CDATA[
			function videotag( tag )
			{
				while( 1 )
				{
					var p = '<?php echo TS_('Copy/paste the URL or the ID of your video from %s:') ?>';
					var video_ID = prompt( p.replace( /%s/, tag ), '' );
					if( ! video_ID )
					{
						return;
					}

					// Validate Video ID or URL:
					var regexp_ID = false;
					var regexp_URL = false;
					switch( tag )
					{
						case 'youtube':
							// Allow HD video code with ?hd=1 at the end
							regexp_ID = /^[a-z0-9_?=-]+$/i;
							regexp_URL = /^(.+\?v=)?([a-z0-9_?=-]+)$/i;
							break;

						case 'dailymotion':
							regexp_ID = /^[a-z0-9]+$/i;
							regexp_URL = /^(.+\/video\/)?([a-z0-9]+)(_[a-z0-9_-]+)?$/i;
							break;

						case 'vimeo':
							regexp_ID = /^\d+$/;
							regexp_URL = /^(.+\/)?(\d+)$/;
							break;

						default:
							// Don't allow unknown video:
							break;
					}

					if( regexp_ID && regexp_URL )
					{	// Check the entered data by regexp:
						if( video_ID.match( regexp_ID ) )
						{	// Valid video ID
							break;
						}
						else if( video_ID.match( /^https?:\/\// ) )
						{	// If this is URL, Check to correct format:
							if( video_ID.match( regexp_URL ) )
							{	// Valid video URL
								// Extract ID from URL:
								video_ID = video_ID.replace( regexp_URL, '$2' );
								break;
							}
							else
							{	// Display error when URL doesn't match:
								alert( '<?php echo TS_('The URL you provided could not be recognized.'); ?>' );
								continue;
							}
						}
					}

					// Display error of wrong entered data:
					alert( '<?php echo TS_('The URL or video ID is invalid.'); ?>' );
				}

				tag = '[video:'+tag+':'+video_ID+']';

				textarea_wrap_selection( b2evoCanvas, tag, '', 1 );
			}
			//]]>
		</script><?php

		return true;
	}
}

?>