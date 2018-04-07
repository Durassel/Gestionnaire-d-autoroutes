<?php
session_start();

try {
	$db = new PDO('mysql:host=localhost;dbname=bdd;charset=utf8', 'root', '');
} catch (Exception $e) {
	die('Erreur : ' . $e->getMessage());
}

$grain = "5gwn9ci2eax";
$salt = "nd0fbw65tsv";