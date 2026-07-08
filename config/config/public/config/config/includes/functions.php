<?php

function clean($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isSeller()
{
    return isset($_SESSION['role']) && $_SESSION['role'] == 'seller';
}

function isBuyer()
{
    return isset($_SESSION['role']) && $_SESSION['role'] == 'buyer';
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function redirect($url)
{
    header("Location: ".$url);
    exit;
}

function generateOrderNo()
{
    return "DM".date("YmdHis").rand(1000,9999);
}
