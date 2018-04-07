<?php
    $title = "Itinéraire";

    include_once "includes/db.php";
    include_once "includes/functions.php";

    if (!connecte()) {
        $_SESSION['flash']['danger'] = "Veuillez vous connecter pour accéder au site.";
        header('Location: connexion.php');
        die();
    }

    // TRAITEMENT
    if (!empty($_POST)) { // RECHERCHER
        if (!empty($_POST['DNom']) && !empty($_POST['ANom'])) {
            // Vérifier que la ville de départ existe
            $request = $db->prepare("SELECT * FROM Ville WHERE Nom = :Nom");
            $request->execute(array(
                'Nom'   => $_POST['DNom']
            ));
            if (!$donnees = $request->fetch())
                $errors['DNom'] = "La ville de départ n'existe pas.";

            // Vérifier que la ville d'arrivée existe
            $request = $db->prepare("SELECT * FROM Ville WHERE Nom = :Nom");
            $request->execute(array(
                'Nom'   => $_POST['ANom']
            ));
            if (!$donnees = $request->fetch())
                $errors['ANom'] = "La ville d'arrivée n'existe pas.";
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: itineraire.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Itinéraire</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="itineraire.php" class="active">Itinéraire</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash(); ?>
                                <h3 class="box-title">Itinéraire</h3>
                                <form class="form-horizontal" action='itineraire.php' method="POST">
                                    <fieldset> 
                                        <legend></legend>
                                        <!-- Ville -->
                                        <div class="form-group <?php if (isset($errors['DNom'])) echo "has-error"; ?>">
                                            <label class="col-md-4 control-label" for="DNom">Nom de la ville de départ</label>  
                                            <div class="col-md-5">
                                                <input id="DNom" name="DNom" type="text" placeholder="Nom de la ville départ" class="form-control input-md" required="" value="<?php if (isset($_POST['DNom'])) echo strip_tags(htmlspecialchars($_POST['DNom'])); ?>">
                                                <span class="help-block">
                                                    <?php
                                                    if (isset($errors['DNom']))
                                                        echo strip_tags(htmlspecialchars($errors['DNom']));
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-group <?php if (isset($errors['ANom'])) echo "has-error"; ?>">
                                            <label class="col-md-4 control-label" for="ANom">Nom de la ville d'arrivée</label>  
                                            <div class="col-md-5">
                                                <input id="ANom" name="ANom" type="text" placeholder="Nom de la ville d'arrivée" class="form-control input-md" required="" value="<?php if (isset($_POST['ANom'])) echo strip_tags(htmlspecialchars($_POST['ANom'])); ?>">
                                                <span class="help-block">
                                                    <?php
                                                    if (isset($errors['ANom']))
                                                        echo strip_tags(htmlspecialchars($errors['ANom']));
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
                            if (!empty($_POST) && !empty($_POST['DNom']) && !empty($_POST['ANom'])) : ?>
                                <div class="col-md-12">
                                    <div class="white-box">
                                        <h3 class="box-title">Résultats de la recherche</h3>
                                        <?php // Rechercher l'ensemble des autoroutes et des sorties passant par la ville
                                        // Autoroutes non fermées de la ville de départ
                                        $request = $db->prepare("SELECT Ville.Nom, Ville.CodP, Troncon.CodA, Sortie.Libelle FROM Ville, Sortie, Troncon WHERE Ville.Nom = :DNom AND Ville.CodP = Sortie.CodP AND Sortie.CodT = Troncon.CodT AND Troncon.Num IS NULL");
                                        $request->execute(array(
                                            'DNom'  => $_POST['DNom']
                                        ));

                                        $i = 0;
                                        $autoroute = "";
                                        while ($data = $request->fetch()) {
                                            if ($i == 0)
                                                echo '<h3>Ville de départ : ' . strip_tags(htmlspecialchars($data['Nom'])) . ' - ' . strip_tags(htmlspecialchars($data['CodP'])) . '</h3>';
                                            
                                            if ($autoroute != $data['CodA'])
                                                echo '<h4>Autoroute : ' . strip_tags(htmlspecialchars($data['CodA'])) . '</h4>';
                                            echo '<p>Sortie : ' . strip_tags(htmlspecialchars($data['Libelle'])) . '</p>';

                                            $i++;
                                            $autoroute = $data['CodA'];
                                        }
                                        if ($i == 0)
                                            echo '<p>Aucun résultat trouvé</p>';

                                        // Autoroutes non fermées de la ville d'arrivée
                                        $request = $db->prepare("SELECT Ville.Nom, Ville.CodP, Troncon.CodA, Sortie.Libelle FROM Ville, Sortie, Troncon WHERE Ville.Nom = :DNom AND Ville.CodP = Sortie.CodP AND Sortie.CodT = Troncon.CodT AND Troncon.Num IS NULL");
                                        $request->execute(array(
                                            'DNom'  => $_POST['ANom']
                                        ));

                                        $i = 0;
                                        $autoroute = "";
                                        while ($data = $request->fetch()) {
                                            if ($i == 0)
                                                echo '<h3>Ville d\'arrivée : ' . strip_tags(htmlspecialchars($data['Nom'])) . ' - ' . strip_tags(htmlspecialchars($data['CodP'])) . '</h3>';
                                            
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