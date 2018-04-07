<?php
    $title = "SCA";

    include_once "includes/db.php";
    include_once "includes/functions.php";

    if (!connecte()) {
        $_SESSION['flash']['danger'] = "Veuillez vous connecter pour accéder au site.";
        header('Location: connexion.php');
        die();
    }

    // TRAITEMENT
    if (!empty($_POST)) { // RECHERCHER
        if (!empty($_POST['Code'])) {
            // Vérifier que la société existe
            $request = $db->prepare("SELECT * FROM Sca WHERE Code = :Code");
            $request->execute(array(
                'Code'   => $_POST['Code']
            ));
            if (!$donnees = $request->fetch())
                $errors['Code'] = "Cette société n'existe pas.";
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: sca_donnee.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">SCA</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="sca_donnee.php" class="active">SCA</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash(); ?>
                                <h3 class="box-title">Gestion de péages par une SCA</h3>
                                <form class="form-horizontal" action='sca_donnee.php' method="POST">
                                    <fieldset> 
                                        <legend></legend>
                                        <!-- Société -->
                                        <div class="form-group <?php if (isset($errors['Code'])) echo "has-error"; ?>">
                                            <label class="col-md-4 control-label" for="Code">Nom de la société</label>  
                                            <div class="col-md-5">
                                                <select id="Code" name="Code" class="form-control">
                                                <?php
                                                    $request = $db->query("SELECT * FROM Sca");
                                                    while ($donnees = $request->fetch()) {
                                                        echo '<option value="' . $donnees['Code'] . '" ';
                                                        if (isset($_POST['Code']) && $_POST['Code'] == $donnees['Code'])
                                                            echo 'selected="selected"';
                                                        echo '>' . strip_tags(htmlspecialchars($donnees['Nom'])) . '</option>';
                                                    }
                                                ?>
                                                </select>
                                                <span class="help-block">
                                                    <?php
                                                    if (isset($errors['Code']))
                                                        echo strip_tags(htmlspecialchars($errors['Code']));
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
                            if (!empty($_POST) && !empty($_POST['Code'])) : ?>
                                <div class="col-md-12">
                                    <div class="white-box">
                                        <h3 class="box-title">Résultats de la recherche</h3>
                                        <?php // Rechercher l'ensemble des péages gérer par la société
                                        $request = $db->prepare("SELECT * FROM Sca, Peage WHERE Sca.Code = :Code AND Sca.Code = Peage.Code ORDER BY Peage.CodA, Peage.PGDuKm, Peage.PGAuKm, Peage.Tarif");
                                        $request->execute(array(
                                            'Code'  => $_POST['Code']
                                        ));

                                        $i = 0;
                                        $autoroute = "";
                                        while ($data = $request->fetch()) {
                                            if ($i == 0) {
                                                echo '<h3>Société : ' . strip_tags(htmlspecialchars($data['Nom'])) . '</h3>';
                                                echo '<h4>Durée du contrat : ' . strip_tags(htmlspecialchars($data['Duree_Contrat'])) . ' ans / Chiffre d\'affaires : ' . strip_tags(htmlspecialchars($data['CA'])) . '€</h4>';
                                            }
                                            
                                            if ($autoroute != $data['CodA'])
                                                echo '<h4>Autoroute : ' . strip_tags(htmlspecialchars($data['CodA'])) . '</h4>';
                                            echo '<p>Péage : ' . strip_tags(htmlspecialchars($data['PGDuKm'])) . ' - ' . strip_tags(htmlspecialchars($data['PGAuKm'])) . ' km : Tarif : ' . strip_tags(htmlspecialchars($data['Tarif'])) . '</p>';

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