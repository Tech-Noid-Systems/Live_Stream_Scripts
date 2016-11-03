<?php
/*
    Podcast generator.
    -- ver. 1.0
    -- -- coded by Aaron Cupp (disfigure at tech-noid.net)

    This script uses two php classes for the majority of the work.
    GETID3 is used for extracting the data from the MP3's
    FeedCreator is used to generate the rss feed for the site
*/

/*  ==================  INCLUDES (classes, functions, etc)  ====================  */
    require("includes/feedcreator/feedcreator.class.php");
    require('includes/getid3/getid3.php');

/*  =============================  GLOBAL VARIABLES  ===========================  */
    define('PODCAST_TABLE', 'podcasts');
    define('PODCAST_DIR', 'D:/Content/SITE/archives/');
    define('PODCAST_WWW', 'http://master.tech-noid.net/archives/');

    // Needed for windows only
    define('GETID3_HELPERAPPSDIR', 'D:/Content/SITE/station/includes/helperapps/');

    // Initialize getID3 engine
    $getID3 = new getID3;

/*  ===========================  DATABASE CONNECTION  ==========================  */
    $dbHost = "master.tech-noid.net";
    $dbUser = "";
    $dbPass = "";
    $connection = mysql_connect($dbHost, $dbUser, $dbPass);
    mysql_select_db("samdb", $connection);

/*  ============================  HELPER FUNCTIONS  ============================  */
    function safe_mysql_query($SQLquery) {
        $result = @mysql_query($SQLquery);
        if (mysql_error()) {
            die('<FONT COLOR="red">'.mysql_error().'</FONT><hr><TT>'.$SQLquery.'</TT>');
        }
        return $result;
    }

    function mysql_table_exists($tablename) {
        return (bool) mysql_query('DESCRIBE '.$tablename);
    }

/*  =======================  DATABASE TABLE MANAGEMENT  ========================  */
    // check and see if the tab exists, if so purge the fucker
    if (mysql_table_exists(PODCAST_TABLE)) {
        $SQLquery  = 'DROP TABLE `'.PODCAST_TABLE.'`';
        safe_mysql_query($SQLquery);
    }

    // verify tha the table is gone, then build a new table
    if (!mysql_table_exists(PODCAST_TABLE)) {
        $SQLquery  = 'CREATE TABLE `'.PODCAST_TABLE.'` (';
        $SQLquery .= ' `ID` mediumint(8) unsigned NOT NULL auto_increment,';
        $SQLquery .= ' `artist` varchar(255) NOT NULL default "",';
        $SQLquery .= ' `title` varchar(255) NOT NULL default "",';
        $SQLquery .= ' `description` varchar(255) NOT NULL default "",';
        $SQLquery .= ' `album` varchar(255) NOT NULL default "",';
        $SQLquery .= ' `length` integer NOT NULL default "0",';
        $SQLquery .= ' `date` integer NOT NULL,';
        $SQLquery .= ' `filepath` text NOT NULL,';
        $SQLquery .= ' PRIMARY KEY (`ID`)';
        $SQLquery .= ') TYPE=MyISAM;';
        safe_mysql_query($SQLquery);
    }

/*  =====================  PROCESS PODCAST DIRECTORY  ==========================  */
    // lets opne up the directory to process (podcast enclosure location)
    $folder = opendir(PODCAST_DIR);
    $i=1;
    while (($file = readdir($folder)) !== false) {
        if($file != "." && $file != "..") {
            $processme = PODCAST_DIR . $file;
            //echo "$i. $file <br />";
            
            // Analyze file and store returned data in $ThisFileInfo
            $ThisFileInfo = $getID3->analyze($processme);
            
            getid3_lib::CopyTagsToComments($ThisFileInfo);
            
            // break out the info to some variables
            $artist = str_replace('&amp;', '&', @implode($ThisFileInfo['comments_html']['artist']));
            $title = str_replace('&amp;', '&', @implode($ThisFileInfo['comments_html']['title']));
            
            $descrip = @implode(@$ThisFileInfo['tags']['id3v2']['comments']);
            $album = @implode($ThisFileInfo['comments_html']['album']);
            //$length = $ThisFileInfo['playtime_seconds'];
            $length = filesize($processme);
            $mod_date = filemtime($processme);
            
            //$filepath = PODCAST_WWW . $ThisFileInfo['filenamepath'];
            $filepath = PODCAST_WWW . $file;
            
            $SQLquery  = 'INSERT INTO `'.PODCAST_TABLE.'` (`ID`, `artist`, `title`, `description`, `album`, `length`, `date`, `filepath`) VALUES (';
            $SQLquery .= '"'.mysql_escape_string($i).'", ';
            $SQLquery .= '"'.mysql_escape_string($artist).'", ';
            $SQLquery .= '"'.mysql_escape_string($title).'", ';
            $SQLquery .= '"'.mysql_escape_string($descrip).'", ';
            $SQLquery .= '"'.mysql_escape_string($album).'", ';
            $SQLquery .= '"'.mysql_escape_string($length).'", ';
            $SQLquery .= '"'.mysql_escape_string($mod_date).'", ';
            $SQLquery .= '"'.mysql_escape_string($filepath).'")';
            safe_mysql_query($SQLquery);
            
            $i++;
        }
    }
    closedir($folder);

/*  =========================  BUILD PODCAST XML  ==============================  */
    // create the new instance of the rss feed generator
    //    setup some basic info about the feed
    $rss = new UniversalFeedCreator();
    $rss->useCached();
    $rss->title = "Tech-Noid Systems Live Show Podcast";
    $rss->description = "An Online Resource Catering in All Things EDM";
    $rss->link = "http://www.tech-noid.net";
    $rss->syndicationURL = "http://radio.tech-noid.net/";

    // add some image and its info to the feed xml
    $image = new FeedImage();
    $image->title = "Tech-Noid Systems Logo";
    $image->url = "http://master.tech-noid.net/path/to/image.png";
    $image->link = "http://www.tech-noid.net";
    $image->description = "Feed provided by tech-noid.net. Click to visit.";
    $rss->image = $image;

    $result = mysql_query("SELECT * FROM podcasts ORDER BY date DESC", $connection);

    while ($data = mysql_fetch_array($result)) {
        $item = new FeedItem();
        $item->title = $data['artist'] . " - " . $data['title'];
        $item->link = $data['filepath'];
        $item->description = $data['description'];
        $item->date = $data['date'];
        $item->source = "http://www.tech-noid.net";
        $item->author = "Tech-Noid Systems";
        
        $item->enclosure = new EnclosureItem();
        $item->enclosure->url = str_replace('&', '&amp;', $data['filepath']);
        $item->enclosure->length = $data['length'];
        $item->enclosure->type = 'audio/x-mpeg';
        
        $rss->addItem($item);
    }

    $rss->saveFeed("RSS2.0", "D:/Content/SITE/archive.xml");

    // Close the database connection
    mysql_close($connection);
?>