<?php
// pages/auth/logout.php
session_start();
require_once '../../engine/core/Auth.php';

Auth::logout();