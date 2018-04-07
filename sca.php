<?php
    $title = "SCA";

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
        header('Location: sca.php');
        die();
    }

    if (isset($_GET['Code'])) {
        // Existence
        $request = $db->prepare("SELECT * FROM Sca WHERE Code = :Code");
        $request->execute(array(
            'Code'  => $_GET['Code']
        ));
        if (!$data = $request->fetch()) {
            $_SESSION['flash']['danger'] = "Cette société n'existe pas.";
            header('Location: sca.php');
            die();
        }
    }

    if (isset($_GET['Code']) && isset($_GET['action']) && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer') {
        $_SESSION['flash']['danger'] = "Uniquement la modification et la suppression de cette société est autorisée.";
        header('Location: sca.php');
        die();
    }

    if (isset($_GET['Code']) && !isset($_GET['action'])) {
        $_SESSION['flash']['danger'] = "Des informations sont manquantes.";
        header('Location: sca.php');
        die();
    }

    // TRAITEMENT
    if (!isset($_GET['Code']) && isset($_GET['action']) && $_GET['action'] == 'ajouter' && !empty($_POST)) { // AJOUTER
        if (!empty($_POST['Nom']) && !empty($_POST['CA']) && !empty($_POST['Duree_Contrat'])) {
            $errors = array();

            // Nom de la société
            if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['Nom']))
                $errors['Nom'] = "Le nom de la société est invalide.";
            // Existence
            $request = $db->prepare("SELECT * FROM Sca WHERE Nom = :Nom");
            $request->execute(array(
                'Nom'   => $_POST['Nom']
            ));
            if ($donnees = $request->fetch()) {
                $errors['NomExiste'] = "Le nom de cette société existe déjà.";
            }

            // Chiffre d'affaires
            if (!is_numeric($_POST['CA']))
                $errors['CA'] = "Le chiffre d'affaires n'est pas un nombre.";

            // Durée du contrat
            if (!is_numeric($_POST['Duree_Contrat']))
                $errors['Duree_Contrat'] = "La durée du contrat doit correspondre à un nombre d'années.";

            if (empty($errors)) {
                $request = $db->prepare("INSERT INTO Sca(Nom, CA, Duree_Contrat) VALUES(:Nom, :CA, :Duree_Contrat)");
                $request->execute(array(
                    'Nom'           => $_POST['Nom'], 
                    'CA'            => $_POST['CA'],
                    'Duree_Contrat' => $_POST['Duree_Contrat']
                ));

                $_SESSION['flash']['success'] = "La société a été ajoutée avec succès.";
                header('Location: sca.php');
                die();
            }
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: sca.php?action=ajouter');
            die();
        }
    } else if (isset($_GET['Code']) && isset($_GET['action']) && $_GET['action'] == 'modifier' && !empty($_POST)) { // MODIFIER
        $errors = array();
        $inputs = array();

        $inputs = $data;

        // Chiffre d'affaires
        if (!empty($_POST['CA']) && strcmp($_POST['CA'], $inputs['CA']) !== 0) {
            if (!is_numeric($_POST['CA']))
                $errors['CA'] = "Le chiffre d'affaires n'est pas un nombre.";

            if (!isset($errors['CA']))
                $inputs['CA'] = $_POST['CA'];
        }

        // Durée du contrat
        if (!empty($_POST['Duree_Contrat']) && strcmp($_POST['Duree_Contrat'], $inputs['Duree_Contrat']) !== 0) {
            if (!is_numeric($_POST['Duree_Contrat']))
                $errors['Duree_Contrat'] = "La durée du contrat doit correspondre à un nombre d'années.";

            if (!isset($errors['Duree_Contrat']))
                $inputs['Duree_Contrat'] = $_POST['Duree_Contrat'];
        }

        // Nom de la société
        if (!empty($_POST['Nom']) && strcmp($_POST['Nom'], $inputs['Nom']) !== 0) {
            if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['Nom']))
                $errors['Nom'] = "Le nom de la société est invalide.";

            // Existence
            $request = $db->prepare("SELECT * FROM Sca WHERE Nom = :Nom");
            $request->execute(array(
                'Nom'   => $_POST['Nom']
            ));
            if ($donnees = $request->fetch())
                $errors['Nom'] = "Le nom de cette société existe déjà.";

            if (!isset($errors['Nom']))
                $inputs['Nom'] = $_POST['Nom'];
        }

        // Erreur
        if (empty($errors)) {
            $request = $db->prepare('UPDATE Sca SET Nom = :Nom, CA = :CA, Duree_Contrat = :Duree_Contrat WHERE CodE = :Code');
            $request->execute(array(
                'Code'          => $inputs['Code'],
                'Nom'           => $inputs['Nom'],
                'CA'            => $inputs['CA'],
                'Duree_Contrat' => $inputs['Duree_Contrat']
            ));

            $data = $inputs;
            $_SESSION['flash']['success'] = "La société a été modifiée avec succès.";
            header('Location: sca.php');
            die();
        }
    } else if (isset($_GET['Code']) && isset($_GET['action']) && $_GET['action'] == 'supprimer' && !empty($_POST)) {
        if (isset($_POST['oui'])) {
            // Suppression de la société
            $request = $db->prepare("DELETE FROM Sca WHERE Code = :Code");
            $request->execute(array(
                'Code'  => $data['Code']
            ));

            $_SESSION['flash']['success'] = "La société a été supprimée avec succès.";
            header('Location: sca.php');
            die();
        }

        if (isset($_POST['non'])) {
            header('Location: sca.php');
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
                                <li><a href="sca.php" class="active">SCA</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash();
                                if (!isset($_GET['Code']) && isset($_GET['action']) && $_GET['action'] == 'ajouter') : ?>
                                    <h3 class="box-title">Ajout d'une société</h3>
                                    <form class="form-horizontal" action='sca.php?action=ajouter' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Nom de la société -->
                                            <div class="form-group <?php if (isset($errors['Nom']) || isset($errors['NomExiste'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Nom">Nom de la société</label>  
                                                <div class="col-md-5">
                                                    <input id="Nom" name="Nom" type="text" placeholder="Nom de la société" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Nom']))
                                                            echo strip_tags(htmlspecialchars($errors['Nom']));
                                                        if (isset($errors['NomExiste']))
                                                            echo strip_tags(htmlspecialchars($errors['NomExiste']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Chiffre d'affaires -->
                                            <div class="form-group <?php if (isset($errors['CA'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CA">Chiffre d'affaires</label>  
                                                <div class="col-md-5">
                                                    <input id="CA" name="CA" type="text" placeholder="Chiffre d'affaires" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CA']))
                                                            echo strip_tags(htmlspecialchars($errors['CA']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Durée du contrat -->
                                            <div class="form-group <?php if (isset($errors['Duree_Contrat'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Duree_Contrat">Durée du contrat</label>  
                                                <div class="col-md-5">
                                                    <input id="Duree_Contrat" name="Duree_Contrat" type="text" placeholder="Durée du contrat" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Duree_Contrat']))
                                                            echo strip_tags(htmlspecialchars($errors['Duree_Contrat']));
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
                                <?php elseif (isset($_GET['Code']) && isset($_GET['action']) && $_GET['action'] == 'modifier') : ?>
                                    <h3 class="box-title">Modification de la ville : <?php echo strip_tags(htmlspecialchars($data['Nom'])); ?></h3>
                                    <form class="form-horizontal" action='sca.php?Code=<?php echo $data['Code']; ?>&action=modifier' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Nom de la société -->
                                            <div class="form-group <?php if (isset($errors['Nom']) || isset($errors['NomExiste'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Nom">Nom de la société</label>  
                                                <div class="col-md-5">
                                                    <input id="Nom" name="Nom" type="text" placeholder="Nom de la société" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['Nom'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Nom']))
                                                            echo strip_tags(htmlspecialchars($errors['Nom']));
                                                        if (isset($errors['NomExiste']))
                                                            echo strip_tags(htmlspecialchars($errors['NomExiste']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Chiffre d'affaires -->
                                            <div class="form-group <?php if (isset($errors['CA'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CA">Chiffre d'affaires</label>  
                                                <div class="col-md-5">
                                                    <input id="CA" name="CA" type="text" placeholder="Chiffre d'affaires" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['CA'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CA']))
                                                            echo strip_tags(htmlspecialchars($errors['CA']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Durée du contrat -->
                                            <div class="form-group <?php if (isset($errors['Duree_Contrat'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="Duree_Contrat">Durée du contrat</label>  
                                                <div class="col-md-5">
                                                    <input id="Duree_Contrat" name="Duree_Contrat" type="text" placeholder="Durée du contrat" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['Duree_Contrat'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['Duree_Contrat']))
                                                            echo strip_tags(htmlspecialchars($errors['Duree_Contrat']));
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
                                <?php elseif (isset($_GET['Code']) && isset($_GET['action']) && $_GET['action'] == 'supprimer') : ?>
                                    <h3 class="box-title">Suppression de la société : <?php echo strip_tags(htmlspecialchars($data['Nom'])); ?></h3>
                                    <div class="row text-center">
                                        <p>Êtes-vous sûr de vouloir supprimer cette société ?</p>
                                        <form enctype="multipart/form-data" class="form-horizontal" method="post" action="sca.php?Code=<?php echo $data['Code']; ?>&action=supprimer">
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
                                <?php elseif (!isset($_GET['Code']) && !isset($_GET['action'])) : ?>
                                    <h3 class="box-title">Sociétés <a href="sca.php?action=ajouter"><span class="glyphicon glyphicon-plus pull-right"></span></a></h3>
                                    <div class="row text-center">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="col-lg-6 text-center">Nom</th>
                                                    <th class="col-lg-2 text-center">Chiffre d'affaires</th>
                                                    <th class="col-lg-2 text-center">Durée du contrat</th>
                                                    <th class="col-lg-2 text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $request = $db->query("SELECT COUNT(Code) AS nbSca FROM Sca");
                                                $data = $request->fetch();

                                                $nbSca = $data['nbSca'];
                                                $perPage = 5;
                                                $nbPage = ceil($nbSca / $perPage);

                                                if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbPage)
                                                    $cPage = $_GET['page'];
                                                else
                                                    $cPage = 1;

                                                $request = $db->query("SELECT * FROM Sca ORDER BY Code DESC LIMIT " . (($cPage - 1) * $perPage) . ", $perPage");
                                                while ($data = $request->fetch()) {
                                                    echo '<tr>
                                                    <td>' . strip_tags(htmlspecialchars($data['Nom'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['CA'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['Duree_Contrat'])) . '</td>
                                                    <td><a href="sca.php?Code=' . $data['Code'] . '&action=modifier"><span class="glyphicon glyphicon-pencil"></span></a>
                                                    <a href="sca.php?Code=' . $data['Code'] . '&action=supprimer"><span class="glyphicon glyphicon-remove"></span></a>
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
                                                        echo '<li class="active"><a href="sca.php?page=' . $i . '">' . $i . '</a></li>';
                                                    else
                                                        echo '<li><a href="sca.php?page=' . $i . '">' . $i . '</a></li>';
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