<?php
    $title = "Tronçon";

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
        header('Location: troncon.php');
        die();
    }

    if (isset($_GET['CodT'])) {
        // Existance
        $request = $db->prepare("SELECT Troncon.CodT, Troncon.CodA, Troncon.Num, Troncon.DuKm AS DuKmT, Troncon.AuKm AS AuKmT, Autoroute.DuKm AS DuKmA, Autoroute.AuKm AS AuKmA FROM Troncon, Autoroute WHERE Troncon.CodT = :CodT AND Troncon.CodA = Autoroute.CodA");
        $request->execute(array(
            'CodT'  => $_GET['CodT']
        ));
        if (!$data = $request->fetch()) {
            $_SESSION['flash']['danger'] = "Ce tronçon n'existe pas.";
            header('Location: troncon.php');
            die();
        }
    }

    if (isset($_GET['CodT']) && isset($_GET['action']) && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer') {
        $_SESSION['flash']['danger'] = "Uniquement la modification et la suppression de ce tronçon est autorisée.";
        header('Location: troncon.php');
        die();
    }

    if (isset($_GET['CodT']) && !isset($_GET['action'])) {
        $_SESSION['flash']['danger'] = "Des informations sont manquantes.";
        header('Location: troncon.php');
        die();
    }

    // TRAITEMENT
    if (!isset($_GET['CodT']) && isset($_GET['action']) && $_GET['action'] == 'ajouter' && !empty($_POST)) { // AJOUTER
        if (isset($_POST['Etat'])) {
            $ide = $_POST['Etat'];
        } else {
            $ide = null;
        }

        if (!empty($_POST['CodA']) && !empty($_POST['DuKm']) && !empty($_POST['AuKm']) && !empty($_POST['Etat']) && !empty($_POST['valider'])) {
            $errors = array();

            // Autoroute
            // Existence
            $request = $db->prepare("SELECT * FROM Autoroute WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'   => $_POST['CodA']
            ));
            if (!$donnees = $request->fetch())
                $errors['CodA'] = "Cette autoroute n'existe pas.";

            // Début de kilométrage du tronçon
            if (!is_numeric($_POST['DuKm']))
                $errors['DuKm'] = "Le début du kilométrage du tronçon doit être un nombre.";
            else {
                if (intval($_POST['DuKm']) < 1)
                    $errors['DuKm'] = "Le début du kilométrage du tronçon doit être supérieur à 0.";
            }

            // Fin de kilométrage  du tronçon
            if (!is_numeric($_POST['AuKm']))
                $errors['AuKm'] = "Le fin du kilométrage du tronçon doit être un nombre.";
            else {
                if (intval($_POST['AuKm']) <= intval($_POST['DuKm']))
                    $errors['AuKm'] = "La fin du kilométrage du tronçon doit être supérieur au début du kilométrage du tronçon.";

                // Fin du tronçon avant la fin de l'autoroute
                if (intval($_POST['AuKm']) > intval($donnees['AuKm']))
                    $errors['AuKm'] = "La fin du kilométrage du tronçon doit être inférieur ou égal à la fin de l'autoroute.";
            }

            // Vérifier qu'il n'y ait pas déjà un tronçon sur ce kilométrage de l'autoroute
            $request = $db->prepare("SELECT * FROM Tronçon WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'  => $_POST['CodA']
            ));
            while ($donnees = $request->fetch()) {
                if (($_POST['DuKm'] >= $donnees['DuKm'] && $_POST['DuKm'] <= $donnees['AuKm']) || ($_POST['AuKm'] >= $donnees['DuKm'] && $_POST['AuKm'] <= $donnees['AuKm']))
                    $errors['DuKm'] = "Un tronçon existe déjà sur cette portion d'autoroute.";
            }

            // Etat du tronçon
            if (!empty($_POST['Etat'])) {
                if ($_POST['Etat'] != 'ouvert' && $_POST['Etat'] != 'ferme')
                    $errors['Etat'] = "L'état du tronçon est inconnu.";
            }

            // Num de fermeture
            if ($_POST['Etat'] == 'ferme' && !empty($_POST['Num'])) {
                $request = $db->prepare("SELECT * FROM Registre_fermeture WHERE Num = :Num");
                $request->execute(array(
                    'Num'   => $_POST['Num']
                ));
                if (!$donnees = $request->fetch())
                    $errors['Num'] = "Cette fermeture n'existe pas dans le registre.";
            }

            if (empty($errors)) {
                if ($_POST['Etat'] == 'ferme') {
                    $request = $db->prepare("INSERT INTO Troncon(CodA, DuKm, AuKm, Num) VALUES(:CodA, :DuKm, :AuKm, :Num)");
                    $request->execute(array(
                        'CodA'      => $_POST['CodA'], 
                        'DuKm'      => $_POST['DuKm'],
                        'AuKm'      => $_POST['AuKm'],
                        'Num'       => $_POST['Num']
                    ));
                } else if ($_POST['Etat'] == 'ouvert') {
                    $request = $db->prepare("INSERT INTO Troncon(CodA, DuKm, AuKm) VALUES(:CodA, :DuKm, :AuKm)");
                    $request->execute(array(
                        'CodA'      => $_POST['CodA'], 
                        'DuKm'      => $_POST['DuKm'],
                        'AuKm'      => $_POST['AuKm']
                    ));
                }

                $_SESSION['flash']['success'] = "Le tronçon a été ajouté avec succès.";
                header('Location: troncon.php');
                die();
            }
        } else if (!empty($_POST['submit'])) {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: troncon.php?action=ajouter');
            die();
        }
    } else if (isset($_GET['CodT']) && isset($_GET['action']) && $_GET['action'] == 'modifier') { // MODIFIER
        if (isset($_POST['Etat'])) {
            $ide = $_POST['Etat'];
        } else if ($data['Num'] == null) {
            $ide = 'ouvert';
        } else if ($data['Num'] != '0') {
            $ide = 'ferme';
        } else {
            $ide = null;
        }

        if (!empty($_POST['valider'])) {
            $errors = array();
            $inputs = array();

            $inputs = $data;

            // Début de kilométrage de l'autoroute
            if (!empty($_POST['DuKm']) && strcmp($_POST['DuKm'], $inputs['DuKmT']) !== 0) {
                if (!is_numeric($_POST['DuKm']))
                    $errors['DuKm'] = "Le début du kilométrage du tronçon doit être un nombre.";
                else {
                    if (intval($_POST['DuKm']) < 1)
                        $errors['DuKm'] = "La début du kilométrage du tronçon doit être supérieur à 0.";
                }

                if (!isset($errors['DuKm']))
                    $inputs['DuKm'] = $_POST['DuKm'];
            }

            // Fin de kilométrage de l'autoroute
            if (!empty($_POST['AuKm']) && strcmp($_POST['AuKm'], $inputs['AuKmT']) !== 0) {
                if (!is_numeric($_POST['AuKm']))
                    $errors['AuKm'] = "Le fin du kilométrage du tronçon doit être un nombre.";
                else {
                    if (intval($_POST['AuKm']) <= intval($_POST['DuKm']))
                        $errors['AuKm'] = "La fin du kilométrage du tronçon doit être supérieur au début du kilométrage du tronçon.";

                    // Fin du tronçon avant la fin de l'autoroute
                    if (intval($_POST['AuKm']) > intval($data['AuKmA']))
                        $errors['AuKm'] = "La fin du kilométrage du tronçon doit être inférieur ou égal à la fin de l'autoroute.";
                }

                if (!isset($errors['AuKm']))
                    $inputs['AuKm'] = $_POST['AuKm'];
            }

            if (!empty($_POST['DuKm']) && !empty($_POST['AuKm'])) {
                // Vérifier qu'il n'y ait pas déjà un tronçon sur ce kilométrage de l'autoroute
                $request = $db->prepare("SELECT * FROM Troncon WHERE CodA = :CodA AND CodT <> :CodT");
                $request->execute(array(
                    'CodA'      => $_POST['CodA'],
                    'CodT'      => $data['CodT']
                ));
                while ($donnees = $request->fetch()) {
                    if (($_POST['DuKm'] >= $donnees['DuKm'] && $_POST['DuKm'] <= $donnees['AuKm']) || ($_POST['AuKm'] >= $donnees['DuKm'] && $_POST['AuKm'] <= $donnees['AuKm']))
                        $errors['DuKm'] = "Un tronçon existe déjà sur cette portion d'autoroute.";
                }
            }

            // Etat du tronçon
            if (!empty($_POST['Etat'])) {
                if ($_POST['Etat'] != 'ouvert' && $_POST['Etat'] != 'ferme')
                    $errors['Etat'] = "L'état du tronçon est inconnu.";
            }

            // Num de fermeture
            if (!empty($_POST['Etat']) && $_POST['Etat'] == 'ferme') {
                if (!empty($_POST['Num']) && strcmp($_POST['Num'], $inputs['Num']) !== 0) {
                    $request = $db->prepare("SELECT * FROM Registre_fermeture WHERE Num = :Num");
                    $request->execute(array(
                        'Num'   => $_POST['Num']
                    ));
                    if (!$donnees = $request->fetch())
                        $errors['Num'] = "Cette fermeture n'existe pas dans le registre.";

                    if (!isset($errors['Num']))
                        $inputs['Num'] = $_POST['Num'];
                }
            }

            // Code de l'autoroute
            if (!empty($_POST['CodA']) && strcmp($_POST['CodA'], $inputs['CodA']) !== 0) {
                // Existence
                $request = $db->prepare("SELECT * FROM Autoroute WHERE CodA = :CodA");
                $request->execute(array(
                    'CodA'   => $_POST['CodA']
                ));
                if (!$donnees = $request->fetch())
                    $errors['CodA'] = "Cette autoroute n'existe pas.";

                if (!isset($errors['CodA']))
                    $inputs['CodA'] = $_POST['CodA'];
            }

            // Erreur
            if (empty($errors)) {
                if (!empty($_POST['Etat']) && $_POST['Etat'] == 'ferme') {
                    $request = $db->prepare('UPDATE Troncon SET CodA = :CodA, DuKm = :DuKm, AuKm = :AuKm, Num = :Num WHERE CodT = :CodT');
                    $request->execute(array(
                        'CodA'          => $inputs['CodA'],
                        'DuKm'          => $inputs['DuKm'],
                        'AuKm'          => $inputs['AuKm'],
                        'Num'           => $inputs['Num'],
                        'CodT'          => $data['CodT']
                    ));
                } else if (!empty($_POST['Etat']) && $_POST['Etat'] == 'ouvert') {
                    $request = $db->prepare('UPDATE Troncon SET CodA = :CodA, DuKm = :DuKm, AuKm = :AuKm, Num = NULL WHERE CodT = :CodT');
                    $request->execute(array(
                        'CodA'          => $inputs['CodA'],
                        'DuKm'          => $inputs['DuKm'],
                        'AuKm'          => $inputs['AuKm'],
                        'CodT'          => $data['CodT']
                    ));
                }

                $data = $inputs;
                $_SESSION['flash']['success'] = "Le tronçon a été modifié avec succès.";
                header('Location: troncon.php');
                die();
            }
        }
    } else if (isset($_GET['CodT']) && isset($_GET['action']) && $_GET['action'] == 'supprimer' && !empty($_POST)) {
        if (isset($_POST['oui'])) {
            // Suppression des sorties du tronçon
            $request = $db->prepare("DELETE FROM Sortie WHERE CodT = :CodT");
            $request->execute(array(
                'CodT'  => $data['CodT']
            ));

            // Suppression du tronçon
            $request = $db->prepare("DELETE FROM Troncon WHERE CodT = :CodT");
            $request->execute(array(
                'CodT'  => $data['CodT']
            ));

            $_SESSION['flash']['success'] = "Le tronçon a été supprimé avec succès.";
            header('Location: troncon.php');
            die();
        }

        if (isset($_POST['non'])) {
            header('Location: troncon.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Tronçon</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="troncon.php" class="active">Tronçon</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash();
                                if (!isset($_GET['CodT']) && isset($_GET['action']) && $_GET['action'] == 'ajouter') : ?>
                                    <h3 class="box-title">Ajout d'un tronçon</h3>
                                    <form class="form-horizontal" action='troncon.php?action=ajouter' method="POST" name="changement">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Code de l'autoroute -->
                                            <div class="form-group <?php if (isset($errors['CodA'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodA">Autoroute</label>
                                                <div class="col-md-5">
                                                    <select id="CodA" name="CodA" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT CodA FROM Autoroute");
                                                        while ($donnees = $request->fetch())
                                                            echo '<option value="' . $donnees['CodA'] . '">' . strip_tags(htmlspecialchars($donnees['CodA'])) . '</option>';
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodA']))
                                                            echo $errors['CodA'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Début kilomètre -->
                                            <div class="form-group <?php if (isset($errors['DuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="DuKm">Kilométrage de début de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="DuKm" name="DuKm" type="text" placeholder="Kilométrage de début d'autoroute" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['DuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['DuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Fin kilomètre -->
                                            <div class="form-group <?php if (isset($errors['AuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="AuKm">Kilométrage de fin de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="AuKm" name="AuKm" type="text" placeholder="Kilométrage de fin d'autoroute" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['AuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['AuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Etat du tronçon -->
                                            <div class="form-group <?php if (isset($errors['Etat'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Etat">Etat du tronçon</label>  
                                                <div class="col-md-5">
                                                    <select id="Etat" name="Etat" class="form-control" onchange="changement.submit()">
                                                        <option value="ouvert" <?php if (isset($_POST['Etat']) && $_POST['Etat'] == 'ouvert') echo 'selected="selected"'; ?>>Ouvert</option>
                                                        <option value="ferme" <?php if (isset($_POST['Etat']) && $_POST['Etat'] == 'ferme') echo 'selected="selected"'; ?>>Fermé</option>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Etat']))
                                                            echo strip_tags(htmlspecialchars($errors['Etat']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Registre de fermeture -->
                                            <?php if (isset($ide) && $ide != -1 && $ide == 'ferme') :
                                                $request = $db->query("SELECT * FROM Registre_fermeture");
                                                if ($request->rowCount() > 0) : ?>
                                                    <div class="form-group <?php if (isset($errors['Num'])) echo "has-error"; ?>">
                                                        <label class="col-md-4 control-label" for="Num">Fermeture</label>  
                                                        <div class="col-md-5">
                                                            <select id="Num" name="Num" class="form-control">
                                                            <?php
                                                                while ($donnees = $request->fetch())
                                                                    echo '<option value="' . $donnees['Num'] . '">' . strip_tags(htmlspecialchars($donnees['Descriptif'])) . '</option>';
                                                            ?>
                                                            </select>
                                                            <span class="help-block">
                                                                <?php
                                                                if (isset($errors['Num']))
                                                                    echo strip_tags(htmlspecialchars($errors['Num']));
                                                                ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                            <?php endif;
                                            endif; ?>
                                            <!-- Button -->
                                            <div class="form-group text-center">
                                                <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                    <input type="submit" name="valider" class="btn btn-success btn-block" value="Ajouter" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                <?php elseif (isset($_GET['CodT']) && isset($_GET['action']) && $_GET['action'] == 'modifier') : ?>
                                    <h3 class="box-title">Modification du tronçon</h3>
                                    <form class="form-horizontal" action='troncon.php?CodT=<?php echo $data['CodT']; ?>&action=modifier' method="POST" name="changement">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Code de l'autoroute -->
                                            <div class="form-group <?php if (isset($errors['CodA'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodA">Autoroute</label>
                                                <div class="col-md-5">
                                                    <select id="CodA" name="CodA" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT CodA FROM Autoroute");
                                                        while ($donnees = $request->fetch()) {
                                                            echo '<option value="' . $donnees['CodA'] . '" ';
                                                            if ($donnees['CodA'] == $data['CodA'])
                                                                echo 'selected="selected"';
                                                            echo '>' . strip_tags(htmlspecialchars($donnees['CodA'])) . '</option>';
                                                        }
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodA']))
                                                            echo $errors['CodA'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Début kilomètre -->
                                            <div class="form-group <?php if (isset($errors['DuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="DuKm">Kilométrage de début de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="DuKm" name="DuKm" type="text" placeholder="Kilométrage de début d'autoroute" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['DuKmT'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['DuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['DuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Fin kilomètre -->
                                            <div class="form-group <?php if (isset($errors['AuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="AuKm">Kilométrage de fin de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="AuKm" name="AuKm" type="text" placeholder="Kilométrage de fin d'autoroute" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['AuKmT'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['AuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['AuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Etat du tronçon -->
                                            <div class="form-group <?php if (isset($errors['Etat'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Etat">Etat du tronçon</label>  
                                                <div class="col-md-5">
                                                    <select id="Etat" name="Etat" class="form-control" onchange="changement.submit()">
                                                        <option value="ouvert" <?php if ($ide == 'ouvert') echo 'selected="selected"'; ?>>Ouvert</option>
                                                        <option value="ferme" <?php if ($ide == 'ferme') echo 'selected="selected"'; ?>>Fermé</option>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Etat']))
                                                            echo strip_tags(htmlspecialchars($errors['Etat']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Registre de fermeture -->
                                            <?php if (isset($ide) && $ide != -1 && $ide == 'ferme') :
                                                $request = $db->query("SELECT * FROM Registre_fermeture");
                                                if ($request->rowCount() > 0) : ?>
                                                    <div class="form-group <?php if (isset($errors['Num'])) echo "has-error"; ?>">
                                                        <label class="col-md-4 control-label" for="Num">Fermeture</label>  
                                                        <div class="col-md-5">
                                                            <select id="Num" name="Num" class="form-control">
                                                            <?php
                                                                while ($donnees = $request->fetch()) {
                                                                    echo '<option value="' . $donnees['Num'] . '"';
                                                                    if ($donnees['Num'] == $data['Num'])
                                                                        echo 'selected="selected"';
                                                                    echo '>' . strip_tags(htmlspecialchars($donnees['Descriptif'])) . '</option>';
                                                                }
                                                            ?>
                                                            </select>
                                                            <span class="help-block">
                                                                <?php
                                                                if (isset($errors['Num']))
                                                                    echo strip_tags(htmlspecialchars($errors['Num']));
                                                                ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                            <?php endif;
                                            endif; ?>
                                            <!-- Button -->
                                            <div class="form-group text-center">
                                                <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                    <input type="submit" name="valider" class="btn btn-success btn-block" value="Modifier" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                <?php elseif (isset($_GET['CodT']) && isset($_GET['action']) && $_GET['action'] == 'supprimer') : ?>
                                    <h3 class="box-title">Suppression du tronçon</h3>
                                    <div class="row text-center">
                                        <p>Êtes-vous sûr de vouloir supprimer ce tronçon ?</p>
                                        <form enctype="multipart/form-data" class="form-horizontal" method="post" action="troncon.php?CodT=<?php echo $data['CodT']; ?>&action=supprimer">
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
                                <?php elseif (!isset($_GET['CodT']) && !isset($_GET['action'])) : ?>
                                    <h3 class="box-title">Tronçons <a href="troncon.php?action=ajouter"><span class="glyphicon glyphicon-plus pull-right"></span></a></h3>
                                    <div class="row text-center">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="col-lg-4 text-center">Autoroute</th>
                                                    <th class="col-lg-2 text-center">Début kilomètre</th>
                                                    <th class="col-lg-2 text-center">Fin kilomètre</th>
                                                    <th class="col-lg-2 text-center">Fermeture</th>
                                                    <th class="col-lg-2 text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $request = $db->query("SELECT COUNT(CodT) AS nbTroncon FROM Troncon");
                                                $data = $request->fetch();

                                                $nbTroncon = $data['nbTroncon'];
                                                $perPage = 5;
                                                $nbPage = ceil($nbTroncon / $perPage);

                                                if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbPage)
                                                    $cPage = $_GET['page'];
                                                else
                                                    $cPage = 1;

                                                $request = $db->query("SELECT * FROM Troncon ORDER BY CodA, DuKm, AuKm DESC LIMIT " . (($cPage - 1) * $perPage) . ", $perPage");
                                                while ($data = $request->fetch()) {
                                                    echo '<tr>
                                                    <td>' . strip_tags(htmlspecialchars($data['CodA'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['DuKm'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['AuKm'])) . '</td>
                                                    <td>';
                                                    if ($data['Num'] == null)
                                                        echo 'Ouvert';
                                                    else
                                                        echo 'Fermé';
                                                    echo '</td>
                                                    <td><a href="troncon.php?CodT=' . $data['CodT'] . '&action=modifier"><span class="glyphicon glyphicon-pencil"></span></a>
                                                    <a href="troncon.php?CodT=' . $data['CodT'] . '&action=supprimer"><span class="glyphicon glyphicon-remove"></span></a>
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
                                                        echo '<li class="active"><a href="troncon.php?page=' . $i . '">' . $i . '</a></li>';
                                                    else
                                                        echo '<li><a href="troncon.php?page=' . $i . '">' . $i . '</a></li>';
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