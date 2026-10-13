<?php
include 'connectdb.php';
session_start();

if (isset($_POST["username"]) && isset($_POST["password"]))
{
    $username = $_POST["username"];
    $password = $_POST["password"];

    $query = " SELECT * from user WHERE
                username='$username' and password=md5('$password')";

    $result = mysqli_query($dataconn, $query);

    if(mysqli_num_rows($result)>0)
    {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['fullname'] = $row['fullname'];
        $_SESSION['id'] = $row['id'];
        $_SESSION['role'] = $row['role'];  
        $id = $row['id'];
        $role = $row['role'];

    echo "<p> Welcome ".$_SESSION['fullname']." to our page<br>";

    $user_role = ($role==1)? "Admin":"Parent";
    echo "Role: $user_role";

    }

    else
    {
        echo '<script type="text/javascript">alert("Your username and password do not match");</script>';
        header("Location: login_page.php?error=1");
        exit();
    }
}

?>