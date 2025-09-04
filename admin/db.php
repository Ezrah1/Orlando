<?php
$con = mysqli_connect("localhost","root","","hotel") or die(mysql_error());

// Set charset to utf8mb4 for proper emoji support
mysqli_set_charset($con, "utf8mb4");

?>