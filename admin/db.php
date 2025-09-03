<?php
// InfinityFree Database Configuration
$con = mysqli_connect("sql300.infinityfree.com", "if0_39842749", "6LLK5O9akZAKiZi", "if0_39842749_XXX") or die(mysql_error());

// Set charset to utf8mb4 for proper emoji support
mysqli_set_charset($con, "utf8mb4");

?>