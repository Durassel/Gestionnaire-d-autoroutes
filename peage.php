<?php
    $title = "Péage";

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
        header('Location: peage.php');
        die();
    }

    if (isset($_GET['CodePeage'])) {
        // Existance
        $request = $db->prepare("SELECT * FROM Peage, Autoroute WHERE CodePeage = :CodePeage AND Peage.CodA = Autoroute.CodA");
        $request->execute(array(
            'CodePeage'  => $_GET['CodePeage']
        ));
        if (!$data = $request->fetch()) {
            $_SESSION['flash']['danger'] = "Ce péage n'existe pas.";
            header('Location: peage.php');
            die();
        }
    }

    if (isset($_GET['CodePeage']) && isset($_GET['action']) && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer') {
        $_SESSION['flash']['danger'] = "Uniquement la modification et la suppression de ce péage est autorisée.";
        header('Location: peage.php');
        die();
    }

    if (isset($_GET['CodePeage']) && !isset($_GET['action'])) {
        $_SESSION['flash']['danger'] = "Des informations sont manquantes.";
        header('Location: peage.php');
        die();
    }

    // TRAITEMENT
    if (!isset($_GET['CodePeage']) && isset($_GET['action']) && $_GET['action'] == 'ajouter' && !empty($_POST)) { // AJOUTER
        if (!empty($_POST['CodA']) && !empty($_POST['PGDuKm']) && !empty($_POST['PGAuKm']) && !empty($_POST['Tarif'])) {
            $errors = array();

            // SCA
            $request = $db->prepare("SELECT * FROM Sca WHERE Code = :Code");
            $request->execute(array(
                'Code'   => $_POST['Code']
            ));
            if (!$donnees = $request->fetch()) {
                $errors['Code'] = "Cette société n'existe pas.";
            }

            // Autoroute
            $request = $db->prepare("SELECT * FROM Autoroute WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'   => $_POST['CodA']
            ));
            if (!$donnees = $request->fetch()) {
                $errors['CodA'] = "Cette autoroute n'existe pas.";
            }

            // Début de kilométrage du péage
            if (!is_numeric($_POST['PGDuKm']))
                $errors['PGDuKm'] = "Le début du kilométrage du péage doit être un nombre.";
            else {
                if (intval($_POST['PGDuKm']) < 1)
                    $errors['PGDuKm'] = "La début du kilométrage du péage doit être supérieur à 0.";
            }

            // Fin de kilométrage  du péage
            if (!is_numeric($_POST['PGAuKm']))
                $errors['PGAuKm'] = "Le fin du kilométrage du péage doit être un nombre.";
            else {
                if (intval($_POST['PGAuKm']) <= intval($_POST['PGDuKm']))
                    $errors['PGAuKm'] = "La fin du kilométrage du péage doit être supérieur au début du kilométrage du péage.";

                // Fin du péage avant la fin de l'autoroute
                if (intval($_POST['PGAuKm']) > intval($donnees['AuKm']))
                    $errors['PGAuKm'] = "La fin du kilométrage du péage doit être inférieur ou égal à la fin de l'autoroute.";
            }

            // Vérifier qu'il n'y ait pas déjà un péage sur ce kilométrage de l'autoroute
            $request = $db->prepare("SELECT * FROM Peage WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'   => $_POST['CodA']
            ));
            while ($donnees = $request->fetch()) {
                if (($_POST['PGDuKm'] >= $donnees['PGDuKm'] && $_POST['PGDuKm'] <= $donnees['PGAuKm']) || ($_POST['PGAuKm'] >= $donnees['PGDuKm'] && $_POST['PGAuKm'] <= $donnees['PGAuKm']))
                    $errors['PGDuKm'] = "Un péage existe déjà sur cette portion d'autoroute.";
            }

            // Tarif
            if (!is_numeric($_POST['Tarif']))
                $errors['Tarif'] = "Le tarif doit être un nombre.";
            else {
                if (intval($_POST['Tarif']) < 1)
                    $errors['Tarif'] = "Le tarif doit être supérieur à 0.";
            }

            if (empty($errors)) {
                $request = $db->prepare("INSERT INTO Peage(CodA, PGDuKm, PGAuKm, Tarif, Code) VALUES(:CodA, :PGDuKm, :PGAuKm, :Tarif, :Code)");
                $request->execute(array(
                    'CodA'      => $_POST['CodA'], 
                    'PGDuKm'    => $_POST['PGDuKm'],
                    'PGAuKm'    => $_POST['PGAuKm'],
                    'Tarif'     => $_POST['Tarif'],
                    'Code'      => $_POST['Code']
                ));

                $_SESSION['flash']['success'] = "Le péage a été ajouté avec succès.";
                header('Location: peage.php');
                die();
            }
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: peage.php?action=ajouter');
            die();
        }
    } else if (isset($_GET['CodePeage']) && isset($_GET['action']) && $_GET['action'] == 'modifier' && !empty($_POST)) { // MODIFIER
        $errors = array();
        $inputs = array();

        $inputs = $data;

        // SCA
        if (!empty($_POST['Code']) && strcmp($_POST['Code'], $inputs['Code']) !== 0) {
            $request = $db->prepare("SELECT * FROM Sca WHERE Code = :Code");
            $request->execute(array(
                'Code'   => $_POST['Code']
            ));
            if (!$donnees = $request->fetch())
                $errors['Code'] = "Cette société n'existe pas.";

            if (!isset($errors['Code']))
                $inputs['Code'] = $_POST['Code'];
        }

        // Code de l'autoroute
        $DuKm = null;
        $AuKm = null;
        if (!empty($_POST['CodA']) && strcmp($_POST['CodA'], $inputs['CodA']) !== 0) {
            // Existence
            $request = $db->prepare("SELECT * FROM Autoroute WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'   => $_POST['CodA']
            ));
            if (!$donnees = $request->fetch())
                $errors['CodA'] = "Cette autoroute n'existe pas.";
            else {
                $DuKm = $donnees['DuKm'];
                $AuKm = $donnees['AuKm'];
            }

            if (!isset($errors['CodA']))
                $inputs['CodA'] = $_POST['CodA'];
        } else {
            $DuKm = $data['DuKm'];
            $AuKm = $data['AuKm'];
        }

        // Début de kilométrage du péage
        if (!empty($_POST['PGDuKm']) && strcmp($_POST['PGDuKm'], $inputs['PGDuKm']) !== 0) {
            if (!is_numeric($_POST['PGDuKm']))
                $errors['PGDuKm'] = "Le début du kilométrage du péage doit être un nombre.";
            else {
                if (intval($_POST['PGDuKm']) < $DuKm)
                    $errors['PGDuKm'] = "La début du kilométrage du péage doit être supérieur à 0.";
                if (intval($_POST['PGDuKm']) >= intval($AuKm))
                    $errors['PGDuKm'] = "La début du kilométrage du péage doit être supérieur à 0.";
            }

            if (!isset($errors['PGDuKm']))
                $inputs['PGDuKm'] = $_POST['PGDuKm'];
        }

        // Fin de kilométrage du péage
        if (!empty($_POST['PGAuKm']) && strcmp($_POST['PGAuKm'], $inputs['PGAuKm']) !== 0) {
            if (!is_numeric($_POST['PGAuKm']))
                $errors['PGAuKm'] = "Le fin du kilométrage du péage doit être un nombre.";
            else {
                if (intval($_POST['PGAuKm']) <= intval($_POST['PGDuKm']))
                    $errors['PGAuKm'] = "La fin du kilométrage du péage doit être supérieur au début du kilométrage du péage.";

                // Fin du péage avant la fin de l'autoroute
                if (intval($_POST['PGAuKm']) > intval($AuKm))
                    $errors['PGAuKm'] = "La fin du kilométrage du péage doit être inférieur ou égal à la fin de l'autoroute.";
            }

            if (!isset($errors['PGAuKm']))
                $inputs['PGAuKm'] = $_POST['PGAuKm'];
        }

        if (!empty($_POST['PGDuKm']) && !empty($_POST['PGAuKm'])) {
            // Vérifier qu'il n'y ait pas déjà un péage sur ce kilométrage de l'autoroute
            $request = $db->prepare("SELECT * FROM Peage WHERE CodA = :CodA AND CodePeage <> :CodePeage");
            $request->execute(array(
                'CodA'      => $_POST['CodA'],
                'CodePeage' => $data['CodePeage']
            ));
            while ($donnees = $request->fetch()) {
                if (($_POST['PGDuKm'] >= $donnees['PGDuKm'] && $_POST['PGDuKm'] <= $donnees['PGAuKm']) || ($_POST['PGAuKm'] >= $donnees['PGDuKm'] && $_POST['PGAuKm'] <= $donnees['PGAuKm']))
                    $errors['PGDuKm'] = "Un péage existe déjà sur cette portion d'autoroute.";
            }
        }

        // Tarif
        if (!empty($_POST['Tarif']) && strcmp($_POST['Tarif'], $inputs['Tarif']) !== 0) {
            if (!is_numeric($_POST['Tarif']))
                $errors['Tarif'] = "Le tarif doit être un nombre.";
            else {
                if (intval($_POST['Tarif']) < 1)
                    $errors['Tarif'] = "Le tarif doit être supérieur à 0.";
            }

            if (!isset($errors['Tarif']))
                $inputs['Tarif'] = $_POST['Tarif'];
        }

        // Erreur
        if (empty($errors)) {
            $request = $db->prepare('UPDATE Peage SET CodA = :CodA, PGDuKm = :PGDuKm, PGAuKm = :PGAuKm, Tarif = :Tarif, Code = :Code WHERE CodePeage = :CodePeage');
            $request->execute(array(
                'CodA'          => $inputs['CodA'],
                'PGDuKm'        => $inputs['PGDuKm'],
                'PGAuKm'        => $inputs['PGAuKm'],
                'Tarif'         => $inputs['Tarif'],
                'Code'          => $inputs['Code'],
                'CodePeage'     => $data['CodePeage']
            ));

            $data = $inputs;
            $_SESSION['flash']['success'] = "Le péage a été modifié avec succès.";
            header('Location: peage.php');
            die();
        }
    } else if (isset($_GET['CodePeage']) && isset($_GET['action']) && $_GET['action'] == 'supprimer' && !empty($_POST)) {
        if (isset($_POST['oui'])) {
            // Suppression du péage
            $request = $db->prepare("DELETE FROM Peage WHERE CodePeage = :CodePeage");
            $request->execute(array(
                'CodePeage'  => $data['CodePeage']
            ));

            $_SESSION['flash']['success'] = "Le péage a été supprimé avec succès.";
            header('Location: peage.php');
            die();
        }

        if (isset($_POST['non'])) {
            header('Location: peage.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Péage</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="peage.php" class="active">Péage</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash();
                                if (!isset($_GET['CodePeage']) && isset($_GET['action']) && $_GET['action'] == 'ajouter') : ?>
                                    <h3 class="box-title">Ajout d'un péage</h3>
                                    <form class="form-horizontal" action='peage.php?action=ajouter' method="POST">
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
                                            <!-- Sca -->
                                            <div class="form-group <?php if (isset($errors['Code'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Code">SCA</label>
                                                <div class="col-md-5">
                                                    <select id="Code" name="Code" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT * FROM Sca ORDER BY Code");
                                                        while ($donnees = $request->fetch())
                                                            echo '<option value="' . $donnees['Code'] . '">' . strip_tags(htmlspecialchars($donnees['Nom'])) . '</option>';
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Code']))
                                                            echo $errors['Code'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Début kilomètre -->
                                            <div class="form-group <?php if (isset($errors['PGDuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="PGDuKm">Kilométrage de début de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="PGDuKm" name="PGDuKm" type="text" placeholder="Kilométrage de début d'autoroute" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['PGDuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['PGDuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Fin kilomètre -->
                                            <div class="form-group <?php if (isset($errors['PGAuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="PGAuKm">Kilométrage de fin de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="PGAuKm" name="PGAuKm" type="text" placeholder="Kilométrage de fin d'autoroute" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['PGAuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['PGAuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Tarif -->
                                            <div class="form-group <?php if (isset($errors['Tarif'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Tarif">Tarif</label>  
                                                <div class="col-md-5">
                                                    <input id="Tarif" name="Tarif" type="text" placeholder="Tarif" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Tarif']))
                                                            echo strip_tags(htmlspecialchars($errors['Tarif']));
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
                                <?php elseif (isset($_GET['CodePeage']) && isset($_GET['action']) && $_GET['action'] == 'modifier') : ?>
                                    <h3 class="box-title">Modification du péage</h3>
                                    <form class="form-horizontal" action='peage.php?CodePeage=<?php echo $data['CodePeage']; ?>&action=modifier' method="POST">
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
                                            <!-- Sca -->
                                            <div class="form-group <?php if (isset($errors['Code'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Code">SCA</label>
                                                <div class="col-md-5">
                                                    <select id="Code" name="Code" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT * FROM Sca ORDER BY Code");
                                                        while ($donnees = $request->fetch()) {
                                                            echo '<option value="' . $donnees['Code'] . '" ';
                                                            if ($donnees['Code'] == $data['Code'])
                                                                echo 'selected="selected"';
                                                            echo '>' . strip_tags(htmlspecialchars($donnees['Nom'])) . '</option>';
                                                        }
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Code']))
                                                            echo $errors['Code'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Début kilomètre -->
                                            <div class="form-group <?php if (isset($errors['PGDuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="PGDuKm">Kilométrage de début de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="PGDuKm" name="PGDuKm" type="text" placeholder="Kilométrage de début d'autoroute" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['PGDuKm'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['PGDuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['PGDuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Fin kilomètre -->
                                            <div class="form-group <?php if (isset($errors['PGAuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="PGAuKm">Kilométrage de fin de péage</label>  
                                                <div class="col-md-5">
                                                    <input id="PGAuKm" name="PGAuKm" type="text" placeholder="Kilométrage de fin d'autoroute" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['PGAuKm'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['PGAuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['PGAuKm']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Tarif -->
                                            <div class="form-group <?php if (isset($errors['Tarif'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Tarif">Tarif</label>  
                                                <div class="col-md-5">
                                                    <input id="Tarif" name="Tarif" type="text" placeholder="Tarif" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['Tarif'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Tarif']))
                                                            echo strip_tags(htmlspecialchars($errors['Tarif']));
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
                                <?php elseif (isset($_GET['CodePeage']) && isset($_GET['action']) && $_GET['action'] == 'supprimer') : ?>
                                    <h3 class="box-title">Suppression du péage</h3>
                                    <div class="row text-center">
                                        <p>Êtes-vous sûr de vouloir supprimer ce péage ?</p>
                                        <form enctype="multipart/form-data" class="form-horizontal" method="post" action="peage.php?CodePeage=<?php echo $data['CodePeage']; ?>&action=supprimer">
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
                                <?php elseif (!isset($_GET['CodePeage']) && !isset($_GET['action'])) : ?>
                                    <h3 class="box-title">Péages <a href="peage.php?action=ajouter"><span class="glyphicon glyphicon-plus pull-right"></span></a></h3>
                                    <div class="row text-center">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="col-lg-2 text-center">Autoroute</th>
                                                    <th class="col-lg-2 text-center">SCA</th>
                                                    <th class="col-lg-2 text-center">Début kilomètre</th>
                                                    <th class="col-lg-2 text-center">Fin kilomètre</th>
                                                    <th class="col-lg-2 text-center">Tarif</th>
                                                    <th class="col-lg-2 text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $request = $db->query("SELECT COUNT(CodePeage) AS nbPeage FROM Peage");
                                                $data = $request->fetch();

                                                $nbPeage = $data['nbPeage'];
                                                $perPage = 5;
                                                $nbPage = ceil($nbPeage / $perPage);

                                                if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbPage)
                                                    $cPage = $_GET['page'];
                                                else
                                                    $cPage = 1;

                                                $request = $db->query("SELECT Peage.CodA, Peage.PGDuKm, Peage.PGAuKm, Peage.Tarif, Peage.CodePeage, Sca.Nom FROM Peage, Sca WHERE Peage.Code = Sca.Code ORDER BY CodA, Sca.Code DESC LIMIT " . (($cPage - 1) * $perPage) . ", $perPage");
                                                while ($data = $request->fetch()) {
                                                    echo '<tr>
                                                    <td>' . strip_tags(htmlspecialchars($data['CodA'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['Nom'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['PGDuKm'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['PGAuKm'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['Tarif'])) . '</td>
                                                    <td><a href="peage.php?CodePeage=' . $data['CodePeage'] . '&action=modifier"><span class="glyphicon glyphicon-pencil"></span></a>
                                                    <a href="peage.php?CodePeage=' . $data['CodePeage'] . '&action=supprimer"><span class="glyphicon glyphicon-remove"></span></a>
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
                                                        echo '<li class="active"><a href="peage.php?page=' . $i . '">' . $i . '</a></li>';
                                                    else
                                                        echo '<li><a href="peage.php?page=' . $i . '">' . $i . '</a></li>';
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