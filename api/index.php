<?php
// Redirect to home page if someone tries to access /api/ directly
header('Location: ../index.php');
exit;
