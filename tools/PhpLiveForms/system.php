<?php
//2020.05.04.00
declare(strict_types = 1);
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
ini_set("error_reporting", "-1");
ini_set("html_errors", "1");
ini_set("max_execution_time", "15");
session_start();

require_once("GithubImport.php");
$GHI = new GithubImport;

$GHI->Get([
  "User" => "ProtocolLive",
  "Repo" => "PhpLive",
  "File" => "pdo.php"
]);
$PDO = new PhpLivePdo([
  "Ip" => "192.168.0.17",
  "User" => "root",
  "Pwd" => "teste",
  "Db" => "protocol",
  "Timeout" => 1
]);

$GHI->Get([
  "User" => "ProtocolLive",
  "Repo" => "PhpLive",
  "File" => "forms.php"
]);
$Forms = new PhpLiveForms($PDO);

$GHI->Get([
  "User" => "ProtocolLive",
  "Repo" => "Ajax",
  "File" => "Ajax.js",
  "IncludeType" => 2
]);