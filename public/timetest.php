<?php
	echo date_default_timezone_get();
	echo "<br/>";
	echo date('Y-m-d H:i:s');
	echo "<br/>";
	$date = new \DateTime('NOW',new \DateTimeZone('Asia/Kolkata'));
      //$date->setTimezone(new \DateTimeZone($timezone_to));
    echo $date->format('Y-m-d H:i:s');

?>