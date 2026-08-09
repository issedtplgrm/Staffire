<?php


//get user_role
function getRole()
{
    return $_SESSION["role"] ?? null;
}

//check role
function isRole($role)
{
    return isset($_SESSION["role"])
        && $_SESSION["role"] === $role;
}


//admin access

function requireAdmin()
{
   
    if (!isRole("admin")) {

        header("Location: ../pages/dashboard.php");
        exit();
    }
}


//admin and manager

function requireManagement()
{


    if (
        !isRole("admin") &&
        !isRole("manager")  
    ) {

        header("Location: ../pages/dashboard.php");
        exit();
    }
}

?>