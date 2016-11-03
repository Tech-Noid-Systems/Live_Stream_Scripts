<?php
/*
	SAM scripts for techno-dnb.com
	--  various functions to pull info from the station dtatabases

	coded by:	Aaron Cupp (disfigure at techno-dnb.com)
	date:		Sept 07, 2008
	version:	1.0
*/




/*
	Show what is playing on the station right now
 */

function SAMradio_playing() {
	// Open the database connection to SAM
   	$connection = mysql_connect("111.222.333.444", "stream1", "t3chn0-dnb");
	mysql_select_db("samdb1", $connection);

	/* 
	Run the query on the songlist & queuelist via SAM db connection..returns the tracks in the queue
	We limit the query and return the last played track plus 10 more   
	*/
   	$result = mysql_query ("SELECT artist, title, duration, date_played FROM historylist ORDER BY date_played DESC LIMIT 1", $connection);
	$result2 = mysql_query ("SELECT viewers FROM relay_counts ORDER BY id DESC LIMIT 1", $connection);
	
	// While there are still rows in the result set, fetch the current row into the array $row
	while ($row = mysql_fetch_array($result)) {
		$row2 = mysql_fetch_array($result2);
		
		$artist = $row["artist"];
		$title = $row["title"];
		$listeners = $row2["viewers"];
		
		//  <div style=\"background-color:#444444\" style=\"padding:6px;\">
		echo "<body bgcolor=\"#444444\"><font size=\"-1\" face=\"arial\" color=\"#FFFFFF\">Now Playing:</font> <font size=\"-1\" face=\"arial\" color=\"#ffb500\"><b>$artist - $title</b></font><font size=\"-1\" face=\"arial\" color=\"#FFFFFF\"> | </font><font size=\"-1\" face=\"arial\" color=\"#ffb500\">$listeners</font><font size=\"-1\" face=\"arial\" color=\"#FFFFFF\"> tuned in<br></font></body>";
	}      
   	// Close the database connection
	mysql_close($connection);
}

/*
	Show the station queue
*/
function SAMradio_queue() {
	// Open the database connection to SAM
   	$connection = mysql_connect("111.222.333.444", "stream1", "t3chn0-dnb");
	mysql_select_db("samdb1", $connection);

	// Run the query on the winestore through the connection
   	$result = mysql_query ("SELECT songlist.*, queuelist.requestID as requestID FROM queuelist, songlist WHERE (queuelist.songID = songlist.ID) ORDER BY queuelist.sortID ASC LIMIT 10", $connection);
	
	// While there are still rows in the result set, fetch the current row into the array $row
	while ($row = mysql_fetch_array($result)) {
		$artist = $row["artist"];
		$title = $row["title"];
		echo "$artist - $title <br>";
	}
   	// Close the database connection
	mysql_close($connection);
}


/*
	Return the recent tracks to be played on the station
*/
function SAMradio_recent() {
	// Open the database connection to SAM
   	$connection = mysql_connect("111.222.333.444", "stream1", "t3chn0-dnb");
	mysql_select_db("samdb1", $connection);

	// Run the query on the winestore through the connection
   	$result = mysql_query ("SELECT artist, title FROM historylist ORDER BY date_played DESC LIMIT 1,10", $connection);
	
	// While there are still rows in the result set, fetch the current row into the array $row
	while ($row = mysql_fetch_array($result)) {
		$artist = $row["artist"];
		$title = $row["title"];
		echo "$artist - $title <br>";
	}
   	// Close the database connection
	mysql_close($connection);
}




?>
