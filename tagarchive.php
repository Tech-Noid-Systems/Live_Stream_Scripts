<?php
/*
    tagarchive.php
    -- script to rename the archives from the station
        this will be fired from the livefeed PAL script in SAM

    coded by:	Aaron Cupp (disfigure at tech-noid.net)
    date:	Sept 20, 2008
	modified : Apr 13, 2010
    version:	1.0.1
*/

    # Open the database connection to SAM
    $connection = mysql_connect("master.tech-noid.net", "", "");
    mysql_select_db("samdb", $connection);

    /* 
    Run the query on the songlist & queuelist via SAM db connection..returns the tracks in the queue
    We limit the query and return the last played track plus 10 more   
    */
    $result = mysql_query ("SELECT * FROM djqueue, live_show_dates WHERE (djqueue.id = live_show_dates.showid) ORDER BY id DESC LIMIT 1", $connection);

    while ($row = mysql_fetch_array($result)) {
        $id = $row["id"];
        $artist = $row["artist"];
        $title = $row["title"];
        $date_played = $row["date"];
    }

    # build the variables for the id3
    $tagTitle = $title . " " . date("m-d-Y");
    $tagArtist = $artist;
    $tagAlbum = 'Tech-Noid Systems Radio Archives';
    $tagYear = date("Y");
    $tagGenre = 'Various';
    $tagComment = 'Recorded Live on tech-noid.net ' . $date_played . ' PST';

    #$TaggingFormat = 'UTF-8';

	
    # Initialize getID3 engine
    include('D:/Content/SITE/station/includes/getid3/getid3.php');
    $getID3 = new getID3;
    #$getID3->setOption(array('encoding'=>$TaggingFormat));
    include('D:/Content/SITE/station/includes/getid3/write.php');

    # Initialize getID3 tag-writing module
    $tagwriter = new getid3_writetags;
    $tagwriter->filename       = 'D:\\Content\\FEED\\stream.mp3';
    $tagwriter->tagformats     = array('id3v2.3');

    # set various options (optional)
    $tagwriter->overwrite_tags = true;
    #$tagwriter->tag_encoding   = $TaggingFormat;
    $tagwriter->remove_other_tags = true;

    # populate data array
    $TagData['title'][]   = $tagTitle;
    $TagData['artist'][]  = $tagArtist;
    $TagData['album'][]   = $tagAlbum;
    $TagData['year'][]    = $tagYear;
    $TagData['genre'][]   = $tagGenre;
    $TagData['comment'][] = $tagComment;

    $tagwriter->tag_data = $TagData;

    # write tags
    if ($tagwriter->WriteTags()) {
    	echo 'Successfully wrote tags<br />';
	if (!empty($tagwriter->warnings)) {
	   echo 'There were some warnings:<br />'.implode('<br /><br />', $tagwriter->warnings);
	}
    } else {
	echo 'Failed to write tags!<br />'.implode('<br /><br />', $tagwriter->errors);
    }

    echo $tagTitle . ' - ' . $tagArtist . ' - ' . $tagAlbum . ' - ' . $tagYear . ' - ' . $tagGenre . ' - ' . $tagComment;

    # Close the database connection
    mysql_close($connection);
?>