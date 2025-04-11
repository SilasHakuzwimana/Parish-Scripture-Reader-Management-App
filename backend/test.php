<?php
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(true);
echo 'PHPMailer loaded successfully!';