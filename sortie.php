<?php
    $title = "Sortie";

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
        header('Location: sortie.php');
        die();
    }

    if (isset($_GET['Numero'])) {
        // Existance
        $request = $db->prepare("SELECT Sortie.Numero, Sortie.Libelle, Sortie.CodT, Sortie.CodP, Sortie.KmSortie, Autoroute.CodA, Troncon.DuKm, Troncon.AuKm FROM Sortie, Troncon, Autoroute WHERE Sortie.Numero = :Numero AND Sortie.CodT = Troncon.CodT AND Troncon.CodA = Autoroute.CodA");
        $request->execute(array(
            'Numero'  => $_GET['Numero']
        ));
        if (!$data = $request->fetch()) {
            $_SESSION['flash']['danger'] = "Cette sortie n'existe pas.";
            header('Location: sortie.php');
            die();
        }
    }

    if (isset($_GET['Numero']) && isset($_GET['action']) && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer') {
        $_SESSION['flash']['danger'] = "Uniquement la modification et la suppression de cette sortie est autorisée.";
        header('Location: sortie.php');
        die();
    }

    if (isset($_GET['Numero']) && !isset($_GET['action'])) {
        $_SESSION['flash']['danger'] = "Des informations sont manquantes.";
        header('Location: sortie.php');
        die();
    }

    // TRAITEMENT
    if (!isset($_GET['Numero']) && isset($_GET['action']) && $_GET['action'] == 'ajouter' && !empty($_POST)) { // AJOUTER
        if (!empty($_POST['Libelle']) && !empty($_POST['CodT']) && !empty($_POST['CodP']) && !empty($_POST['KmSortie'])) {
            $errors = array();

            // Libellé
            if (strlen($_POST['Libelle']) > 25)
                $errors['Libelle'] = "Le libellé de la sortie est trop long (25 caractères maximum).";
            if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['Libelle']))
                $errors['Libelle'] = "Le libellé n'est pas valide.";

            // CodP
            $request = $db->prepare("SELECT * FROM Ville WHERE CodP = :CodP");
            $request->execute(array(
                'CodP'   => $_POST['CodP']
            ));
            if (!$donnees = $request->fetch()) {
                $errors['CodP'] = "Ce code postal n'existe pas.";
            }

            // CodT
            $request = $db->prepare("SELECT Troncon.CodT, Autoroute.DuKm, Autoroute.AuKm FROM Troncon, Autoroute WHERE CodT = :CodT");
            $request->execute(array(
                'CodT'   => $_POST['CodT']
            ));
            if (!$donnees = $request->fetch()) {
                $errors['CodT'] = "Ce tronçon n'existe pas.";
            }

            // Kilomètre de la sortie par rapport au début/fin de l'autoroute
            if (!is_numeric($_POST['KmSortie']))
                $errors['KmSortie'] = "Le kilométrage de sortie doit être un nombre.";
            else {
                if (intval($_POST['KmSortie']) > $donnees['AuKm'])
                    $errors['KmSortie'] = "Le kilométrage de sortie doit intervenir avant la fin du tronçon.";
                if (intval($_POST['KmSortie']) < $donnees['DuKm'])
                    $errors['KmSortie'] = "Le kilométrage de sortie doit intervenir après le début du tronçon.";
            }

            // Vérifier qu'il n'y ait pas déjà une sortie identique
            $request = $db->prepare("SELECT * FROM Sortie WHERE Libelle = :Libelle AND CodT = :CodT AND CodP = :CodP AND KmSortie = :KmSortie");
            $request->execute(array(
                'Libelle'   => $_POST['Libelle'],
                'CodT'      => $_POST['CodT'],
                'CodP'      => $_POST['CodP'],
                'KmSortie'  => $_POST['KmSortie']
            ));
            if ($donnees = $request->fetch())
                $errors['Libelle'] = "Une sortie identique existe déjà.";

            if (empty($errors)) {
                $request = $db->prepare("INSERT INTO Sortie(Libelle, CodT, CodP, KmSortie) VALUES(:Libelle, :CodT, :CodP, :KmSortie)");
                $request->execute(array(
                    'Libelle'   => $_POST['Libelle'], 
                    'CodT'      => $_POST['CodT'],
                    'CodP'      => $_POST['CodP'],
                    'KmSortie'  => $_POST['KmSortie']
                ));

                $_SESSION['flash']['success'] = "La fermeture a été ajoutée avec succès.";
                header('Location: sortie.php');
                die();
            }
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: sortie.php?action=ajouter');
            die();
        }
    } else if (isset($_GET['Numero']) && isset($_GET['action']) && $_GET['action'] == 'modifier' && !empty($_POST)) { // MODIFIER
        $errors = array();
        $inputs = array();

        $inputs = $data;

        // Libellé
        if (!empty($_POST['Libelle']) && strcmp($_POST['Libelle'], $inputs['Libelle']) !== 0) {
            if (strlen($_POST['Libelle']) > 25)
                $errors['Libelle'] = "Le libellé de la sortie est trop long (25 caractères maximum).";
            if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['Libelle']))
                $errors['Libelle'] = "Le libellé n'est pas valide.";

            if (!isset($errors['Libelle']))
                $inputs['Libelle'] = $_POST['Libelle'];
        }

        // CodT
        if (!empty($_POST['CodT']) && strcmp($_POST['CodT'], $inputs['CodT']) !== 0) {
            $request = $db->prepare("SELECT Troncon.CodT, Autoroute.DuKm, Autoroute.AuKm FROM Troncon, Autoroute WHERE CodT = :CodT");
            $request->execute(array(
                'CodT'   => $_POST['CodT']
            ));
            if (!$donnees = $request->fetch())
                $errors['CodT'] = "Ce tronçon n'existe pas.";
            
            if (!isset($errors['CodT']))
                $inputs['CodT'] = $_POST['CodT'];
        }

        // CodP
        if (!empty($_POST['CodP']) && strcmp($_POST['CodP'], $inputs['CodP']) !== 0) {
            $request = $db->prepare("SELECT * FROM Ville WHERE CodP = :CodP");
            $request->execute(array(
                'CodP'   => $_POST['CodP']
            ));
            if (!$donnees = $request->fetch())
                $errors['CodP'] = "Ce code postal n'existe pas.";

            if (!isset($errors['CodP']))
                $inputs['CodP'] = $_POST['CodP'];
        }

        // Kilomètre de la sortie par rapport au début/fin de l'autoroute
        if (!empty($_POST['KmSortie']) && strcmp($_POST['KmSortie'], $inputs['KmSortie']) !== 0) {
            if (!is_numeric($_POST['KmSortie']))
                $errors['KmSortie'] = "Le kilométrage de sortie doit être un nombre.";
            else {
                if (intval($_POST['KmSortie']) > $data['AuKm'])
                    $errors['KmSortie'] = "Le kilométrage de sortie doit intervenir avant la fin du tronçon.";
                if (intval($_POST['KmSortie']) < $data['DuKm'])
                    $errors['KmSortie'] = "Le kilométrage de sortie doit intervenir après le début du tronçon.";
            }

            if (!isset($errors['KmSortie']))
                $inputs['KmSortie'] = $_POST['KmSortie'];
        }

        // Vérifier qu'il n'y ait pas déjà une sortie identique
        if (!empty($_POST['Libelle']) && !empty($_POST['CodT']) && !empty($_POST['CodP']) && !empty($_POST['KmSortie'])) {
            $request = $db->prepare("SELECT * FROM Sortie WHERE Libelle = :Libelle AND CodT = :CodT AND CodP = :CodP AND KmSortie = :KmSortie");
            $request->execute(array(
                'Libelle'   => $_POST['Libelle'],
                'CodT'      => $_POST['CodT'],
                'CodP'      => $_POST['CodP'],
                'KmSortie'  => $_POST['KmSortie']
            ));
            if ($donnees = $request->fetch())
                $errors['Libelle'] = "Une sortie identique existe déjà.";
        }

        // Erreur
        if (empty($errors)) {
            $request = $db->prepare('UPDATE Sortie SET Libelle = :Libelle, CodT = :CodT, CodP = :CodP, KmSortie = :KmSortie WHERE Numero = :Numero');
            $request->execute(array(
                'Libelle'   => $inputs['Libelle'],
                'CodT'      => $inputs['CodT'],
                'CodP'      => $inputs['CodP'],
                'KmSortie'  => $inputs['KmSortie'],
                'Numero'    => $inputs['Numero']
            ));

            $data = $inputs;
            $_SESSION['flash']['success'] = "La sortie a été modifiée avec succès.";
            header('Location: sortie.php');
            die();
        }
    } else if (isset($_GET['Numero']) && isset($_GET['action']) && $_GET['action'] == 'supprimer' && !empty($_POST)) {
        if (isset($_POST['oui'])) {
            // Suppression de la sortie
            $request = $db->prepare("DELETE FROM Sortie WHERE Numero = :Numero");
            $request->execute(array(
                'Numero'  => $data['Numero']
            ));

            $_SESSION['flash']['success'] = "La sortie a été supprimée avec succès.";
            header('Location: sortie.php');
            die();
        }

        if (isset($_POST['non'])) {
            header('Location: sortie.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Sortie</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="sortie.php" class="active">Sortie</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash();
                                if (!isset($_GET['Numero']) && isset($_GET['action']) && $_GET['action'] == 'ajouter') : ?>
                                    <h3 class="box-title">Ajout d'une sortie</h3>
                                    <form class="form-horizontal" action='sortie.php?action=ajouter' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Libellé -->
                                            <div class="form-group <?php if (isset($errors['Libelle'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Libelle">Libellé</label>  
                                                <div class="col-md-5">
                                                    <input id="Libelle" name="Libelle" type="text" placeholder="Libellé" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Libelle']))
                                                            echo strip_tags(htmlspecialchars($errors['Libelle']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- CodT -->
                                            <div class="form-group <?php if (isset($errors['CodT'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodT">Tronçon</label>
                                                <div class="col-md-5">
                                                    <select id="CodT" name="CodT" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT * FROM Troncon ORDER BY CodA");
                                                        while ($donnees = $request->fetch())
                                                            echo '<option value="' . $donnees['CodT'] . '">' . strip_tags(htmlspecialchars($donnees['CodA'])) . ' : ' . strip_tags(htmlspecialchars($donnees['DuKm'])) . ' - ' . strip_tags(htmlspecialchars($donnees['AuKm'])) . '</option>';
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodT']))
                                                            echo $errors['CodT'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- CodP -->
                                            <div class="form-group <?php if (isset($errors['CodP'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodP">Ville</label>  
                                                <div class="col-md-5">
                                                    <select id="CodP" name="CodP" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT * FROM Ville ORDER BY CodP");
                                                        while ($donnees = $request->fetch())
                                                            echo '<option value="' . $donnees['CodP'] . '">' . strip_tags(htmlspecialchars($donnees['Nom'])) . '</option>';
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodP']))
                                                            echo strip_tags(htmlspecialchars($errors['CodP']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Kilomètre de la sortie -->
                                            <div class="form-group <?php if (isset($errors['KmSortie'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="KmSortie">Kilométrage de la sortie</label>  
                                                <div class="col-md-5">
                                                    <input id="KmSortie" name="KmSortie" type="text" placeholder="Kilométrage de la sortie" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['KmSortie']))
                                                            echo strip_tags(htmlspecialchars($errors['KmSortie']));
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
                                <?php elseif (isset($_GET['Numero']) && isset($_GET['action']) && $_GET['action'] == 'modifier') : ?>
                                    <h3 class="box-title">Modification de la sortie</h3>
                                    <form class="form-horizontal" action='sortie.php?Numero=<?php echo $data['Numero']; ?>&action=modifier' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Libellé -->
                                            <div class="form-group <?php if (isset($errors['Libelle'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Libelle">Libellé</label>  
                                                <div class="col-md-5">
                                                    <input id="Libelle" name="Libelle" type="text" placeholder="Libellé" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['Libelle'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Libelle']))
                                                            echo strip_tags(htmlspecialchars($errors['Libelle']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- CodT -->
                                            <div class="form-group <?php if (isset($errors['CodT'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodT">Tronçon</label>
                                                <div class="col-md-5">
                                                    <select id="CodT" name="CodT" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT * FROM Troncon ORDER BY CodA");
                                                        while ($donnees = $request->fetch()) {
                                                            echo '<option value="' . $donnees['CodT'] . '" ';
                                                            if ($donnees['CodT'] == $data['CodT'])
                                                                echo 'selected="selected"';
                                                            echo '>' . strip_tags(htmlspecialchars($donnees['CodA'])) . ' : ' . strip_tags(htmlspecialchars($donnees['DuKm'])) . ' - ' . strip_tags(htmlspecialchars($donnees['AuKm'])) . '</option>';
                                                        }
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodT']))
                                                            echo $errors['CodT'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- CodP -->
                                            <div class="form-group <?php if (isset($errors['CodP'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodP">Ville</label>  
                                                <div class="col-md-5">
                                                    <select id="CodP" name="CodP" class="form-control">
                                                    <?php
                                                        $request = $db->query("SELECT * FROM Ville ORDER BY CodP");
                                                        while ($donnees = $request->fetch()) {
                                                            echo '<option value="' . $donnees['CodP'] . '" ';
                                                            if ($donnees['CodP'] == $data['CodP'])
                                                                echo 'selected="selected"';
                                                            echo '>' . strip_tags(htmlspecialchars($donnees['Nom'])) . '</option>';
                                                        }
                                                    ?>
                                                    </select>
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodP']))
                                                            echo strip_tags(htmlspecialchars($errors['CodP']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Kilomètre de la sortie -->
                                            <div class="form-group <?php if (isset($errors['KmSortie'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="KmSortie">Kilométrage de la sortie</label>  
                                                <div class="col-md-5">
                                                    <input id="KmSortie" name="KmSortie" type="text" placeholder="Kilométrage de la sortie" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['KmSortie'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['KmSortie']))
                                                            echo strip_tags(htmlspecialchars($errors['KmSortie']));
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
                                <?php elseif (isset($_GET['Numero']) && isset($_GET['action']) && $_GET['action'] == 'supprimer') : ?>
                                    <h3 class="box-title">Suppression de la sortie</h3>
                                    <div class="row text-center">
                                        <p>Êtes-vous sûr de vouloir supprimer cette sortie ?</p>
                                        <form enctype="multipart/form-data" class="form-horizontal" method="post" action="sortie.php?Numero=<?php echo $data['Numero']; ?>&action=supprimer">
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
                                <?php elseif (!isset($_GET['Numero']) && !isset($_GET['action'])) : ?>
                                    <h3 class="box-title">Sorties <a href="sortie.php?action=ajouter"><span class="glyphicon glyphicon-plus pull-right"></span></a></h3>
                                    <div class="row text-center">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="col-lg-4 text-center">Libellé</th>
                                                    <th class="col-lg-2 text-center">Tronçon</th>
                                                    <th class="col-lg-2 text-center">Ville</th>
                                                    <th class="col-lg-2 text-center">Kilomètre de sortie</th>
                                                    <th class="col-lg-2 text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $request = $db->query("SELECT COUNT(Numero) AS nbSortie FROM Sortie");
                                                $data = $request->fetch();

                                                $nbSortie = $data['nbSortie'];
                                                $perPage = 5;
                                                $nbPage = ceil($nbSortie / $perPage);

                                                if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbPage)
                                                    $cPage = $_GET['page'];
                                                else
                                                    $cPage = 1;

                                                $request = $db->query("SELECT Sortie.Numero, Sortie.Libelle, Sortie.CodT, Sortie.CodP, Sortie.KmSortie, Autoroute.CodA, Troncon.DuKm, Troncon.AuKm, Ville.Nom FROM Sortie, Troncon, Autoroute, Ville WHERE Sortie.CodT = Troncon.CodT AND Troncon.CodA = Autoroute.CodA AND Sortie.CodP = Ville.CodP ORDER BY Numero, CodT, CodP, KmSortie DESC LIMIT " . (($cPage - 1) * $perPage) . ", $perPage");
                                                while ($data = $request->fetch()) {
                                                    echo '<tr>
                                                    <td>' . strip_tags(htmlspecialchars($data['Libelle'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['CodA'])) . ' : ' . strip_tags(htmlspecialchars($data['DuKm'])) . ' - ' . strip_tags(htmlspecialchars($data['AuKm'])) . ' km</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['Nom'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['KmSortie'])) . '</td>
                                                    <td><a href="sortie.php?Numero=' . $data['Numero'] . '&action=modifier"><span class="glyphicon glyphicon-pencil"></span></a>
                                                    <a href="sortie.php?Numero=' . $data['Numero'] . '&action=supprimer"><span class="glyphicon glyphicon-remove"></span></a>
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
                                                        echo '<li class="active"><a href="sortie.php?page=' . $i . '">' . $i . '</a></li>';
                                                    else
                                                        echo '<li><a href="sortie.php?page=' . $i . '">' . $i . '</a></li>';
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