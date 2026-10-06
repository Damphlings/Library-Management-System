<?php

echo "Student Password Hash:<br>";
echo password_hash('student123', PASSWORD_DEFAULT);

echo "<br><br>";

echo "Employee Password Hash:<br>";
echo password_hash('employee123', PASSWORD_DEFAULT);
?>