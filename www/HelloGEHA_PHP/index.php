<?php

    header('Content-type: text/html; charset=utf-8');

    /* Login class */
    require_once("Controller/LoginProc.php");

    /* make instance of Login class (It also runs a login process too!) */
    $login = new Login();

    /* Login stat checking */
    if ($login->chkLoginStat() == true) {

        echo "Logged in";

    } else {

        echo "Not Logged in";

    }

?>


