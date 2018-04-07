<?php
	include_once "includes/db.php";
  	include_once "includes/functions.php"; 

	if (!connecte()) {
		header('Location: connexion.php');
		die();
	}

	unset($_SESSION['auth']);
	$_SESSION['flash']['success'] = "Vous êtes maintenant déconnecté.";
	header('Location: connexion.php');
	die();
?>