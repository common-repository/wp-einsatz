<?php 
class wpeinsatzwidget_new extends WP_Widget
{
  function __construct()
  {
	#load_plugin_textdomain('php-everywhere', false, plugin_dir_path( __FILE__ )  . 'languages/' );
    $widget_ops = array('classname' => 'wpeinsatzwidget_new', 'description' => __('Zeige Einsatzliste', 'wp-einsatz') );
    parent::__construct('wpeinsatzwidget_new', 'WP-Einsatz', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array('title' => 'Letzter Einsatz', 'filter' => '' ) );    
    $title = $instance['title'];
    $filter = $instance['filter'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label>
  <label for="<?php echo $this->get_field_id('filter'); ?>">Filter: <textarea class="widefat" id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" rows="10"><?php echo esc_attr($filter); ?></textarea></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['filter'] = $new_instance['filter'];
    return $instance;
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
	
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
    $filter = empty($instance['filter']) ? ' ' : apply_filters('widget_content', $instance['filter']);
	
	echo $before_title . $title . $after_title;;
	
 
    // WIDGET CODE GOES HERE
	global $wpdb;
    $table_name = $wpdb->prefix . "einsaetze";

    $sql = "SET lc_time_names = 'de_DE'"; 
    $wpdb->get_results("$sql", ARRAY_A);    
	if (trim($filter) != '') {
		$filter = str_replace("\\\"","\"", "WHERE $filter ");
	}
	
	$einsatz = $wpdb->get_row("SELECT *, DATE_FORMAT(Datum, '".get_option( 'wpeinsatz_widget_datum')."') AS Datum_F, DATE_FORMAT(Datum, '".get_option( 'wpeinsatz_widget_uhrzeit')."') AS Uhrzeit FROM $table_name $filter ORDER BY Datum DESC LIMIT 1");
    
	$datum = $einsatz->Datum_F;           
    $text = $datum." ".$einsatz->Uhrzeit."<br>".$einsatz->Ort."<br>".$einsatz->Art;
    if ( get_option( 'wpeinsatz_widgetlink') != "") {
      $text = "<a href='".get_settings('home').get_option( 'wpeinsatz_widgetlink')."'>".$text."</a>";
    }
	echo $text;
    #$html = $text;
    #echo f_charset($html);
 
    echo $after_widget;
  }
 
}
?>