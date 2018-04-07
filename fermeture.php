<?php
    $title = "Registre de fermeture";

    include_once "includes/db.php";
    include_once "includes/functions.php";

    if (!connecte()) {
        $_SESSION['flash']['danger'] = "Veuillez vous connecter pour accéder au site.";
        header('Location: connexion.php');
        die();
    }

    if (!connecte() || !statut('Administrateur')) {
        $_SESSION['flash']['danger'] = "Vous ne pouvez pas accéder à cette page.";
        header('Location: index.php');
        die();
    }

    // VERIFICATIONS
    if (isset($_GET['action']) && $_GET['action'] != 'ajouter' && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer') {
        $_SESSION['flash']['danger'] = "Cette action n'existe pas.";
        header('Location: fermeture.php');
        die();
    }

    if (isset($_GET['Num'])) {
        // Existence
        $request = $db->prepare("SELECT * FROM Registre_fermeture WHERE Num = :Num");
        $request->execute(array(
            'Num'  => $_GET['Num']
        ));
        if (!$data = $request->fetch()) {
            $_SESSION['flash']['danger'] = "Cette fermeture n'existe pas.";
            header('Location: fermeture.php');
            die();
        }
    }

    if (isset($_GET['Num']) && isset($_GET['action']) && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer') {
        $_SESSION['flash']['danger'] = "Uniquement la modification et la suppression de cette société est autorisée.";
        header('Location: fermeture.php');
        die();
    }

    if (isset($_GET['Num']) && !isset($_GET['action'])) {
        $_SESSION['flash']['danger'] = "Des informations sont manquantes.";
        header('Location: fermeture.php');
        die();
    }

    // TRAITEMENT
    if (!isset($_GET['Num']) && isset($_GET['action']) && $_GET['action'] == 'ajouter' && !empty($_POST)) { // AJOUTER
        if (!empty($_POST['DateDebut']) && !empty($_POST['DateFin']) && !empty($_POST['Descriptif'])) {
            $errors = array();

            // Date de début
            list($jour, $mois, $annee) = explode('/', $_POST['DateDebut']);
            if (!checkdate($mois, $jour, $annee)) {
                $errors['DateDebut'] = "Le format de la date est invalide.";
            } else {
                $DateDebut = $annee . '-' . $mois . '-' . $jour;
            }

            // Date de fin
            list($jour, $mois, $annee) = explode('/', $_POST['DateFin']);
            if (!checkdate($mois, $jour, $annee)) {
                $errors['DateFin'] = "Le format de la date est invalide.";
            } else {
                $DateFin = $annee . '-' . $mois . '-' . $jour;
            }

            // Date de fin > date de début
            list($Dannee, $Dmois, $Djour) = explode('-', $DateDebut);
            list($Fannee, $Fmois, $Fjour) = explode('-', $DateFin);
            // Comparaison de l'année
            if ($Dannee > $Fannee) {
                $errors['DateDebut'] = "La date de début de fermeture doit être inférieure à celle de fin de fermeture.";
            } else { // Année Date Début <= Année Date Fin
                // Comparaison du mois
                if ($Dmois > $Fmois) {
                    $errors['DateDebut'] = "La date de début de fermeture doit être inférieure à celle de fin de fermeture.";
                } else { // Mois Date Début <= Mois Date Fin
                    // Comparaison du jour
                    if ($Djour >= $Fjour) {
                        $errors['DateDebut'] = "La date de début de fermeture doit être inférieure à celle de fin de fermeture.";
                    }
                }
            }

            // Descriptif
            if (strlen($_POST['Descriptif']) > 150)
                $errors['Descriptif'] = "Le descriptif de la fermeture est trop long (150 caractères maximum).";

            // Vérifier qu'il n'y ait pas déjà une fermeture identique
            $request = $db->prepare("SELECT * FROM Registre_fermeture WHERE Descriptif = :Descriptif AND DateDebut = :DateDebut AND DateFin = :DateFin");
            $request->execute(array(
                'Descriptif'    => $_POST['Descriptif'],
                'DateDebut'     => $DateDebut,
                'DateFin'       => $DateFin
            ));
            if ($donnees = $request->fetch())
                $errors['Descriptif'] = "Une fermeture identique existe déjà.";

            if (empty($errors)) {
                $request = $db->prepare("INSERT INTO Registre_fermeture(DateDebut, DateFin, Descriptif) VALUES(:DateDebut, :DateFin, :Descriptif)");
                $request->execute(array(
                    'DateDebut'     => $DateDebut, 
                    'DateFin'       => $DateFin,
                    'Descriptif'    => $_POST['Descriptif']
                ));

                $_SESSION['flash']['success'] = "La fermeture a été ajoutée avec succès.";
                header('Location: fermeture.php');
                die();
            }
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: fermeture.php?action=ajouter');
            die();
        }
    } else if (isset($_GET['Num']) && isset($_GET['action']) && $_GET['action'] == 'modifier' && !empty($_POST)) { // MODIFIER
        $errors = array();
        $inputs = array();

        $inputs = $data;

        // Date de début
        list($jour, $mois, $annee) = explode('/', $_POST['DateDebut']);
        if (!checkdate($mois, $jour, $annee)) {
            $errors['DateDebut'] = "Le format de la date est invalide.";
        } else {
            $DateDebut = $annee . '-' . $mois . '-' . $jour;
        }

        if (!empty($DateDebut) && strcmp($DateDebut, $data['DateDebut']) !== 0) {
            if (!isset($errors['DateDebut']))
                $inputs['DateDebut'] = $DateDebut;
        }

        // Date de fin
        list($jour, $mois, $annee) = explode('/', $_POST['DateFin']);
        if (!checkdate($mois, $jour, $annee)) {
            $errors['DateFin'] = "Le format de la date est invalide.";
        } else {
            $DateFin = $annee . '-' . $mois . '-' . $jour;
        }

        if (!empty($DateFin) && strcmp($DateFin, $data['DateFin']) !== 0) {
            if (!isset($errors['DateFin']))
                $inputs['DateFin'] = $DateFin;
        }

        // Date de fin > date de début
        list($Dannee, $Dmois, $Djour) = explode('-', $DateDebut);
        list($Fannee, $Fmois, $Fjour) = explode('-', $DateFin);
        // Comparaison de l'année
        if ($Dannee > $Fannee) {
            $errors['DateDebut'] = "La date de début de fermeture doit être inférieure à celle de fin de fermeture.";
        } else { // Année Date Début <= Année Date Fin
            // Comparaison du mois
            if ($Dmois > $Fmois) {
                $errors['DateDebut'] = "La date de début de fermeture doit être inférieure à celle de fin de fermeture.";
            } else { // Mois Date Début <= Mois Date Fin
                // Comparaison du jour
                if ($Djour >= $Fjour) {
                    $errors['DateDebut'] = "La date de début de fermeture doit être inférieure à celle de fin de fermeture.";
                }
            }
        }

        // Descriptif
        if (!empty($_POST['Descriptif']) && strcmp($_POST['Descriptif'], $data['Descriptif']) !== 0) {
            if (strlen($_POST['Descriptif']) > 150)
                $errors['Descriptif'] = "Le descriptif de la fermeture est trop long (150 caractères maximum).";

            if (!isset($errors['Descriptif']))
                $inputs['Descriptif'] = $_POST['Descriptif'];
        }

        // Vérifier qu'il n'y ait pas déjà une fermeture identique
        $request = $db->prepare("SELECT * FROM Registre_fermeture WHERE Descriptif = :Descriptif AND DateDebut = :DateDebut AND DateFin = :DateFin AND Num <> :Num");
        $request->execute(array(
            'Descriptif'    => $inputs['Descriptif'],
            'DateDebut'     => $inputs['DateDebut'],
            'DateFin'       => $inputs['DateFin'],
            'Num'           => $inputs['Num']
        ));
        if ($donnees = $request->fetch())
            $errors['Descriptif'] = "Une fermeture identique existe déjà.";

        // Erreur
        if (empty($errors)) {
            $request = $db->prepare('UPDATE Registre_fermeture SET DateDebut = :DateDebut, DateFin = :DateFin, Descriptif = :Descriptif WHERE Num = :Num');
            $request->execute(array(
                'DateDebut'     => $inputs['DateDebut'],
                'DateFin'       => $inputs['DateFin'],
                'Descriptif'    => $inputs['Descriptif'],
                'Num'           => $inputs['Num']
            ));

            $data = $inputs;
            $_SESSION['flash']['success'] = "La fermeture a été modifiée avec succès.";
            header('Location: fermeture.php');
            die();
        }
    } else if (isset($_GET['Num']) && isset($_GET['action']) && $_GET['action'] == 'supprimer' && !empty($_POST)) {
        if (isset($_POST['oui'])) {
            // Modifier les tronçons fermés
            $request = $db->prepare('UPDATE Troncon SET Num = NULL WHERE Num = :Num');
            $request->execute(array(
                'Num'   => $data['Num']
            ));

            // Suppression de la fermeture
            $request = $db->prepare("DELETE FROM Registre_fermeture WHERE Num = :Num");
            $request->execute(array(
                'Num'  => $data['Num']
            ));

            $_SESSION['flash']['success'] = "La fermeture a été supprimée avec succès.";
            header('Location: fermeture.php');
            die();
        }

        if (isset($_POST['non'])) {
            header('Location: fermeture.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Registre de fermeture</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="fermeture.php" class="active">Registre de fermeture</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash();
                                if (!isset($_GET['Num']) && isset($_GET['action']) && $_GET['action'] == 'ajouter') : ?>
                                    <h3 class="box-title">Ajout d'une fermeture</h3>
                                    <form class="form-horizontal" action='fermeture.php?action=ajouter' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Date de début -->
                                            <div class="form-group <?php if (isset($errors['DateDebut'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="DateDebut">Date de début de fermeture</label>  
                                                <div class="col-md-5">
                                                    <input id="DateDebut" name="DateDebut" type="text" placeholder="jj/mm/aa" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['DateDebut']))
                                                            echo strip_tags(htmlspecialchars($errors['DateDebut']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Date de fin -->
                                            <div class="form-group <?php if (isset($errors['DateFin'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="DateFin">Date de fin de fermeture</label>  
                                                <div class="col-md-5">
                                                    <input id="DateFin" name="DateFin" type="text" placeholder="jj/mm/aa" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['DateFin']))
                                                            echo strip_tags(htmlspecialchars($errors['DateFin']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Descriptif -->
                                            <div class="form-group <?php if (isset($errors['Descriptif'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Descriptif">Descriptif de la fermeture</label>  
                                                <div class="col-md-5">
                                                    <input id="Descriptif" name="Descriptif" type="text" placeholder="Descriptif de la fermeture" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Descriptif']))
                                                            echo strip_tags(htmlspecialchars($errors['Descriptif']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Button -->
                                            <div class="form-group text-center">
                                                <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                    <input type="submit" class="btn btn-success btn-block" value="Ajouter" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                <?php elseif (isset($_GET['Num']) && isset($_GET['action']) && $_GET['action'] == 'modifier') : ?>
                                    <h3 class="box-title">Modification de la fermeture : <?php echo strip_tags(htmlspecialchars($data['Descriptif'])); ?></h3>
                                    <form class="form-horizontal" action='fermeture.php?Num=<?php echo $data['Num']; ?>&action=modifier' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <?php
                                            list($Dannee, $Dmois, $Djour) = explode('-', $data['DateDebut']);
                                            $DateDebut = $Djour . '/' . $Dmois . '/' . $Dannee;
                                            list($Fannee, $Fmois, $Fjour) = explode('-', $data['DateFin']);
                                            $DateFin = $Fjour . '/' . $Fmois . '/' . $Fannee;
                                            ?>
                                            <!-- Date de début -->
                                            <div class="form-group <?php if (isset($errors['DateDebut'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="DateDebut">Date de début de fermeture</label>  
                                                <div class="col-md-5">
                                                    <input id="DateDebut" name="DateDebut" type="text" placeholder="jj/mm/aa" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($DateDebut)); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['DateDebut']))
                                                            echo strip_tags(htmlspecialchars($errors['DateDebut']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Date de fin -->
                                            <div class="form-group <?php if (isset($errors['DateFin'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="DateFin">Date de fin de fermeture</label>  
                                                <div class="col-md-5">
                                                    <input id="DateFin" name="DateFin" type="text" placeholder="jj/mm/aa" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($DateFin)); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['DateFin']))
                                                            echo strip_tags(htmlspecialchars($errors['DateFin']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Descriptif -->
                                            <div class="form-group <?php if (isset($errors['Descriptif'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Descriptif">Descriptif de la fermeture</label>  
                                                <div class="col-md-5">
                                                    <input id="Descriptif" name="Descriptif" type="text" placeholder="Descriptif de la fermeture" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['Descriptif'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Descriptif']))
                                                            echo strip_tags(htmlspecialchars($errors['Descriptif']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Button -->
                                            <div class="form-group text-center">
                                                <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                    <input type="submit" class="btn btn-success btn-block" value="Modifier" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                <?php elseif (isset($_GET['Num']) && isset($_GET['action']) && $_GET['action'] == 'supprimer') : ?>
                                    <h3 class="box-title">Suppression de la fermeture : <?php echo strip_tags(htmlspecialchars($data['Descriptif'])); ?></h3>
                                    <div class="row text-center">
                                        <p>Êtes-vous sûr de vouloir supprimer cette fermeture ?</p>
                                        <form enctype="multipart/form-data" class="form-horizontal" method="post" action="fermeture.php?Num=<?php echo $data['Num']; ?>&action=supprimer">
                                            <fieldset>
                                                <div class="form-group text-center">
                                                    <div class="col-xs-6 col-sm-offset-2 col-sm-4 col-md-offset-3 col-md-3 col-lg-offset-3 col-lg-3">
                                                        <button name="oui" class="btn btn-success btn-block">Oui</button>
                                                    </div>
                                                    <div class="col-xs-6 col-sm-4 col-md-3 col-lg-3">
                                                        <button name="non" class="btn btn-success btn-block">Non</button>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </form>
                                    </div>
                                <?php elseif (!isset($_GET['Num']) && !isset($_GET['action'])) : ?>
                                    <h3 class="box-title">Registre de fermetures <a href="fermeture.php?action=ajouter"><span class="glyphicon glyphicon-plus pull-right"></span></a></h3>
                                    <div class="row text-center">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="col-lg-2 text-center">Date de début</th>
                                                    <th class="col-lg-2 text-center">Date de fin</th>
                                                    <th class="col-lg-6 text-center">Descriptif</th>
                                                    <th class="col-lg-2 text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $request = $db->query("SELECT COUNT(Num) AS nbFermeture FROM Registre_fermeture");
                                                $data = $request->fetch();

                                                $nbFermeture = $data['nbFermeture'];
                                                $perPage = 5;
                                                $nbPage = ceil($nbFermeture / $perPage);

                                                if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbPage)
                                                    $cPage = $_GET['page'];
                                                else
                                                    $cPage = 1;

                                                $request = $db->query("SELECT * FROM Registre_fermeture ORDER BY DateDebut, DateFin DESC LIMIT " . (($cPage - 1) * $perPage) . ", $perPage");
                                                while ($data = $request->fetch()) {
                                                    list($Dannee, $Dmois, $Djour) = explode('-', $data['DateDebut']);
                                                    $DateDebut = $Djour . '/' . $Dmois . '/' . $Dannee;
                                                    list($Fannee, $Fmois, $Fjour) = explode('-', $data['DateFin']);
                                                    $DateFin = $Fjour . '/' . $Fmois . '/' . $Fannee;

                                                    echo '<tr>
                                                    <td>' . strip_tags(htmlspecialchars($DateDebut)) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($DateFin)) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['Descriptif'])) . '</td>
                                                    <td><a href="fermeture.php?Num=' . $data['Num'] . '&action=modifier"><span class="glyphicon glyphicon-pencil"></span></a>
                                                    <a href="fermeture.php?Num=' . $data['Num'] . '&action=supprimer"><span class="glyphicon glyphicon-remove"></span></a>
                                                    </td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row text-center">
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination">
                                            <?php
                                                for ($i = 1; $i <= $nbPage; $i++) {
                                                    if ($i == $cPage)
                                                        echo '<li class="active"><a href="fermeture.php?page=' . $i . '">' . $i . '</a></li>';
                                                    else
                                                        echo '<li><a href="fermeture.php?page=' . $i . '">' . $i . '</a></li>';
                                                }
                                            ?>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
<?php include_once "includes/footer.php"; ?>