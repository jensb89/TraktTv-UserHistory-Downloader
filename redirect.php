<?php
/* This is just needed initially to get an AccessToken / code the first time */
if (isset($_POST) && !empty($_POST)) {
    print_r($_POST);   
}else{  
    echo "post Not set";
}
if (isset($_GET) && !empty($_GET)) {
    print_r($_GET);   
}else{  
    echo "Not set";
}
?>