<?php
    $title = "Accueil";

    include_once "includes/db.php";
    include_once "includes/functions.php";

    if (!connecte()) {
        $_SESSION['flash']['danger'] = "Veuillez vous connecter pour accéder au site.";
        header('Location: connexion.php');
        die();
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Accueil</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li class="active">Accueil</li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <?php echo flash(); ?>
                        <?php if (statut('Administrateur')) : ?>
                            <div class="col-md-6">
                                <div class="white-box">
                                    <h3 class="box-title">Recherche</h3>
                                    <ul>
                                        <li><a href="ville_donnee.php">Ville</a></li>
                                        <li><a href="itineraire.php">Itinéraire</a></li>
                                        <li><a href="sca_donnee.php">SCA</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="white-box">
                                    <h3 class="box-title">Administration</h3>
                                    <ul>
                                        <li><a href="autoroute.php">Autoroutes</a></li>
                                        <li><a href="peage.php">Péages</a></li>
                                        <li><a href="fermeture.php">Registre de fermetures</a></li>
                                        <li><a href="sca.php">SCA</a></li>
                                        <li><a href="sortie.php">Sorties</a></li>
                                        <li><a href="troncon.php">Tronçons</a></li>
                                        <li><a href="ville.php">Ville</a></li>
                                    </ul>
                                </div>
                            </div>
                        <?php elseif (statut('Visiteur')) : ?>
                            <div class="col-md-12">
                                <div class="white-box">
                                    <h3 class="box-title">Recherche</h3>
                                    <ul>
                                        <li><a href="ville_donnee.php">Ville</a></li>
                                        <li><a href="itineraire.php">Itinéraire</a></li>
                                        <li><a href="sca_donnee.php">SCA</a></li>
                                    </ul>
                                </div>
                            </div>      
                        <?php endif; ?>
                    </div>
                </div>
<?php include_once "includes/footer.php"; ?>