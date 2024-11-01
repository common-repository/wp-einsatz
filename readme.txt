=== Plugin Name ===
Contributors: stoepf
Tags: wp-einsatz,einsatz,feuerwehr,rettungsdienst
Tested up to: 5.5.1
Requires at least: 3.0
Stable tag: 0.7.4

Feuerwehreinsätze einfach in Wordpress organisieren

== Description ==

Bei WP-Einsatz handelt es sich um ein spezielles WordPress-Plugin für Feuerwehren.
Mit Hilfe dieses Plugins kann der letzte Einsatz als Widget angezeigt werden und eine Einsatzliste auf einer beliebigen Seite angezeigt werden.

Verwendung:
Mit Hilfe des Shortcodes "[wpeinsatzliste]" (oder der beiden Platzhalters “<!−−einsatzliste−−>” oder "wpeinsatzlistewp") kann an einer beliebigen Stelle eine Einsatzliste eingefügt werden. Dazu muss noch ein benutzerdefiniertes Feld “jahr” erzeugt werden, dass das gewünschte Berichtsjahr enthält. Zur Anzeige von Einsätze eines einzelnen Monats muss ein Feld “monat” hinzugefügt werden. Gültige Werte in diesem Feld sind 1-12.
Erzeugt man statt dem Feld “jahr” ein Feld “letzte” mit einem Wert x, dann werden die letzten x Einsätze angezeigt. Wird beides nicht angegeben werden alle Einsätze in der Tabelle angezeigt.

== Installation ==

1. Die beiden Dateien `wp-einsatz.php` und `wp-einsatz.css` in das Verzeichnis `/wp-content/plugins/` hochladen
2. Das Plugin im 'Plugins'-Menü von WordPress aktivieren
3. Während der Pluginaktivierung wird eine Tabelle wp_einsaetze erzeugt, in die man Einsätze eingetragen kann. Die Tabelle kann dynamisch erweitert werden. Die Felder werden dann automatisch mit angezeigt. Nicht geändert werden dürfen die Felder ID und Datum.

== Screenshots ==

1. benutzerdefiniertefelder.jpg
   Beispiel für ein benutzerdefiniertes Feld zur Anzeige aller Einsätze des Jahres 2013

== Changelog ==

= 0.7.4 =
* Neues Widget "WP-Einsatz" mit anpassbarem Titel und Filtermöglichkeit
* z.B. 'Feuerwehr LIKE "%A-Dorf%"'

= 0.7.3 =
* PHP 7 kompatibel

= 0.7.2 =
* Icons hinzugefügt
* Zeichencodierung verändert

= 0.7.1 =
* Widget-Fehler behoben

= 0.7 =
* Shortcode eingeführt
* Datums- und Uhrzeitformat konfiguierbar
* Sortierung umschaltbar
* Feld-Reihenfolge einstellbar
* Feld-Ausrichtung einstellbar
* Automatische Einsatznummern
* Anzahl der Einsätze pro Seite im Adminbereich
    
= 0.6.3 =
* Zusätzlicher Platzhalter möglich
    
= 0.6.2 =
* Adminbereich: Anzeige von Einsätzen in 10er Blöcken
* Zeichenkodierung kann bei Problemen verändert werden

= 0.6.1 =
* Filterung nach Monat möglich

= 0.6 =
* Neues Menu “Einstellungen”
* Widget-Link einstellbar
* Text/Bild für Links einstellbar
* Mögliche Felder können hinzugefügt/bearbeitet/gelöscht werden
* Datumsformatierung verbessert

= 0.5 =
* Letzte x Einsätze angezeigen
* Farbeinstellung für Berichtslink im Stylesheet möglich
* Kodierungseinstellungen rausgenommen

= 0.4.1 =
* Bugfix: fehlendem Parameter in Funktion einsatz_liste
* Bugfix: eregi_replace durch ereg_replace ersetzt
* Sonderbehandlung für Feld “Link” zum verlinken von Einsatzberichten

= 0.3 =
* Administrationsmenu: Einfügen, Bearbeiten und Löschen von Einsätzen direkt aus WordPress
