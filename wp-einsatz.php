<?php
/*
Plugin Name: wp-einsatz
Plugin Script: wp-einsatz.php
Plugin URI: http://www.feuerwehr-guenzburg.de/links/eigene-plugins/
Description: Einsatzliste und Widget fuer Feuerwehren
Version: 0.7.4
Author: Stefan Hauf
Author URI: http://www.feuerwehr-guenzburg.de
*/

require 'widget.php';

$loc_de = setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
setlocale(LC_TIME, $loc_de);
// Erstellt die Tabelle beim ersten Start
function install () {
  global $wpdb;
   
  $table_name = $wpdb->prefix . "einsaetze";
  if($wpdb->get_var("show tables like '$table_name'") != $table_name) {      
    $sql = "CREATE TABLE " . $table_name . " (
	    ID mediumint(9) NOT NULL AUTO_INCREMENT,
      Nr_Jahr mediumint(9) NOT NULL,
      Nr_Monat mediumint(9) NOT NULL,
	    Datum datetime NOT NULL default '0000-00-00 00:00:00',
	    Ort TEXT NOT NULL default '',
	    Art TEXT NOT NULL default '',
	    UNIQUE KEY ID (ID)
	    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    $insert = "INSERT INTO $table_name (Datum, Ort, Art) VALUES (NOW(), 'Musterstadt', 'Fehlalarm')";
    $results = $wpdb->query( $insert );
  }
  add_option( 'wpeinsatz_widgetlink',     '/',                              '', 'no' );
  add_option( 'wpeinsatz_link',           '<img src=\"/wp-content/plugins/wp-einsatz/newspaper.png\">', '', 'no' );
  add_option( 'wpeinsatz_charset',        'none',                           '', 'no' );
  add_option( 'wpeinsatz_nr_jahr',        'nein',                           '', 'no' );
  add_option( 'wpeinsatz_nr_monat',       'nein',                           '', 'no' );
  add_option( 'wpeinsatz_sortierung',     'ASC',                            '', 'no' );
  add_option( 'wpeinsatz_adminanzahl',    '25',                             '', 'no' );
  add_option( 'wpeinsatz_reihenfolge',    'Nr_Jahr,Nr_Monat,Datum,Ort,Art', '', 'no' );
  add_option( 'wpeinsatz_liste_datum',    '%d. %b',                         '', 'no' );
  add_option( 'wpeinsatz_liste_uhrzeit',  '%H:%i',                          '', 'no' );
  add_option( 'wpeinsatz_widget_filter',  '',                               '', 'no' );
  add_option( 'wpeinsatz_widget_datum',   '%d. %b',                         '', 'no' );
  add_option( 'wpeinsatz_widget_uhrzeit', '%H:%i',                          '', 'no' );      
}
register_activation_hook(__FILE__,'install');

//  Fügt das CSS-File in den Header ein
function addHeaderCode() {
  echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-einsatz/wp-einsatz.css" />' . "\n";		
}

add_action('wp_head', 'addHeaderCode', 1);	      
add_action('widgets_init', create_function('', 'return register_widget("wpeinsatzwidget_new");'));

function f_charset($html) {
    $wpeinsatz_charset = get_option('wpeinsatz_charset');
    if ($wpeinsatz_charset != "none") {
      $f_charset = $wpeinsatz_charset;
      $html = $f_charset($html);
    }
    return $html;
}
 
function einsatzzeile($einsatz,$field,$align,$coding) {
	if ($align == "c") {
	  $align = " style=\"text-align:center\"";
	}
	else if ($align == "r") {
	  $align = " style=\"text-align:right\"";
	}
	else {
		$align = "";
	}
  $field = str_replace(" ", "_", $field);      
  if ($field == "Datum") {
    $datum = $einsatz['Datum_F'];    
    if ($coding != "none") {
      $datum = $coding($datum);
    }
    $html .= "    <td$align>$datum</td>\n";
    $html .= "    <td$align>".$einsatz['Uhrzeit']."</td>\n";
  }
  else if ($field == "Link") {
    if ($einsatz[$field] == "") {
      $html .= "    <td> </td>\n";
    }
    else {  
    	$linktext = str_replace("\\\"","\"", get_option( 'wpeinsatz_link'));
      $html .= "    <td$align><a class=\"einsatzbericht\" href=\"".$einsatz[$field]."\">".$linktext."</a></td>\n";
    }
  }
  else {
  	if ( ($field != "Nr_Jahr"  || get_option('wpeinsatz_nr_jahr')  == "ja") &&
  	     ($field != "Nr_Monat" || get_option('wpeinsatz_nr_monat') == "ja")     ) {
    	$html .= "    <td$align>".$einsatz[$field]."</td>\n";
    }
  }
  return $html;
}
  
function einsatz_liste($edit, $start) {
  global $wpdb;  
  $table_name = $wpdb->prefix . "einsaetze";
  $limit = "";
  $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
  if ($edit == 1) {  
    
    if (get_option('wpeinsatz_adminanzahl') > 0) {
	    $limit = "$start, ".get_option('wpeinsatz_adminanzahl');
	    echo "<table border='0'><tr><td>Eins&auml;tze beginnend ab:</td><td>\n";
	    for ($i = 0; $i < $count/get_option('wpeinsatz_adminanzahl'); $i++) {
	      $next  = $i*get_option('wpeinsatz_adminanzahl');
	      if ($start == $next)
	        echo "<td><b><input style=\"font-weight: bold;\" type=\"submit\" name=\"start\" value=\"-".$next."-\" /></b></td>";
	      else
	        echo "<td><form method=\"post\"><input type=\"submit\" name=\"start\" value=\"".$next."\" /></form></td>";
	    }
	    echo "</tr></table>\n";
    }
    else {
    	$limit = "0, 100";
    }
    
  }
  else {  
    $jahr  = $wpdb->escape(get_post_meta(get_the_ID(),'jahr',  TRUE));
    $monat = $wpdb->escape(get_post_meta(get_the_ID(),'monat', TRUE));
    $limit = $wpdb->escape(get_post_meta(get_the_ID(),'letzte',TRUE));
  }
  
  $html  = "<table class='einsatzliste' border='1'>\n";
  $html .= "  <tr>\n";
  $ueberschriften = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
  
  foreach ($ueberschriften as $ueberschrift) {
    $field = $ueberschrift->Field;
    if ($field == "ID") continue; // ignore ID
    $fields[] = $field;
  }
    
  foreach (explode(",",get_option('wpeinsatz_reihenfolge')) as $field) {  
    list($field,$align) = explode(";", $field);   
  	if(($key = array_search($field, $fields)) !== false) {
  		unset($fields[$key]);
		}
  	if ( ($field != "Nr_Jahr"  || get_option('wpeinsatz_nr_jahr')  == "ja") &&
  	     ($field != "Nr_Monat" || get_option('wpeinsatz_nr_monat') == "ja")     ) {
	    $field = str_replace("_", " ", $field);
	  	$html .= "    <th>".$field."</th>\n";
	    if ($field == "Datum")
	      $html .= "    <th>Uhrzeit</th>\n";
    }
  }
    
  foreach ($fields as $field) {           
    $fields2[] = $field;
    if ( ($field != "Nr_Jahr"  || get_option('wpeinsatz_nr_jahr')  == "ja") &&
  	     ($field != "Nr_Monat" || get_option('wpeinsatz_nr_monat') == "ja")     ) {
	    $field = str_replace("_", " ", $field);
	    $html .= "    <th>".$field."</th>\n";  
	    if ($field == "Datum")
	      $html .= "    <th>Uhrzeit</th>\n";      
    }
  }
  if ($edit == 1) {
    $html .= "    <th>&Auml;ndern</th>\n";
    $html .= "    <th>L&ouml;schen</th>\n";
  }

  $html .= "  </tr>\n";
  $sql = "SET lc_time_names = 'de_DE'"; 
  $wpdb->get_results("$sql", ARRAY_A);    

  $mainsql = "SELECT *, DATE_FORMAT(Datum, '".get_option( 'wpeinsatz_liste_datum')."') AS Datum_F, DATE_FORMAT(Datum, '".get_option( 'wpeinsatz_liste_uhrzeit')."') AS Uhrzeit FROM $table_name ";
  if ( $limit != "") {  	
    $sql = "$mainsql ORDER BY Datum DESC LIMIT $limit";
  }
  else if (strlen($jahr) == 4) {
    if ($monat > 0 && $monat < 13) {
      $monat_sql = "AND MONTH(Datum) = '$monat'";
    }
    $sql = "$mainsql WHERE YEAR(Datum) = '$jahr' $monat_sql ORDER BY Datum ".get_option('wpeinsatz_sortierung')."$limit";
  }
  else {                            
    $sql = "$mainsql ORDER BY Datum DESC LIMIT 10";
  }
  $einsaetze = $wpdb->get_results("$sql", ARRAY_A);       
      
  $cod1 = $wpdb->get_results("SHOW VARIABLES LIKE 'character_set_results'");  
  $cod2 = $wpdb->get_results("SHOW VARIABLES LIKE 'character_set_database'");    
  $cod1 = $cod1[0]->Value;
  $cod2 = $cod2[0]->Value;
  $coding = "none";
  if ($cod1 == "utf8"   && $cod2 == "latin1") $coding = "utf8_decode";      
  if ($cod1 == "latin1" && $cod2 == "utf8"  ) $coding = "utf8_encode";      
  
  foreach ($einsaetze as $einsatz) {    
    $i++;
    $html .= ($i%2) ? "  <tr>\n" : "  <tr class='alt'>\n";
    foreach (explode(",",get_option('wpeinsatz_reihenfolge')) as $field) {
    	list($field,$align) = explode(";", $field);
    	if(($key = array_search($field, $fields)) !== false) {
    		unset($fields[$key]);
			}			
    	$html .= einsatzzeile($einsatz,$field,$align,$coding);
    }
    foreach ($fields as $field) {
    	list($field,$align) = explode(";", $field);
    	$html .= einsatzzeile($einsatz,$field,$align,$coding);
    }          
    if ($edit == 1) {
      $html .= "    <td><form method=\"post\" id=\"edit\"  ><fieldset class=\"options\"><input type=\"hidden\" name=\"ID\" value=\"".$einsatz['ID']."\"><input type=\"submit\" name=\"edit\"   value=\"&Auml;ndern\"  /></fieldset></form></td>";
      $html .= "    <td><form method=\"post\" id=\"delete\"><fieldset class=\"options\"><input type=\"hidden\" name=\"ID\" value=\"".$einsatz['ID']."\"><input type=\"submit\" name=\"delete\" value=\"L&ouml;schen\" /></fieldset></form></td>";        
    }
    $html .= "  </tr>\n";
  }	 
  $html .= "</table>\n";
  return f_charset($html);
}

function einsatz_filter($content) {
  return preg_replace( '<!--einsatzliste-->', einsatz_liste(0, 0), $content );
  return preg_replace( 'wpeinsatzlistewp', einsatz_liste(0, 0), $content );
}
add_filter('the_content', 'einsatz_filter');


function wpeinsatzliste_func( ) {
	return einsatz_liste(0, 0);
}
add_shortcode( 'wpeinsatzliste', 'wpeinsatzliste_func' );


function einsatz_adminliste() {
  global $wpdb;   
  $table_name = $wpdb->prefix . "einsaetze";
  if($_POST['delete']) {
    $wpdb->query("DELETE FROM $table_name WHERE ID = ".$_POST['ID']." LIMIT 1");
    echo "<div class='updated'><p>Einsatz wurde erfolgreich gel&ouml;scht.</p></div>";
  }
  if($_POST['update']) {
    $sql = "UPDATE $table_name SET "; 
    foreach ($_POST as $key => $value) {    
      if ($key == "ID" || $key == "update") continue;
      $sql .= "`$key` = \"$value\", ";
    }
    $sql   = substr($sql,0,-2);    
    $sql .= " WHERE ID = ".$_POST['ID']." LIMIT 1";  
    $wpdb->query($sql);
    
    echo "<div class='updated'><p>Einsatz wurde erfolgreich aktualisiert.</p></div>";
  }

  if($_POST['edit']) {
    $sql = "SELECT * FROM $table_name WHERE ID = ".$_POST['ID']." LIMIT 1";       
    $einsatz = $wpdb->get_row($sql, ARRAY_A);
    
    $html  = "<table>\n  <form METHOD='POST'>\n";
    foreach ($einsatz as $key => $value) {
      $type = "text";      
      if ($key == "ID")  {
        $type = "hidden";
        $html .= "<input name='$key' value='$value' type='$type'>";
      }
      else {
      	if ( ($key != "Nr_Jahr"  || get_option('wpeinsatz_nr_jahr')  == "ja") &&
  	         ($key != "Nr_Monat" || get_option('wpeinsatz_nr_monat') == "ja")     ) {    
        	$html .= "<tr><td>$key</td><td><input name='$key' value='$value' type='$type'></td></tr>\n";
      	}
      }    
    }
    $html .= "    <tr><td>&nbsp;</td><td><input type='submit' name='update'></td></tr>\n  </form>\n</table>\n";
    echo f_charset($html);
  }
  else {
    if (!isset($_POST["start"])) $start = 0;
    else                         $start = $_POST["start"];
    
    $html = einsatz_liste(1, $start);   
    echo $html;
  }
}

function einsatz_new() {
  global $wpdb;  
  $table_name = $wpdb->prefix . "einsaetze";
  
  if($_POST['new']) {
  
    foreach($_POST as $key=>$value) {
  	  $key = str_replace(" ", "_", $key);
      if ($value!='' && $key!="new") {
        $keys .= "`$key`, ";
        $values .= "'$value', ";
      }
    }
    $keys   = substr($keys,0,-2);
    $values = substr($values,0,-2);
    $sql = "INSERT INTO $table_name ($keys) VALUES ($values)";
    $results = $wpdb->query($sql);
    echo "<div class='updated'><p>Einsatz wurde erfolgreich eingetragen.</p></div>";
  }
  $date = $wpdb->get_var("SELECT NOW()");
  $nr_monat = $wpdb->get_var("SELECT MAX(Nr_Monat) from $table_name WHERE Month(Datum)=MONTH(NOW()) AND Year(Datum)=YEAR(NOW())");
  $nr_monat++;
  $nr_jahr = $wpdb->get_var("SELECT MAX(Nr_Jahr) from $table_name WHERE Year(Datum)=YEAR(NOW())");
  $nr_jahr++;
     
  $html  = "<table>\n  <form METHOD='POST'>\n";
  $ueberschriften = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
  foreach ($ueberschriften as $ueberschrift) {
    $field = $ueberschrift->Field;
  	$field = str_replace("_", " ", $field);
    $default = $ueberschrift->Default;
    if ($field == "ID") continue; // ignore ID                 
    if ($field == "Datum")      
      $html .= "    <tr><td>$field</td><td><input name='$field' type='text' value='".$date."'></td></tr>\n";
    else if ($field == "Nr Jahr") {
    	if (get_option('wpeinsatz_nr_jahr')  == "ja")
        $html .= "    <tr><td>$field</td><td><input name='$field' type='text' value='".$nr_jahr."'></td></tr>\n";	
    }
    else if ($field == "Nr Monat") {
    	if (get_option('wpeinsatz_nr_monat') == "ja")
        $html .= "    <tr><td>$field</td><td><input name='$field' type='text' value='".$nr_monat."'></td></tr>\n";	
    }
    else    
      $html .= "    <tr><td>$field</td><td><input name='$field' type='text' value='$default'></td></tr>\n";
  }
  $html .= "    <tr><td>&nbsp;</td><td><input type='submit' name='new'></td></tr>\n  </form>\n</table>";
  echo f_charset($html);
}

function einsatz_settings() {
		
  global $wpdb;  
  $table_name = $wpdb->prefix . "einsaetze";
  if($_POST['field_delete']) {
  	$old = str_replace(" ", "_", $_POST['old']);
    $wpdb->query("ALTER TABLE $table_name DROP `$old`");    
    $reihenfolge = str_replace(",$old", "", get_option( 'wpeinsatz_reihenfolge'));
    $reihenfolge = str_replace("$old,", "", $reihenfolge);
  	update_option( 'wpeinsatz_reihenfolge', $reihenfolge);  	  
    echo "<div class='updated'><p>Feld wurde erfolgreich gel&ouml;scht.</p></div>";
  }
  if($_POST['field_edit']) {
  	$new = str_replace(" ", "_", $_POST['new']);
  	$old = str_replace(" ", "_", $_POST['old']);    
    $reihenfolge = str_replace("$old", "$new", get_option( 'wpeinsatz_reihenfolge'));
    update_option( 'wpeinsatz_reihenfolge', $reihenfolge);
    $wpdb->query("ALTER TABLE $table_name CHANGE `$old` `$new` TEXT");
    echo "<div class='updated'><p>Feld wurde erfolgreich bearbeitet.</p></div>";
  }
  if($_POST['field_new']) {
  	$field = str_replace(" ", "_", $_POST['field']);
  	$query = "ALTER TABLE $table_name ADD `$field` TEXT NOT NULL";
    $wpdb->query($query);    
  	update_option( 'wpeinsatz_reihenfolge', get_option( 'wpeinsatz_reihenfolge').",".$field);  	  
    echo "<div class='updated'><p>Feld wurde erfolgreich angelegt.</p></div>";
  }
  if($_POST['reihenfolge']) {
  	  update_option( 'wpeinsatz_reihenfolge', $_POST['reihenfolge']);  	  
      echo "<div class='updated'><p>Feld wurde erfolgreich aktualisiert.</p></div>";
  }

  if($_POST['liste_datum']) {
  	  update_option( 'wpeinsatz_liste_datum', $_POST['format']);  	  
      echo "<div class='updated'><p>Feld wurde erfolgreich aktualisiert.</p></div>";
  }
  if($_POST['liste_uhrzeit']) {
  	  update_option( 'wpeinsatz_liste_uhrzeit', $_POST['format']);  	  
      echo "<div class='updated'><p>Feld wurde erfolgreich aktualisiert.</p></div>";
  }
  if($_POST['widget_datum']) {
  	  update_option( 'wpeinsatz_widget_datum', $_POST['format']);  	  
      echo "<div class='updated'><p>Feld wurde erfolgreich aktualisiert.</p></div>";
  }
  if($_POST['widget_filter']) {
  	  update_option( 'wpeinsatz_widget_filter', str_replace('\"', '"', $_POST['format']));  	  
      echo "<div class='updated'><p>Feld wurde erfolgreich aktualisiert.</p></div>";
  }
  if($_POST['widget_uhrzeit']) {
  	  update_option( 'wpeinsatz_widget_uhrzeit', $_POST['format']);  	  
      echo "<div class='updated'><p>Feld wurde erfolgreich aktualisiert.</p></div>";
  }
  	
  if($_POST['setting_link']) {
  	if (isset($_POST['widgetlink']))
  	  update_option( 'wpeinsatz_widgetlink', $_POST['widgetlink']);
  	if ($_POST['link'])
  	  update_option( 'wpeinsatz_link', $_POST['link']);
  	if ($_POST['charset'])
  	  update_option( 'wpeinsatz_charset', $_POST['charset']);
  	if ($_POST['nr_jahr'])
  	  update_option( 'wpeinsatz_nr_jahr', $_POST['nr_jahr']);
  	if ($_POST['nr_monat'])
  	  update_option( 'wpeinsatz_nr_monat', $_POST['nr_monat']);
  	if ($_POST['sortierung'])
  	  update_option( 'wpeinsatz_sortierung', $_POST['sortierung']);
  	if ($_POST['adminanzahl'])
  	  update_option( 'wpeinsatz_adminanzahl', $_POST['adminanzahl']);
  	  
    echo "<div class='updated'><p>Einstellung wurde erfolgreich aktualisiert.</p></div>";
  }
  
  $html  = "<h3>Vorhandene Felder bearbeiten:</h3>\n";
  $ueberschriften = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
  $html .= "<table>\n<tr><th>Feldname</th><th colspan=\"2\">Reihenfolge</th><th colspan=\"3\">Ausrichtung</th><th></th></tr>\n";
  
  //foreach ($ueberschriften as $ueberschrift) {
  $felder =	get_option('wpeinsatz_reihenfolge');    
  $fields = explode(",", $felder);
  foreach ($fields as $field) {
    //$field = $ueberschrift->Field;    
    if ($field == "ID")       continue; // ignore ID                 
    $field2 = $field;
  	$field = str_replace("_", " ", $field);  	
  	list($field,$align) = explode(";", $field);   
    $html .= "<tr><td><form METHOD='POST'>\n";
    if ($field != "Nr Jahr" && $field != "Nr Monat" && $field != "Datum") {    
    	$html .= "<input name='old' type='hidden' value='$field'><input name='new' type='text' value='$field'><input type='submit' name='field_edit' value='Name &auml;ndern'><input type='submit' name='field_delete' value='Feld l&ouml;schen'></form></td>";    	
    }
    else {
    	$html .= "$field";
    }
    $html .= "</td>\n<td>";
    $fields = explode(",", $felder);
    $pos = array_search($field2, $fields);
    if ($pos > 0) {
    	$fields[$pos]   = $fields[$pos-1];
    	$fields[$pos-1] = $field2;
    	$link_u = implode(",",$fields);
      $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_u\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/up.png\" width=\"16\" height=\"16\"></form>";
    }
    else {
    	$html .= "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/blank.png\" width=\"16\" height=\"16\">";
    }
    $html .= "</td>\n<td>";
    $fields = explode(",", $felder);
    if ($pos < count($fields)-1 ) {
    	$fields[$pos]   = $fields[$pos+1];
    	$fields[$pos+1] = $field2;
    	$link_d = implode(",",$fields);
      $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_d\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/down.png\" width=\"16\" height=\"16\"></form>";
    }
    $html .= "</td>";
    $html .= "<td><nobr>";
    $left  ="_sel";        
    $center="";
    $right ="";
  	$field1 = str_replace(" ", "_", $field);
    $link_l = str_replace($field2,"$field1",$felder);
    $link_c = str_replace($field2,"$field1;c",$felder);
    $link_r = str_replace($field2,"$field1;r",$felder);       
    if ($align == "c") {
    	$center="_sel";
    	$left="";    
		  $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_l\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_left$left.png\"></form>";
    $html .= "</td>\n<td>";
		  $html .= "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_center$center.png\" width=\"16\" height=\"16\">";
    $html .= "</td>\n<td>";
		  $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_r\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_right$right.png\"></form>";
    }
    else if ($align == "r") {
    	$right ="_sel";
    	$left="";
		  $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_l\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_left$left.png\"></form>";
    $html .= "</td>\n<td>";
		  $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_c\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_center$center.png\"></form>";
    $html .= "</td>\n<td>";
		  $html .= "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_right$right.png\" width=\"16\" height=\"16\">";
    }
    else {
		  $html .= "<img src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_left$left.png\" width=\"16\" height=\"16\">";
    $html .= "</td>\n<td>";
		  $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_c\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_center$center.png\"></form>";
    $html .= "</td>\n<td>";
		  $html .= "<form style=\"display:inline\" method=\"post\"><input type=\"hidden\" name=\"reihenfolge\" value=\"$link_r\"><input type=\"image\" src=\"".get_bloginfo('wpurl')."/wp-content/plugins/wp-einsatz/align_right$right.png\"></form>";
    }
    $html .= "</nobr></td>";
    $html .= "</tr>\n";
  }
  $html .= "</table>\n";
  $html .= "<h3>Neues Feld anlegen:</h3>";
  $html .= "<form METHOD='POST'>\n<input name='field' type='text' value=''><input type='submit' name='field_new' value='Anlegen'>\n</form>\n";
  $html .= "<h3>Einstellungen:</h3>\n";
  $html .= "Widget-Linkadresse:<br>\n";	
  $html .= "<form METHOD='POST'>\n<code>".get_bloginfo( 'wpurl')."</code><input name='widgetlink' type='text' value='".get_option( 'wpeinsatz_widgetlink')."'><input type='submit' name='setting_link' value='&Auml;ndern'> Leer f&uuml;r keinen Link\n</form>\n";  	
  $html .= "Text f&uuml;r 'Links':<br>\n";	  
  $linktext = str_replace("\\\"","\"", get_option( 'wpeinsatz_link'));
  $html .= "<form METHOD='POST'>\n<input name='link' type='text' value='$linktext'><input type='submit' name='setting_link' value='&Auml;ndern'> <code>&lt;img src=\"url_zum_bild\"&gt;</code> f&uuml;r ein Bild statt Text\n</form>\n";  	
  $html .= "Zeichencodierung:<br>\n";	
  $html .= "<form METHOD='POST'>\n<select name='charset'>\n";
  $char = array('keine' => 'none', 'UTF8 Enc' => 'utf8_encode', 'UTF8 Dec' => 'utf8_decode');
  foreach($char as $besch => $wert) {
    if (get_option( 'wpeinsatz_charset') == $wert) $selected = "selected";
    else                                           $selected = "";
    $html .= "<option value='$wert' $selected>$besch</option>\n";
  }
  $html .= "</select>\n<input type='submit' name='setting_link' value='&Auml;ndern'></form>\n";
  $html .= "<br>\n";	
  $html .= "Einsatzzahlen:<br>\n";	
  $html .= "Generiert ein Feld in das beim Eintragen automatisch die n&auml;chste Einsatznummer eingetragen wird.<br>\n";	    
  if (get_option( 'wpeinsatz_nr_jahr') == "nein") {
  	$nein = "checked";
  	$ja   = "";
  }
  else {
  	$nein = "";
  	$ja   = "checked";
  }  
  $html .= "<table>\n";
  $html .= "<tr><td><form METHOD='POST'>\nJahresweise</td><td><input type=\"radio\" name=\"nr_jahr\" value=\"ja\"$ja> Ja </td><td><input type=\"radio\" name=\"nr_jahr\" value=\"nein\"$nein> Nein </td><td>\n<input type='submit' name='setting_link' value='&Auml;ndern'></form></td></tr>\n";  	
  if (get_option( 'wpeinsatz_nr_monat') == "nein") {
  	$nein = "checked";
  	$ja   = "";
  }
  else {
  	$nein = "";
  	$ja   = "checked";
  }  
  $html .= "<tr><td><form METHOD='POST'>\nMonatsweise</td><td><input type=\"radio\" name=\"nr_monat\" value=\"ja\"$ja> Ja </td><td><input type=\"radio\" name=\"nr_monat\" value=\"nein\"$nein> Nein </td><td>\n<input type='submit' name='setting_link' value='&Auml;ndern'></form></td></tr>\n";  	
  $html .= "</table>\n";  
  $html .= "<br>\n";	
  $html .= "Sortierung: (gilt nicht f&uuml;r Adminbereich)\n";
  if (get_option( 'wpeinsatz_sortierung') == "ASC") {
  	$ASC  = "checked";
  	$DESC = "";
  }
  else {
  	$ASC  = "";
  	$DESC = "checked";
  }  
  $html .= "<table>\n";
  $html .= "<tr><td><form METHOD='POST'>\n<input type=\"radio\" name=\"sortierung\" value=\"ASC\"$ASC> Aufsteigend (&Auml;ltester zu erst) </td></tr>\n";
  $html .= "<tr><td><input type=\"radio\" name=\"sortierung\" value=\"DESC\"$DESC> Absteigend (Neuster zu erst) </td></tr>\n";
  $html .= "<tr><td>\n<input type='submit' name='setting_link' value='&Auml;ndern'></form></td></tr>\n";  	
  $html .= "</table>\n";  
  
  $html .= "<br>\n";	
  $html .= "Datums- und Uhrzeitformate<br>\n";	
  $html .= "<table><tr><td>Einsatzliste Datum";	
  $html .= "<form METHOD='POST'>\n<input name='format' type='text' value='".get_option( 'wpeinsatz_liste_datum')."'><input type='submit' name='liste_datum' value='&Auml;ndern'>\n</form>\n";  	
  $html .= "</td><td rowspan=\"4\"> </td><td rowspan=\"4\" bgcolor=\"lightgrey\">Datum:<br>
%a	Abgek&uuml;rzter Name des Wochentags (So ... Sa)<br>
%W	Name des Wochentags (Sonntag ... Samstag)<br>
%d	Tag im Monat, numerisch (00 ... 31)<br>
%e	Tag im Monat, numerisch (0 ... 31)<br>
%m	Monat, numerisch (00 ... 12)<br>
%c	Monat, numerisch (0 ... 12)<br>
%b	Abgek&uuml;rzter Name des Monats (Jan ... Dez)<br>
%M	Monatsname (Januar ... Dezember)<br>
%Y	Jahr, numerisch, vierstellig<br>
%y	Jahr, numerisch, zweistellig";
  $html .= "</td><td rowspan=\"4\">Uhrzeit:<br>
%H	Stunde (00 ... 23)<br>
%k	Stunde (0 ... 23)<br>
%i	Minuten<br>
%S	Sekunden</td></tr>\n<tr><td>";	
  $html .= "Einsatzliste Uhrzeitformat<br>\n";	
  $html .= "<form METHOD='POST'>\n<input name='format' type='text' value='".get_option( 'wpeinsatz_liste_uhrzeit')."'><input type='submit' name='liste_uhrzeit' value='&Auml;ndern'>\n</form>\n";  	
  $html .= "</td></tr>\n<tr><td>";	
  $html .= "Widget Filter<br>\n";	
  $html .= "<form METHOD='POST'>\n<input name='format' type='text' value='".get_option( 'wpeinsatz_widget_filter')."'><input type='submit' name='widget_filter' value='&Auml;ndern'>\n</form>leer f&uuml;r keine Filterung\n";  	
  $html .= "</td></tr>\n<tr><td>";	
  $html .= "Widget Datumsformat<br>\n";	
  $html .= "<form METHOD='POST'>\n<input name='format' type='text' value='".get_option( 'wpeinsatz_widget_datum')."'><input type='submit' name='widget_datum' value='&Auml;ndern'>\n</form>\n";  	
  $html .= "</td></tr>\n<tr><td>";	
  $html .= "Widget Uhrzeitformat<br>\n";	
  $html .= "<form METHOD='POST'>\n<input name='format' type='text' value='".get_option( 'wpeinsatz_widget_uhrzeit')."'><input type='submit' name='widget_uhrzeit' value='&Auml;ndern'>\n</form>\n";  	
  $html .= "</td>\n</tr></table>";	
  
  $html .= "<br>\n";	
  $html .= "Adminbereich: Anzahl der Eins&auml;tze auf einer Seite<br>\n";	
  $html .= "<form METHOD='POST'>\n<input name='adminanzahl' type='text' value='".get_option( 'wpeinsatz_adminanzahl')."'><input type='submit' name='setting_link' value='&Auml;ndern'>\n</form>\n";  	
  
  $html .= "<br>\n";	
  $html .= "Bei Problemen mit Zeichcodierung bitte die folgenden Zeilen in die Email anh&auml;ngen:<br>\n";	  
  foreach($wpdb->get_results("SHOW VARIABLES LIKE 'character_set_%'") as $row) {
    $html .= $row->Variable_name.": ".$row->Value."<br>\n";
  }
  $html .= "blog_charset: ".get_option("blog_charset")."<br>\n";
  $html .= "wpeinsatz_charset: ".get_option("wpeinsatz_charset")."<br>\n";
  
  echo $html;
}

//  Erstellen der Menus und deren Anzeige im wp-Adminbereich
function einsatz_menu() {
  add_menu_page('Eins&auml;tze', 'Eins&auml;tze', 1, __FILE__, 'einsatz_adminliste');
  add_submenu_page(__FILE__, 'Neuer Einsatz', 'Neuer Einsatz', 1, 'einsatz_new', 'einsatz_new');
  add_submenu_page(__FILE__, 'Einstellungen', 'Einstellungen', 8, 'einsatz_settings', 'einsatz_settings');
}
add_action('admin_menu', 'einsatz_menu');


function einsatz_widget_init() 
{
  function einsatz_widget_list() 
  {
    global $wpdb;
    $table_name = $wpdb->prefix . "einsaetze";

    $sql = "SET lc_time_names = 'de_DE'"; 
    $wpdb->get_results("$sql", ARRAY_A);    
	if (get_option('wpeinsatz_widget_filter') != '') {
		$filter = "WHERE ".get_option('wpeinsatz_widget_filter')." ";
	}
	else {
		$filter = "";
	}
	
	$einsatz = $wpdb->get_row("SELECT *, DATE_FORMAT(Datum, '".get_option( 'wpeinsatz_widget_datum')."') AS Datum_F, DATE_FORMAT(Datum, '".get_option( 'wpeinsatz_widget_uhrzeit')."') AS Uhrzeit FROM $table_name $filter ORDER BY Datum DESC LIMIT 1");

    echo "<li id=\"archives\" class=\"widget widget_archive\"><h2 class=\"widgettitle\">Letzter Einsatz</h2>";
    
    $cod1 = $wpdb->get_results("SHOW VARIABLES LIKE 'character_set_results'");  
    $cod2 = $wpdb->get_results("SHOW VARIABLES LIKE 'character_set_database'");    
    $cod1 = $cod1[0]->Value;
    $cod2 = $cod2[0]->Value;
    $coding = "none";
    if ($cod1 == "utf8"   && $cod2 == "latin1") $coding = "utf8_decode";      
    if ($cod1 == "latin1" && $cod2 == "utf8"  ) $coding = "utf8_encode";      
        
    $datum = $einsatz->Datum_F;    
    if ($coding != "none") {
      $datum = $coding($datum);
    }
    
    $text = $datum." ".$einsatz->Uhrzeit."<br>".$einsatz->Ort."<br>".$einsatz->Art;
    if ( get_option( 'wpeinsatz_widgetlink') != "") {
      $text = "<a href='".get_settings('home').get_option( 'wpeinsatz_widgetlink')."'>".$text."</a>";
    }
    $html = "<ul><li>".$text."</li></ul></li>\n";
    echo f_charset($html);
        
  }

  wp_register_sidebar_widget(
    'letzter_einsatz',        // your unique widget id
    'Letzter Einsatz',          // widget name
    'einsatz_widget_list',  // callback function
    array(                  // options
        'description' => 'Zeigt den letzten Einsatz auf der Startseite'
    )
	);
  wp_register_sidebar_widget(
    'letzter_einsatz',        // your unique widget id
    'Letzter Einsatz',          // widget name
    'einsatz_widget_list_control'  // callback function
	);
}

add_action('widgets_init', 'einsatz_widget_init');
?>
