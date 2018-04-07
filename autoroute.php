<?php
    $title = "Autoroute";

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
        header('Location: autoroute.php');
        die();
    }

    if (isset($_GET['CodA'])) {
        // Existance
        $request = $db->prepare("SELECT * FROM Autoroute WHERE CodA = :CodA");
        $request->execute(array(
            'CodA'  => $_GET['CodA']
        ));
        if (!$data = $request->fetch()) {
            $_SESSION['flash']['danger'] = "Cette autoroute n'existe pas.";
            header('Location: autoroute.php');
            die();
        }
    }

    if (isset($_GET['CodA']) && isset($_GET['action']) && $_GET['action'] != 'modifier' && $_GET['action'] != 'supprimer') {
        $_SESSION['flash']['danger'] = "Uniquement la modification et la suppression de cette autoroute est autorisée.";
        header('Location: autoroute.php');
        die();
    }

    if (isset($_GET['CodA']) && !isset($_GET['action'])) {
        $_SESSION['flash']['danger'] = "Des informations sont manquantes.";
        header('Location: autoroute.php');
        die();
    }

    // TRAITEMENT
    if (!isset($_GET['CodA']) && isset($_GET['action']) && $_GET['action'] == 'ajouter' && !empty($_POST)) { // AJOUTER
        if (!empty($_POST['CodA']) && !empty($_POST['AuKm'])) {
            $errors = array();

            // Code de l'autoroute
            if (!preg_match('/^A[0-9]+$/', $_POST['CodA']))
                $errors['CodA'] = "Le nom de l'autoroute est invalide.";
            // Existence
            $request = $db->prepare("SELECT * FROM Autoroute WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'   => $_POST['CodA']
            ));
            if ($donnees = $request->fetch()) {
                $errors['CodAExiste'] = "Le nom de cette autoroute existe déjà.";
            }

            // Fin de kilométrage de l'autoroute
            if (!is_numeric($_POST['AuKm']))
                $errors['AuKm'] = "Le kilométrage n'est pas un nombre.";
            else {
                if (intval($_POST['AuKm']) < 2)
                    $errors['AuKm'] = "La fin du kilométrage de l'autoroute doit être supérieur à 1.";
            }

            if (empty($errors)) {
                $request = $db->prepare("INSERT INTO Autoroute(CodA, DuKm, AuKm) VALUES(:CodA, :DuKm, :AuKm)");
                $request->execute(array(
                    'CodA'  => $_POST['CodA'], 
                    'DuKm'  => '1',
                    'AuKm'  => $_POST['AuKm']
                ));

                $_SESSION['flash']['success'] = "L'autoroute a été ajoutée avec succès.";
                header('Location: autoroute.php');
                die();
            }
        } else {
            $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
            header('Location: autoroute.php?action=ajouter');
            die();
        }
    } else if (isset($_GET['CodA']) && isset($_GET['action']) && $_GET['action'] == 'modifier' && !empty($_POST)) { // MODIFIER
        $errors = array();
        $inputs = array();

        $inputs = $data;

        // Fin de kilométrage de l'autoroute
        if (!empty($_POST['AuKm']) && strcmp($_POST['AuKm'], $inputs['AuKm']) !== 0) {
            if (!is_numeric($_POST['AuKm']))
                $errors['AuKm'] = "La fin de kilométrage de l'autoroute n'est pas un nombre.";
            else {
                if (intval($_POST['AuKm']) < 2)
                    $errors['AuKm'] = "La fin du kilométrage de l'autoroute doit être supérieur à 1.";
            }

            if (!isset($errors['AuKm']))
                $inputs['AuKm'] = $_POST['AuKm'];
        }

        // Code de l'autoroute
        if (!empty($_POST['CodA']) && strcmp($_POST['CodA'], $inputs['CodA']) !== 0) {
            if (!preg_match('/^A[0-9]+$/', $_POST['CodA']))
                $errors['CodA'] = "Le code de l'autoroute est invalide.";

            // Existence
            $request = $db->prepare("SELECT * FROM Autoroute WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'   => $_POST['CodA']
            ));
            if ($donnees = $request->fetch())
                $errors['CodAExiste'] = "Le code de cette autoroute existe déjà.";

            if (!isset($errors['CodA']) && !isset($errors['CodAExiste']))
                $inputs['CodA'] = $_POST['CodA'];
        }

        // Erreur
        if (empty($errors)) {
            $request = $db->prepare('UPDATE Autoroute SET CodA = :NCodA, AuKm = :AuKm WHERE CodA = :CodA');
            $request->execute(array(
                'NCodA' => $inputs['CodA'],
                'AuKm'  => $inputs['AuKm'],
                'CodA'  => $data['CodA']
            ));

            $data = $inputs;
            $_SESSION['flash']['success'] = "L'autoroute a été modifiée avec succès.";
            header('Location: autoroute.php');
            die();
        }
    } else if (isset($_GET['CodA']) && isset($_GET['action']) && $_GET['action'] == 'supprimer' && !empty($_POST)) {
        if (isset($_POST['oui'])) {
            // Suppression de l'autoroute
            $request = $db->prepare("DELETE FROM Autoroute WHERE CodA = :CodA");
            $request->execute(array(
                'CodA'  => $data['CodA']
            ));

            $_SESSION['flash']['success'] = "L'autoroute a été supprimée avec succès.";
            header('Location: autoroute.php');
            die();
        }

        if (isset($_POST['non'])) {
            header('Location: autoroute.php');
            die();
        }
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Autoroute</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="index.php">Accueil</a></li>
                                <li><a href="autoroute.php" class="active">Autoroute</a></li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <?php echo flash();
                                if (!isset($_GET['CodA']) && isset($_GET['action']) && $_GET['action'] == 'ajouter') : ?>
                                    <h3 class="box-title">Ajout d'une autoroute</h3>
                                    <form class="form-horizontal" action='autoroute.php?action=ajouter' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Code de l'autoroute -->
                                            <div class="form-group <?php if (isset($errors['CodA']) || isset($errors['CodAExiste'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodA">Code de l'autoroute</label>  
                                                <div class="col-md-5">
                                                    <input id="CodA" name="CodA" type="text" placeholder="Code de l'autoroute" class="form-control input-md" required="">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodA']))
                                                            echo strip_tags(htmlspecialchars($errors['CodA']));
                                                        if (isset($errors['CodAExiste']))
                                                            echo strip_tags(htmlspecialchars($errors['CodAExiste']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Fin kilomètre -->
                                            <div class="form-group <?php if (isset($errors['AuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="AuKm">Kilométrage de fin d'autoroute</label>  
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
                                            <!-- Button -->
                                            <div class="form-group text-center">
                                                <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                    <input type="submit" class="btn btn-success btn-block" value="Ajouter" />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                <?php elseif (isset($_GET['CodA']) && isset($_GET['action']) && $_GET['action'] == 'modifier') : ?>
                                    <h3 class="box-title">Modification de l'autoroute : <?php echo strip_tags(htmlspecialchars($data['CodA'])); ?></h3>
                                    <form class="form-horizontal" action='autoroute.php?CodA=<?php echo $data['CodA']; ?>&action=modifier' method="POST">
                                        <fieldset> 
                                            <legend></legend>
                                            <!-- Code de l'autoroute -->
                                            <div class="form-group <?php if (isset($errors['CodA']) || isset($errors['CodAExiste'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="CodA">Code de l'autoroute</label>  
                                                <div class="col-md-5">
                                                    <input id="CodA" name="CodA" type="text" placeholder="Code de l'autoroute" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['CodA'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['CodA']))
                                                            echo strip_tags(htmlspecialchars($errors['CodA']));
                                                        if (isset($errors['CodAExiste']))
                                                            echo strip_tags(htmlspecialchars($errors['CodAExiste']));
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Fin kilomètre -->
                                            <div class="form-group <?php if (isset($errors['AuKm'])) echo "has-error"; ?>">
                                                <label class="col-md-4 control-label" for="AuKm">Kilométrage de fin d'autoroute</label>  
                                                <div class="col-md-5">
                                                    <input id="AuKm" name="AuKm" type="text" placeholder="Kilométrage de fin d'autoroute" class="form-control input-md" value="<?php echo strip_tags(htmlspecialchars($data['AuKm'])); ?>">
                                                    <span class="help-block">
                                                        <?php
                                                        if (isset($errors['AuKm']))
                                                            echo strip_tags(htmlspecialchars($errors['AuKm']));
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
                                <?php elseif (isset($_GET['CodA']) && isset($_GET['action']) && $_GET['action'] == 'supprimer') : ?>
                                    <h3 class="box-title">Suppression de l'autoroute : <?php echo strip_tags(htmlspecialchars($data['CodA'])); ?></h3>
                                    <div class="row text-center">
                                        <p>Êtes-vous sûr de vouloir supprimer cette autoroute ?</p>
                                        <form enctype="multipart/form-data" class="form-horizontal" method="post" action="autoroute.php?CodA=<?php echo $data['CodA']; ?>&action=supprimer">
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
                                <?php elseif (!isset($_GET['CodA']) && !isset($_GET['action'])) : ?>
                                    <h3 class="box-title">Autoroutes <a href="autoroute.php?action=ajouter"><span class="glyphicon glyphicon-plus pull-right"></span></a></h3>
                                    <div class="row text-center">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="col-lg-6 text-center">Code de l'autoroute</th>
                                                    <th class="col-lg-2 text-center">Début kilomètre</th>
                                                    <th class="col-lg-2 text-center">Fin kilomètre</th>
                                                    <th class="col-lg-2 text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $request = $db->query("SELECT COUNT(CodA) AS nbAutoroute FROM Autoroute");
                                                $data = $request->fetch();

                                                $nbAutoroute = $data['nbAutoroute'];
                                                $perPage = 5;
                                                $nbPage = ceil($nbAutoroute / $perPage);

                                                if (isset($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nbPage)
                                                    $cPage = $_GET['page'];
                                                else
                                                    $cPage = 1;

                                                $request = $db->query("SELECT * FROM Autoroute ORDER BY CodA DESC LIMIT " . (($cPage - 1) * $perPage) . ", $perPage");
                                                while ($data = $request->fetch()) {
                                                    echo '<tr>
                                                    <td>' . strip_tags(htmlspecialchars($data['CodA'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['DuKm'])) . '</td>
                                                    <td>' . strip_tags(htmlspecialchars($data['AuKm'])) . '</td>
                                                    <td><a href="autoroute.php?CodA=' . $data['CodA'] . '&action=modifier"><span class="glyphicon glyphicon-pencil"></span></a>
                                                    <a href="autoroute.php?CodA=' . $data['CodA'] . '&action=supprimer"><span class="glyphicon glyphicon-remove"></span></a>
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
                                                        echo '<li class="active"><a href="autoroute.php?page=' . $i . '">' . $i . '</a></li>';
                                                    else
                                                        echo '<li><a href="autoroute.php?page=' . $i . '">' . $i . '</a></li>';
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