<?php
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=\"liste_presence.csv\"");

$csvFile = fopen("data.csv", "r");
fpassthru($csvFile);
fclose($csvFile);
?>
