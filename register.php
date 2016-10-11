<?php
if(empty($_POST)){
    exit();
}
else{
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];
    $country = $_POST['country'];
    $message = $_POST['message'];

    print "<table> $first_name</br> $last_name</br>$telephone</br>$email</br>$country</br>$message</br></table>";
}

?>