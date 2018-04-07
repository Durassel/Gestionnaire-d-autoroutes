<?php
include_once "includes/db.php";

function flash() {
	if (isset($_SESSION['flash'])) {
		foreach ($_SESSION['flash'] as $type => $message) {
			echo '<div class="alert alert-' . $type . '" role="alert">' . $message . '</div>';
		}
		unset($_SESSION['flash']);
	}
}


function connecte() {
	if (isset($_SESSION['auth'])) {
		return true;
	} else {
		return false;
	}
}

function statut($nom) {
	if (!connecte()) {
		return false;
	}

	if ($_SESSION['auth']['Statut'] == $nom) {
		return true;
	} else {
		return false;
	}
}
?>