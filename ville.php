<?php
    $title = "Ville";

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
        header('Location: ville.php');
        die();
    }

    if ((isset($_GET['CodP']) && !isset($_GET['Nom'])) || (!isset($_GET['CodP']) && isset($_GET['Nom']))) {
        $_SESSION['flash']['danger'] = "Des informations sont manquantes.";
        header('Location: ville.php');
        die();
    }

    if ((isset($_GET['CodP']) && isset($_GET['Nom']) && !isset($_GET['action'])) || (isset($_GET['CodP']) && isset($_GET['Nom']) && isset($_GET['action']) && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer')) {
        $_SESSION['flash']['danger'] = "Uniquement la modification et la suppression de cette ville est autorisée.";
        header('Location: ville.php');
        die();
    }

    if (isset($_GET['CodP']) && isset($_GET['Nom'])) {
        // Existence
        $request = $db->prepare("SELECT * FROM Ville WHERE CodP = :CodP AND Nom = :Nom");
        $request->execute(array(
            'CodP'  => $_GET['CodP'],
            'Nom'  => $_GET['Nom']
        ));
        if (!$data = $request->fetch()) {
            $_SESSION['flash']['danger'] = "Ce code postal et cette ville n'existe pas.";
            header('Location: ville.php');
            die();
        }
    }

    // TRAITEMENT
    if (!isset($_GET['CodP']) && !isset($_GET['Nom']) && isset($_GET['action']) && $_GET['action'] == 'ajouter' && !empty($_POST)) { // AJOUTER
        if (!empty($_POST['CodP']) && !empty($_POST['Nom'])) {
            $errors = array();

            // Code postal
            $codePostalType = array(
              "France" => "^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$"
            );

            if (array_key_exists("France", $codePostalType)) {
                if (!preg_match("/" . $codePostalType["France"] . "/i", $_POST['CodP']))
                    $errors['CodP'] = "Le code postal est invalide.";
            }

            // Nom de la ville
            if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['Nom']))
                $errors['Nom'] = "Le nom de la ville est invalide.";

            // Existence
            $request = $db->prepare("SELECT * FROM Ville WHERE CodP = :CodP OR Nom = :Nom");
            $request->execute(array(
                'CodP'  => $_POST['CodP'], 
                'Nom'   => $_POST['Nom']
            ));
            if ($donnees = $request->fetch())
                $errors['CodP'] = "Ce code postal ou cette ville existe déjà.";

            if (empty($errors)) {
                $request = $db->prepare("INSERT INTO Ville(CodP, Nom) VALUES(:CodP, :Nom)");
                $request->execute(array(
                    'CodP'  => $_POST['CodP'], 
                    'Nom'   => $_POST['Nom']
                ));

                $_SESSION['flash']['success'] = "La ville a été ajoutée avec succès.";
                header('Location: ville.php');
                die();
            }
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: ville.php?action=ajouter');
            die();
        }
    } else if (isset($_GET['CodP']) && isset($_GET['Nom']) && isset($_GET['action']) && $_GET['action'] == 'modifier' && !empty($_POST)) { // MODIFIER
        $errors = array();
        $inputs = array();

        $inputs = $data;

        // Code postal
        if (!empty($_POST['CodP']) && strcmp($_POST['CodP'], $inputs['CodP']) !== 0) {
            $codePostalType = array(
              "France" => "^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$"
            );

            if (array_key_exists("France", $codePostalType)) {
              if (!preg_match("/" . $codePostalType["France"] . "/i", $_POST['CodP']))
                $errors['CodP'] = "Le code postal est invalide.";
            }

            if (!isset($errors['CodP']))
                $inputs['CodP'] = $_POST['CodP'];
        }

        // Nom de la ville
        if (!empty($_POST['Nom']) && strcmp($_POST['Nom'], $inputs['Nom']) !== 0) {
            if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['Nom']))
                $errors['Nom'] = "Le nom de la ville est invalide.";

            if (!isset($errors['Nom']))
                $inputs['Nom'] = $_POST['Nom'];
        }

        // Existence
        if (strcmp($_POST['CodP'], $data['CodP']) !== 0) {
            $request = $db->prepare("SELECT * FROM Ville WHERE CodP = :NCodP AND CodP <> :CodP");
            $request->execute(array(
                'NCodP'  => $_POST['CodP'], 
                'CodP'   => $data['CodP']
            ));
            if ($donnees = $request->fetch())
                $errors['CodP'] = "Ce code postal existe déjà.";
        }
        if (strcmp($_POST['Nom'], $data['Nom']) !== 0) {
            $request = $db->prepare("SELECT * FROM Ville WHERE Nom = :NNom AND Nom <> :Nom");
            $request->execute(array(
                'NNom'  => $_POST['Nom'], 
                'Nom'   => $data['Nom']
            ));
            if ($donnees = $request->fetch())
                $errors['Nom'] = "Cette ville existe déjà.";
        }

        // Erreur
        if (empty($errors)) {
            $request = $db->prepare('UPDATE Ville SET CodP = :NCodP, Nom = :NNom WHERE CodP = :CodP AND Nom = :Nom');
            $request->execute(array(
                'NCodP'     => $inputs['CodP'],
                'NNom'      => $inputs['Nom'],
                'CodP'      => $data['CodP'],
                'Nom'       => $data['Nom']
            ));

            $data = $inputs;
            $_SESSION['flash']['success'] = "La ville a été modifiée avec succès.";
            header('Location: ville.php');
            die();
        }
    } else if (isset($_GET['CodP']) && isset($_GET['Nom']) && isset($_GET['action']) && $_GET['action'] == 'supprimer' && !empty($_POST)) {
        if (isset($_POST['oui'])) {
            // Suppression de la ville
            $request = $db->prepare("DELETE FROM Ville WHERE CodP = :CodP AND Nom = :Nom");
            $request->execute(array(
                'CodP'  => $data['CodP'],
                'Nom'   => $data['Nom']
            ));

            $_SESSION['flash']['success'] = "La ville a été supprimée avec succès.";
            header('Location: ville.php');
            die();
        }

        if (isset($_POST['non'])) {
            header('Location: ville.php');
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
                                <li><a href="ville.php" class="active">Ville</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash();
                                if (!isset($_GET['CodP']) && !isset($_GET['Nom']) && isset($_GET['action']) && $_GET['action'] == 'ajouter') : ?>
                                    <h3 class="box-title">Ajout d'une ville</h3>
                                    <form class="form-horizontal" action='ville.php?action=ajouter' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Code postal -->
                                            <div class="form-group <?php if (isset($errors['CodP'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodP">Code postal</label>  
                                                <div class="col-md-5">
                                                    <input id="CodP" name="CodP" type="text" placeholder="Code postal" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodP']))
                                                            echo strip_tags(htmlspecialchars($errors['CodP']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
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
                                                    <input type="submit" class="btn btn-success btn-block" value="Ajouter" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                <?php elseif (isset($_GET['CodP']) && isset($_GET['Nom']) && isset($_GET['action']) && $_GET['action'] == 'modifier') : ?>
                                    <h3 class="box-title">Modification de la ville : <?php echo strip_tags(htmlspecialchars($data['Nom'])); ?></h3>
                                    <form class="form-horizontal" action='ville.php?CodP=<?php echo $data['CodP']; ?>&Nom=<?php echo $data['Nom']; ?>&action=modifier' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Code postal -->
                                            <div class="form-group <?php if (isset($errors['CodP'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodP">Code postal</label>  
                                                <div class="col-md-5">
                                                    <input id="CodP" name="CodP" type="text" placeholder="Code postal" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['CodP'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodP']))
                                                            echo strip_tags(htmlspecialchars($errors['CodP']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Ville -->
                                            <div class="form-group <?php if (isset($errors['Nom'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Nom">Nom de la ville</label>  
                                                <div class="col-md-5">
                                                    <input id="Nom" name="Nom" type="text" placeholder="Nom de la ville" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['Nom'])); ?>">
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
                                                    <input type="submit" class="btn btn-success btn-block" value="Modifier" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                <?php elseif (isset($_GET['CodP']) && isset($_GET['Nom']) && isset($_GET['action']) && $_GET['action'] == 'supprimer') : ?>
                                    <h3 class="box-title">Suppression de la ville : <?php echo strip_tags(htmlspecialchars($data['Nom'])); ?></h3>
                                    <div class="row text-center">
                                        <p>Êtes-vous sûr de vouloir supprimer cette ville ?</p>
                                        <form enctype="multipart/form-data" class="form-horizontal" method="post" action="ville.php?CodP=<?php echo $data['CodP']; ?>&Nom=<?php echo $data['Nom']; ?>&action=supprimer">
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
                                <?php elseif (!isset($_GET['CodP']) && !isset($_GET['Nom']) && !isset($_GET['action'])) : ?>
                                    <h3 class="box-title">Villes <a href="ville.php?action=ajouter"><span class="glyphicon glyphicon-plus pull-right"></span></a></h3>
                                    <div class="row text-center">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="col-lg-2 text-center">Code postal</th>
                                                    <th class="col-lg-8 text-center">Nom de la ville</th>
                                                    <th class="col-lg-2 text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $request = $db->query("SELECT COUNT(Nom) AS nbVille FROM Ville");
                                                $data = $request->fetch();

                                                $nbVille = $data['nbVille'];
                                                $perPage = 5;
                                                $nbPage = ceil($nbVille / $perPage);

                                                if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbPage)
                                                    $cPage = $_GET['page'];
                                                else
                                                    $cPage = 1;

                                                $request = $db->query("SELECT * FROM Ville ORDER BY CodP, Nom DESC LIMIT " . (($cPage - 1) * $perPage) . ", $perPage");
                                                while ($data = $request->fetch()) {
                                                    echo '<tr>
                                                    <td>' . strip_tags(htmlspecialchars($data['CodP'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['Nom'])) . '</td>
                                                    <td><a href="ville.php?CodP=' . $data['CodP'] . '&Nom=' . $data['Nom'] . '&action=modifier"><span class="glyphicon glyphicon-pencil"></span></a>
                                                    <a href="ville.php?CodP=' . $data['CodP'] . '&Nom=' . $data['Nom'] . '&action=supprimer"><span class="glyphicon glyphicon-remove"></span></a>
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
                                                        echo '<li class="active"><a href="ville.php?page=' . $i . '">' . $i . '</a></li>';
                                                    else
                                                        echo '<li><a href="ville.php?page=' . $i . '">' . $i . '</a></li>';
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