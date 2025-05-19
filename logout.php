<?php
require_once 'includes/functions.php';

// Tuhotaan sessio
session_destroy();

// Ohjataan kirjautumissivulle
header("Location: login.php");
exit();
?>