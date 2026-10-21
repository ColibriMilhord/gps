<?php
require_once __DIR__ . '/_layout.php';
session_demarrer();
$_SESSION = [];
session_destroy();
rediriger('login.php');
