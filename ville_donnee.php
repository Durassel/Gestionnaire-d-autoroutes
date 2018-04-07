<?php
    $title = "Ville";

    include_once "includes/db.php";
    include_once "includes/functions.php";

    if (!connecte()) {
        $_SESSION['flash']['danger'] = "Veuillez vous connecter pour accéder au site.";
        header('Location: connexion.php');
        die();
    }

    // TRAITEMENT
    if (!empty($_POST)) { // RECHERCHER
        if (!empty($_POST['Nom'])) {
            // Vérifier que la ville existe
            $request = $db->prepare("SELECT * FROM Ville WHERE Nom = :Nom");
            $request->execute(array(
                'Nom'   => $_POST['Nom']
            ));
            if (!$donnees = $request->fetch())
                $errors['Nom'] = "Cette ville n'existe pas.";
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: ville_donnee.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Ville</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="ville_donnee.php" class="active">Ville</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash(); ?>
                                <h3 class="box-title">Autoroutes et sorties d'une ville</h3>
                                <form class="form-horizontal" action='ville_donnee.php' method="POST">
                                    <fieldset> 
                                        <legend></legend>
                                        <!-- Ville -->
                                        <div class="form-group <?php if (isset($errors['Nom'])) echo "has-error"; ?>">
                                            <label class="col-md-4 control-label" for="Nom">Nom de la ville</label>  
                                            <div class="col-md-5">
                                                <input id="Nom" name="Nom" type="text" placeholder="Nom de la ville" class="form-control input-md" required="">
                                                <span class="help-block">
                                                    <?php
                                                    if (isset($errors['Nom']))
                                                        echo strip_tags(htmlspecialchars($errors['Nom']));
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Button -->
                                        <div class="form-group text-center">
                                            <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                <input type="submit" class="btn btn-success btn-block" value="Chercher" />
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                        <?php
                        if (empty($errors)) :
                            if (!empty($_POST) && !empty($_POST['Nom'])) : ?>
                                <div class="col-md-12">
                                    <div class="white-box">
                                        <h3 class="box-title">Résultats de la recherche</h3>
                                        <?php // Rechercher l'ensemble des autoroutes et des sorties passant par la ville
                                        $request = $db->prepare("SELECT Ville.CodP, Ville.Nom, Sortie.Libelle, Troncon.CodA FROM Ville, Sortie, Troncon WHERE Ville.Nom = :Nom AND Ville.CodP = Sortie.CodP AND Sortie.CodT = Troncon.CodT ORDER BY Troncon.CodA, Sortie.CodP, Ville.Nom");
                                        $request->execute(array(
                                            'Nom'  => $_POST['Nom']
                                        ));

                                        $i = 0;
                                        $autoroute = "";
                                        while ($data = $request->fetch()) {
                                            if ($i == 0)
                                                echo '<h3>Ville : ' . strip_tags(htmlspecialchars($data['Nom'])) . ' - ' . strip_tags(htmlspecialchars($data['CodP'])) . '</h3>';
                                            
                                            if ($autoroute != $data['CodA'])
                                                echo '<h4>Autoroute : ' . strip_tags(htmlspecialchars($data['CodA'])) . '</h4>';
                                            echo '<p>Sortie : ' . strip_tags(htmlspecialchars($data['Libelle'])) . '</p>';

                                            $i++;
                                            $autoroute = $data['CodA'];
                                        }
                                        if ($i == 0)
                                            echo '<p>Aucun résultat trouvé</p>';
                                        ?>
                                </div>
                            </div>
                            <?php endif;
                        endif; ?>
                    </div>
                </div>
<?php include_once "includes/footer.php"; ?>