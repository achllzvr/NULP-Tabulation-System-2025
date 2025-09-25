<?php
require __DIR__.'/includes/bootstrap.php';
auth_logout();
header('Location: login.php?logged_out=1');
exit;
