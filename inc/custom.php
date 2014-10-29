<?php
/**
 * Custom functions
 */

//define resource types
define('RESOURCE_TYPES', serialize(array(
  'case_study'=>'Case Study',
  'white_paper'=>'White Paper',
  'infographic'=>'Infographic',
  'webinar'=>'Webinar',
  'video'=>'Video',
  'brief'=>'Brief',
  'html'=>'Web Page',
  'resource'=>'Resource'
)));

$resource_types = unserialize(RESOURCE_TYPES);


// search both posts and pages with default wordpress search
function mboy_search_filter( $query ) {
    if ( $query->is_search ) {
        $query->set( 'post_type', array('post','page','resource') );
    }
    return $query;
}
add_filter('pre_get_posts','mboy_search_filter');

//fix annoying shortcode/wpautop problems
add_filter('the_content', 'shortcode_empty_paragraph_fix');
function shortcode_empty_paragraph_fix($content) {
	$array = array (
		'<p>[' => '[',
		']</p>' => ']',
		']<br />' => ']'
	);

	$content = strtr($content, $array);
	return $content;
}

//columns
// column group/row: [col-group] [/col-group], no atts
function mb_colgroup_func( $atts, $content = null ) {
	extract( shortcode_atts( array(
	), $atts ) );

  return '<div class="nested row">'. do_shortcode($content) .'</div>';
}
add_shortcode( 'col-group', 'mb_colgroup_func' );

// half columns: [half] [/half]
function mb_halfcol_func( $atts, $content = null ) {
  extract( shortcode_atts( array(
  ), $atts ) );

  return '<div class="half section">'. do_shortcode($content) .'</div>';
}
add_shortcode( 'half', 'mb_halfcol_func' );

// third columns: [third] [/third]
function mb_thirdcol_func( $atts, $content = null ) {
  extract( shortcode_atts( array(
  ), $atts ) );

  return '<div class="third section">'. do_shortcode($content) .'</div>';
}
add_shortcode( 'third', 'mb_thirdcol_func' );

// two-thirds columns: [two-thirds] [/two-thirds]
function mb_twothirdscol_func( $atts, $content = null ) {
  extract( shortcode_atts( array(
  ), $atts ) );

  return '<div class="two-thirds section">'. do_shortcode($content) .'</div>';
}
add_shortcode( 'two-thirds', 'mb_twothirdscol_func' );

// callout shortchode: [callout title="Topic/Title" color="Optional override! orange, blue, or grey"]<img> + content [/callout]
function mb_callout_func( $atts, $content = null ) {
  extract( shortcode_atts( array(
    'title' => '',
    'color' => ''
  ), $atts ) );

  //catch other spelling of grey
  if($color == 'gray') $color = 'grey';

  //build output
  $output = '</div>'; //close the parent content row
  $output .= '<div class="callout-section ' . $color .'">'; //open callout div
  // add title block if title is specified
  if($title != "") {
    $output .= '<div class="callout-title"><h2 class="content row">' . $title .'</h2></div>'; //title
  }
  $output .= '<div class="content row callout-content">' . do_shortcode($content) . '</div>'; //content section
  $output .= '</div><div class="row content">'; //close callout div, re-open content div

  $count++;
  return $output;
}
add_shortcode( 'callout', 'mb_callout_func' );

// button shortchode: [button title="Click Me!" link="www.google.com"]
function mb_button_func( $atts ) {
  extract( shortcode_atts( array(
    'title' => 'Click me!',
    'link' => ''
  ), $atts ) );

  return '<a href="'. $link .'" class="button"><span>'. $title .'</span></a>';
}
add_shortcode( 'button', 'mb_button_func' );

//hook shortcodes into tinymce editor
add_action('init', 'add_mb_buttons');
function add_mb_buttons() {
   if ( current_user_can('edit_posts') &&  current_user_can('edit_pages') )
   {
     add_filter('mce_external_plugins', 'mb_shortcode_plugin');
     add_filter('mce_buttons', 'mb_shortcode_buttons');
   }
}
function mb_shortcode_buttons($buttons) {
   array_push($buttons, "half", "third", "two-thirds", "callout", "custom-button");
   return $buttons;
}
function mb_shortcode_plugin($plugin_array) {
   $plugin_array['mboy'] = get_bloginfo('template_url').'/assets/admin/admincodes.js';
   return $plugin_array;
}



/**
	* Metabox code
	*/

add_action( 'admin_init', 'mb_register_meta_boxes' );

$prefix = 'mb_meta_';
global $meta_boxes;
$meta_boxes = array();

//subtitle metabox:
$meta_boxes[] = array(
	'id' => 'subtitle',
  'title' => 'Page Subtitle',
  'pages' => array( 'page' ),
  'fields' => array(
		array(
			'name' => 'Subtitle',
      'desc' => 'A short subtitle to appear in the header below the page title',
      'id' => $prefix . 'subtitle',
      'type' => 'textarea'
    ),
  )
);

//home page metabox:
//get options for the featured resource select box
$resource_array = array();
$resources = new WP_Query(array(
  'post_type' => 'resource',
  'posts_per_page' => 99
));
if( $resources->have_posts() ) {
  while( $resources->have_posts() ) {
    $resources->the_post();
    $resource_array[get_the_ID()] = get_the_title();
  }
}
$meta_boxes[] = array(
  'id' => 'homepage',
  'title' => 'Home Page Options',
  'pages' => array( 'page' ),
  'fields' => array(
    array(
      'type' => 'heading',
      'name' => 'Featured Resource',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Featured Resource',
      'desc' => 'Choose a resource to feature on the home page',
      'id' => $prefix . 'home_resource',
      'type' => 'select',
      'options'  => $resource_array,
      'multiple'    => false,
      'placeholder' => 'Select a resource',
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    array(
      'type' => 'heading',
      'name' => 'Latest News Item',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'News Title',
      'desc' => 'title of news item',
      'id' => $prefix . 'home_news_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Link',
      'desc' => 'where this news item should link to',
      'id' => $prefix . 'home_news_href',
      'type' => 'text'
    ),
    array(
      'name' => 'Image',
      'desc' => 'Featured image, appears below news title',
      'id' => $prefix . 'home_news_image',
      'type' => 'image_advanced',
      'max_file_uploads' => 1,
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    //Sponsor Logos
    array(
      'type' => 'heading',
      'name' => 'Sponsors',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Sponsor Logos',
      'desc' => 'Upload logos of sponsors/affiliates to appear at the bottom of the home page',
      'id' => $prefix . 'home_logos',
      'type' => 'image_advanced',
      'max_file_uploads' => 10,
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    // Home page footer callout
    array(
      'type' => 'heading',
      'name' => 'Home Callout Above Footer',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Callout Text',
      'desc' => 'A short call to action',
      'id' => $prefix . 'home_footer_cta',
      'type' => 'textarea'
    ),
  ),
  'only_on' => array(
    'template' => array( 'page-home.php' )
  )
);

//about overview metabox:
$meta_boxes[] = array(
  'id' => 'about_overview',
  'title' => 'About Page Options',
  'pages' => array( 'page' ),
  'fields' => array(
    //Our Team callout
    array(
      'type' => 'heading',
      'name' => 'Our Team Callout section',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Title',
      'desc' => 'e.g. "Our Team"',
      'id' => $prefix . 'about_team_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Content',
      'desc' => 'A short paragraph or list',
      'id' => $prefix . 'about_team_text',
      'type' => 'wysiwyg',
      'raw'  => true,
      'options' => array(
        'textarea_rows' => 6,
        'teeny'         => true,
        'media_buttons' => false,
      ),
    ),
    array(
      'name' => 'Featured images',
      'desc' => 'Upload three featured images for the team callout section. Will be cropped to a circle.',
      'id' => $prefix . 'about_team_images',
      'type' => 'image_advanced',
      'max_file_uploads' => 3,
    ),
    array(
      'name' => 'Button text',
      'desc' => 'e.g. "Meet Our Leadership Team"',
      'id' => $prefix . 'about_team_link',
      'type' => 'text'
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    //Join Our Team Content
    array(
      'type' => 'heading',
      'name' => 'Join Our Team section',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Title',
      'desc' => 'e.g. "Join Our Team"',
      'id' => $prefix . 'about_join_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Content',
      'desc' => 'One or two paragraphs of text',
      'id' => $prefix . 'about_join_text',
      'type' => 'wysiwyg',
      'raw'  => true,
      'options' => array(
        'textarea_rows' => 6,
        'teeny'         => true,
        'media_buttons' => false,
      ),
    ),
    array(
      'name' => 'Button text',
      'desc' => 'e.g. "See Current Openings"',
      'id' => $prefix . 'about_join_link',
      'type' => 'text'
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    //Adometry Stats Content
    array(
      'type' => 'heading',
      'name' => 'Adometry Statistics section',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Title',
      'desc' => 'e.g. "Adometry by the numbers"',
      'id' => $prefix . 'about_stats_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Statistic 1',
      'desc' => 'e.g. "3 offices worldwide"',
      'id' => $prefix . 'about_stats1',
      'type' => 'text'
    ),
    array(
      'name' => 'Statistic 2',
      'desc' => '',
      'id' => $prefix . 'about_stats2',
      'type' => 'text'
    ),
    array(
      'name' => 'Statistic 3',
      'desc' => '',
      'id' => $prefix . 'about_stats3',
      'type' => 'text'
    ),
    array(
      'name' => 'Statistic 4',
      'desc' => '',
      'id' => $prefix . 'about_stats4',
      'type' => 'text'
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    //Adometry Stats Content
    array(
      'type' => 'heading',
      'name' => 'News and Events section',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Title',
      'desc' => 'e.g. "Adometry news and events"',
      'id' => $prefix . 'about_news_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Content',
      'desc' => 'About a paragraph of text',
      'id' => $prefix . 'about_news_text',
      'type' => 'wysiwyg',
      'raw'  => true,
      'options' => array(
        'textarea_rows' => 6,
        'teeny'         => true,
        'media_buttons' => false,
      ),
    ),
    array(
      'name' => 'Button text',
      'desc' => 'e.g. "Visit our newsroom"',
      'id' => $prefix . 'about_news_link',
      'type' => 'text'
    ),
    array(
      'name' => 'Sponsor Logos',
      'desc' => 'Upload logos (around 4-5) of sponsors/affiliates',
      'id' => $prefix . 'about_logos',
      'type' => 'image_advanced',
      'max_file_uploads' => 5,
    ),
  ),
  'only_on' => array(
    'template' => array( 'page-about.php' )
  )
);

//careers page metabox:
$meta_boxes[] = array(
  'id' => 'careers_page',
  'title' => 'Benefits Section',
  'pages' => array( 'page' ),
  'fields' => array(
    array(
      'name' => 'Benefits Section Title',
      'desc' => 'e.g. "Benefits and Perks"',
      'id' => $prefix . 'career_benefits_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Intro paragraph',
      'desc' => 'A short (about one paragraph) blurb to go above the list of benefits',
      'id' => $prefix . 'career_benefits_text',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Image',
      'desc' => 'Appears to the right of the intro paragraph',
      'id' => $prefix . 'career_benefits_image',
      'type' => 'image_advanced',
      'max_file_uploads' => 1,
    ),
    array(
      'name' => 'List of benefits',
      'desc' => 'Use H5\'s for the titles, and the plus sign to add more',
      'id' => $prefix . 'career_benefits_list',
      'type' => 'wysiwyg',
      'raw'  => true,
      'options' => array(
        'textarea_rows' => 6,
        'teeny'         => true,
        'media_buttons' => true,
      ),
      'clone' => true
    ),
  ),
  'only_on' => array(
    'template' => array( 'page-careers.php' )
  )
);

//home page slide metabox:
$meta_boxes[] = array(
  'id' => 'slide_vars',
  'title' => 'Slide subtitle and display options',
  'pages' => array( 'slide' ),
  'fields' => array(
    array(
      'name' => 'Slide Subtitle',
      'desc' => 'A short (less than 70 characters) subheading below the slide title',
      'id' => $prefix . 'slide_sub',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Slide Link',
      'desc' => 'URL this slide should link to (will not link if left blank)',
      'id' => $prefix . 'slide_href',
      'type' => 'text'
    ),
    array(
      'name' => 'Featured on home page?',
      'desc' => '',
      'id' => $prefix . 'slide_featured',
      'type' => 'checkbox',
      'std'  => 1
    ),
  ),
  'validation' => array(
    'rules' => array(
      $prefix . 'slide_sub' => array(
        'maxlength' => 70,
      ),
    ),
    // optional override of default jquery.validate messages
    'messages' => array(
      $prefix . 'slide_sub' => array(
        'maxlength' => 'Subtitle must be under 70 characters',
      ),
    )
  )
);

//leadership metabox:
$meta_boxes[] = array(
  'id' => 'leadership',
  'title' => 'Job Title',
  'pages' => array( 'leadership' ),
  'fields' => array(
    array(
      'name' => 'Official job title',
      'desc' => '',
      'id' => $prefix . 'leader_job',
      'type' => 'textarea'
    ),
  )
);

//contact page metabox:
$meta_boxes[] = array(
  'id' => 'contact',
  'title' => 'Contact Form',
  'pages' => array( 'page' ),
  'fields' => array(
    /*array(
      'name' => 'Contact section title',
      'desc' => 'e.g. "Contact Us"',
      'id' => $prefix . 'contact_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Contact form ID',
      'desc' => 'You can find this in the Forms section, under the column "ID" for the form of your choice',
      'id' => $prefix . 'contact_form',
      'type' => 'text'
    ),*/
    array(
      'name' => 'Contact Form embed code',
      'desc' => 'Pardot iframe for the contact form',
      'id' => $prefix . 'contact_embed',
      'type' => 'textarea'
    ),
  ),
  'only_on' => array(
    'template' => array( 'page-contact.php' )
  )
);

//offices/locations meta box:
$meta_boxes[] = array(
  'id' => 'offices',
  'title' => 'Address and Directions',
  'pages' => array( 'office' ),
  'fields' => array(
    array(
      'name' => 'Address',
      'desc' => 'Street address of office and other contact information, if applicable',
      'id' => $prefix . 'office_address',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Map embed code',
      'desc' => 'Paste in embed code from Google Maps',
      'id' => $prefix . 'office_map',
      'type' => 'textarea'
    ),
  )
);

//resource metabox:
$meta_boxes[] = array(
  'id' => 'resource',
  'title' => 'Resource Options',
  'pages' => array( 'resource' ),
  'fields' => array(
    array(
      'name' => 'Resource type',
      'desc' => 'For sorting resources, and for the resource thumbnail',
      'id' => $prefix . 'resource_type',
      'type' => 'select',
      'options'  => $resource_types,
      'multiple'    => false,
      'placeholder' => 'Select a resource type',
    ),
    array(
      'name' => 'Resource Landing Page Title',
      'desc' => 'This displays on the Resource Overview page (full title on the landing page). Max 42 characters.',
      'id' => $prefix . 'resource_loop_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Resource Summary',
      'desc' => 'This displays on the Resource Overview page (full content on the landing page). Max 130 characters.',
      'id' => $prefix . 'resource_summary',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Downloadable Attachment',
      'id'   => $prefix . 'resource_file',
      'type' => 'file_advanced',
      'max_file_uploads' => 1,
    ),
    array(
      'name' => 'iFrame code (alternative to the downloadable attachment)',
      'desc' => 'if you upload an attachment, this will not show.',
      'id' => $prefix . 'resource_iframe',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Pardot form (optional)',
      'desc' => 'if applicable, paste the embed code for the Pardot form here',
      'id' => $prefix . 'resource_form',
      'type' => 'textarea'
    ),
  ),
  'validation' => array(
    'rules' => array(
      $prefix . 'resource_loop_title' => array(
        'maxlength' => 42,
      ),
      $prefix . 'resource_summary' => array(
        'maxlength' => 130,
      ),
      $prefix . 'resource_type' => array(
        'required' => true,
      ),
    ),
    'messages' => array(
      $prefix . 'resource_loop_title' => array(
        'maxlength' => __( 'Title must be less than 42 characters', 'mboy' ),
      ),
      $prefix . 'resource_summary' => array(
        'maxlength' => __( 'Summary must be no more than 130 characters', 'mboy' ),
      ),
      $prefix . 'resource_type' => array(
        'required' => __( 'You must select a resource type', 'mboy' ),
      ),
    )
  )
);

//newsroom item metabox:
$meta_boxes[] = array(
  'id' => 'newsroom',
  'title' => 'Newsroom options',
  'pages' => array( 'news' ),
  'fields' => array(
    array(
      'name' => 'News type',
      'desc' => 'For sorting; must be news, thought leadership, or event.',
      'id' => $prefix . 'news_type',
      'type' => 'select',
      'options'  => array(
        'news' => 'News',
        'thought' => 'Thought Leadership',
        'event' => 'Events',
      ),
      'multiple'    => false,
      'placeholder' => 'Select a news type',
    ),
    array(
      'name' => 'News Summary',
      'desc' => '360 characters max',
      'id' => $prefix . 'news_summary',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Link URL',
      'desc' => 'Where does this newsroom item link to?',
      'id' => $prefix . 'news_url',
      'type' => 'text'
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    array(
      'name' => 'Optional logo',
      'desc' => 'Adds the attached image to the news item summary box. Use with care, can break layout height.',
      'id' => $prefix . 'news_logo',
      'type' => 'image_advanced',
      'max_file_uploads' => 1,
    ),
    array(
      'name' => 'Logo top margin',
      'desc' => 'Bump the image down by a certain number of pixels. Enter a unitless integer.',
      'id' => $prefix . 'news_logo_margin',
      'type' => 'number',
      'min'  => 0,
      'step' => 1
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    array(
      'name' => 'Start Date (Optional)',
      'id'   => $prefix . 'news_startdate',
      'type' => 'date',
      'js_options' => array(
        'appendText'      => '(mm-dd-yyyy)',
        'dateFormat'      => 'MM dd, yy',
        'changeMonth'     => true,
        'changeYear'      => true,
        'showButtonPanel' => true,
      ),
    ),
    array(
      'name' => 'End Date (Optional)',
      'id'   => $prefix . 'news_enddate',
      'type' => 'date',
      'js_options' => array(
        'appendText'      => '(mm-dd-yyyy)',
        'dateFormat'      => 'MM dd, yy',
        'changeMonth'     => true,
        'changeYear'      => true,
        'showButtonPanel' => true,
      ),
    )
  ),
  'validation' => array(
    'rules' => array(
      $prefix . 'news_summary' => array(
        'maxlength' => 365,
      ),
    ),
    'messages' => array(
      $prefix . 'news_summary' => array(
        'maxlength' => __( 'Summary must be no more than 365 characters', 'mboy' ),
      ),
    )
  )
);

//customer and partner metabox
//get options for case study select box
$case_array = array();
$case_studies = new WP_Query(array(
  'post_type' => 'resource',
  'meta_key' => 'mb_meta_resource_type',
  'meta_value' => 'case_study',
  'posts_per_page' => 99
));
if( $case_studies->have_posts() ) {
  while( $case_studies->have_posts() ) {
    $case_studies->the_post();
    $case_array[get_the_ID()] = get_the_title();
  }
}
$meta_boxes[] = array(
  'id' => 'assoc_case_study',
  'title' => 'Associated Case Study (optional)',
  'pages' => array( 'customer', 'partner' ),
  'fields' => array(
    array(
      'name' => 'Case Study',
      'desc' => 'If entered, the logo will link to the case study',
      'id' => $prefix . 'associated_case',
      'type' => 'select',
      'options'  => $case_array,
      'multiple'    => false,
      'placeholder' => 'Select a case study',
    )
  )
);

//all pages next steps content
$meta_boxes[] = array(
  'id' => 'nextsteps',
  'title' => 'Next Steps',
  'pages' => array( 'page', 'resource', 'press' ),
  'fields' => array(
    array(
      'type' => 'heading',
      'name' => 'Next Step #1',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Next Step name',
      'desc' => '',
      'id' => $prefix . 'nextstep_name_1',
      'type' => 'text'
    ),
    array(
      'name' => 'Next Step description',
      'desc' => 'Short (1 sentence) blurb',
      'id' => $prefix . 'nextstep_desc_1',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Next Step link href',
      'desc' => 'Where should this link to?',
      'id' => $prefix . 'nextstep_link_1',
      'type' => 'text'
    ),
    array(
      'name' => 'Resource type of link',
      'desc' => 'determines thumbnail image',
      'id' => $prefix . 'nextstep_type_1',
      'type' => 'select',
      'options'  => $resource_types,
      'multiple'    => false,
      'placeholder' => 'Select a resource type',
    ),
    array(
      'type' => 'divider',
      'id'   => 'fake_divider_id',
    ),
    //second step
    array(
      'type' => 'heading',
      'name' => 'Next Step #2',
      'id'   => 'fake_id',
    ),
    array(
      'name' => 'Next Step name',
      'desc' => '',
      'id' => $prefix . 'nextstep_name_2',
      'type' => 'text'
    ),
    array(
      'name' => 'Next Step description',
      'desc' => 'Short (1 sentence) blurb',
      'id' => $prefix . 'nextstep_desc_2',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Next Step link href',
      'desc' => 'Where should this link to?',
      'id' => $prefix . 'nextstep_link_2',
      'type' => 'text'
    ),
    array(
      'name' => 'Resource type of link',
      'desc' => 'determines link thumbnail',
      'id' => $prefix . 'nextstep_type_2',
      'type' => 'select',
      'options'  => $resource_types,
      'multiple'    => false,
      'placeholder' => 'Select a resource type',
    ),
  ),
  'only_on' => array(
    'template' => array( 'default','resource','press','page-about.php','page-contact.php','page-newsroom.php', 'page-customers.php', 'page-partners.php')
  )
);

//all pages optional callout
$meta_boxes[] = array(
  'id' => 'callout',
  'title' => 'Callout (Optional)',
  'pages' => array( 'page', 'resource', 'press' ),
  'fields' => array(
    array(
      'name' => 'Callout title',
      'desc' => 'Callout will display iff you fill in this field',
      'id' => $prefix . 'callout_title',
      'type' => 'text'
    ),
    array(
      'name' => 'Callout content',
      'desc' => 'You can use any normal content you would input in the page content here (e.g. lists, buttons, columns, etc)',
      'id' => $prefix . 'callout_content',
      'type' => 'wysiwyg',
      'raw'  => true,
      'options' => array(
        'textarea_rows' => 6,
        'teeny'         => true,
        'media_buttons' => true,
      ),
    ),
  ),
  'only_on' => array(
    'template' => array( 'default', 'page-customers.php', 'page-partners.php', 'page-careers.php', 'resource', 'press' )
  )
);

//quote metabox:
$meta_boxes[] = array(
  'id' => 'quote',
  'title' => 'Quotation (optional)',
  'pages' => array( 'page' ),
  'fields' => array(
    array(
      'name' => 'Quotation',
      'desc' => '',
      'id' => $prefix . 'quote_text',
      'type' => 'textarea'
    ),
    array(
      'name' => 'Author image',
      'desc' => 'A photo of the quote author (will be cropped to a circle)',
      'id' => $prefix . 'quote_profile',
      'type' => 'image_advanced',
      'max_file_uploads' => 1,
    ),
  ),
  'only_on' => array(
    'template' => array( 'default', 'page-customers.php', 'page-partners.php', 'page-careers.php' )
  )
);


function mb_register_meta_boxes() {
	global $meta_boxes;

	if ( !class_exists( 'RW_Meta_Box' ) )
	        return;

	foreach ( $meta_boxes as $meta_box ) {
		// Register meta boxes only for some posts/pages
		if ( isset( $meta_box['only_on'] ) && ! mb_check_include( $meta_box['only_on'] ) ) {
			continue;
		}
		new RW_Meta_Box( $meta_box );
	}
}

function mb_check_include( $conditions ) {
	// Include in back-end only
	if ( ! defined( 'WP_ADMIN' ) || ! WP_ADMIN )
		return false;

	// Always include for ajax
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		return true;

	if ( isset( $_GET['post'] ) )
		$post_id = $_GET['post'];
	elseif ( isset( $_POST['post_ID'] ) )
		$post_id = $_POST['post_ID'];
	else
		$post_id = false;

	$post_id = (int) $post_id;
	$post = get_post( $post_id );
  $post_type = get_post_type( $post );

	foreach ( $conditions as $cond => $v ) {
		// Catch non-arrays too
		if ( ! is_array( $v ) ) {
			$v = array( $v );
		}

		switch ( $cond ) {
			case 'id':
				if ( in_array( $post_id, $v ) ) {
					return true;
				}
			break;
			case 'parent':
				$post_parent = $post->post_parent;
				if ( in_array( $post_parent, $v ) ) {
					return true;
				}
			break;
			case 'slug':
				$post_slug = $post->post_name;
				if ( in_array( $post_slug, $v ) ) {
					return true;
				}
			break;
			case 'template':
				$template = get_post_meta( $post_id, '_wp_page_template', true );
        // if post type other than page is specified, don't return true
				if ( in_array( $template, $v ) || in_array($post_type, $v)  ) {
					return true;
				}
			break;
		}
	}

	// If no condition matched
	return false;
}
