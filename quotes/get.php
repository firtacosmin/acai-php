<?php

include "quote_class.php";

$quotes = array( 
new Quote("message 1", "author 1", 1900), 
new Quote( "message 2", "author 2", 1910 ), 
new Quote( "message 3", "author 3", 1920 ),
new Quote("message 4", "author 4", 1930), 
new Quote( "message 5", "author 5", 1940 ), 
new Quote( "message 6", "author 6", 1950 ),
new Quote("message 7", "author 7", 1960), 
new Quote( "message 8", "author 8", 1970 ), 
new Quote( "message 9", "author 9", 1980 )  );

//print_r($quotes);

$pos = rand(0,count($quotes)-1);



//echo "position:";
//echo $pos;
//echo "</br>";
//echo $quotes[$pos]->getQuote();
//echo "</br>";

$myJSON = json_encode($quotes[$pos]);
//echo "JSON: </br>";
//echo $quotes[$pos]->toJson();
echo $myJSON;



?>
