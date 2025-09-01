<?php
include('db.php');

echo "<h2>Orlando International Resorts - Room Inventory</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>ID</th>";
echo "<th>Room Name</th>";
echo "<th>Base Price (KES)</th>";
echo "<th>Description</th>";
echo "<th>Status</th>";
echo "</tr>";

$query = "SELECT * FROM named_rooms ORDER BY base_price DESC";
$result = mysqli_query($query, "");

while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td><strong>" . $row['room_name'] . "</strong></td>";
    echo "<td>KES " . number_format($row['base_price'], 2) . "</td>";
    echo "<td>" . $row['description'] . "</td>";
    echo "<td>" . ($row['is_active'] ? 'Active' : 'Inactive') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><h3>Room Categories:</h3>";
echo "<ul>";

// Group rooms by price categories
$premium_rooms = [];
$standard_rooms = [];
$budget_rooms = [];
$economy_rooms = [];

$query = "SELECT * FROM named_rooms WHERE is_active = 1 ORDER BY base_price DESC";
$result = mysqli_query($query, "");

while($row = mysqli_fetch_assoc($result)) {
    if($row['base_price'] >= 3500) {
        $premium_rooms[] = $row['room_name'] . ' (KES ' . number_format($row['base_price']) . ')';
    } elseif($row['base_price'] >= 2000) {
        $standard_rooms[] = $row['room_name'] . ' (KES ' . number_format($row['base_price']) . ')';
    } elseif($row['base_price'] >= 1500) {
        $budget_rooms[] = $row['room_name'] . ' (KES ' . number_format($row['base_price']) . ')';
    } else {
        $economy_rooms[] = $row['room_name'] . ' (KES ' . number_format($row['base_price']) . ')';
    }
}

if(!empty($premium_rooms)) {
    echo "<li><strong>Premium Rooms:</strong> " . implode(', ', $premium_rooms) . "</li>";
}
if(!empty($standard_rooms)) {
    echo "<li><strong>Standard Rooms:</strong> " . implode(', ', $standard_rooms) . "</li>";
}
if(!empty($budget_rooms)) {
    echo "<li><strong>Budget Rooms:</strong> " . implode(', ', $budget_rooms) . "</li>";
}
if(!empty($economy_rooms)) {
    echo "<li><strong>Economy Rooms:</strong> " . implode(', ', $economy_rooms) . "</li>";
}

echo "</ul>";

mysqli_close($con);
?>
