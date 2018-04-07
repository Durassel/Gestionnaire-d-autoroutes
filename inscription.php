<?php
    $title = "Accueil";

    include_once "includes/db.php";
    include_once "includes/functions.php";

    // Traitement
    if (connecte()) {
        $_SESSION['flash']['danger'] = "Vous êtes déjà connecté.";
        header('Location: index.php');
        die();
    }

    $errors = array();
    // Formulaire complet
    if (!empty($_POST['prenom']) && !empty($_POST['nom']) && !empty($_POST['email']) && !empty($_POST['confirm_email']) && !empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        // Prénom
        if (strlen($_POST['prenom']) > 255)
            $errors['taillePrenom'] = "Votre prénom est trop long.";
        if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['prenom']))
            $errors['prenom'] = "Votre prénom n'est pas valide.";

        // Nom
        if (strlen($_POST['nom']) > 255)
            $errors['tailleNom'] = "Votre nom est trop long.";
        if (!preg_match('/^[a-zA-ZÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\s]+$/', $_POST['nom']))
            $errors['nom'] = "Votre nom n'est pas valide.";

        // Email
        $result = $db->query('SELECT email FROM utilisateur');
        while ($data = $result->fetch()) {
            if (strcasecmp($data['email'], $_POST['email']) == 0)
                $errors['emailUtilise'] = "Votre email est déjà utilisé.";
        }
        if (strlen($_POST['email']) > 255)
            $errors['tailleEmail'] = "Votre email est trop long.";
        if (strcmp($_POST['email'], $_POST['confirm_email']) !== 0)
            $errors['emailDifferent'] = "Votre email et sa confirmation sont différents.";
        if (!preg_match("#^[a-zA-Z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#", $_POST['email']))
            $errors['email'] = "Votre email n'est pas un email.";

        // Password
        if (strlen($_POST['password']) > 255)
            $errors['taillePassword'] = "Votre mot de passe est trop long.";
        if (strcmp($_POST['password'], $_POST['confirm_password']) !== 0)
            $errors['passwordDifferent'] = "Votre mot de passe et sa confirmation sont différents.";

        // Erreur
        if (empty($errors)) {
            $request = $db->prepare('INSERT INTO utilisateur(Prenom, Nom, Email, Password, Statut) VALUES(:prenom, :nom, :email, :password, :statut)');
            $request->execute(array(
            'prenom'        => $_POST['prenom'],
            'nom'           => $_POST['nom'],
            'email'         => $_POST['email'],
            'password'      => sha1($grain.$_POST['password'].$salt),
            'statut'        => 'Visiteur'
            ));

            $_SESSION['flash']['success'] = "Vous êtes maintenant inscrit.";
            header('Location: connexion.php');
            die();
        }
    } else if(isset($_POST['prenom']) || isset($_POST['nom']) || isset($_POST['email']) || isset($_POST['confirm_email']) || isset($_POST['password']) || isset($_POST['confirm_password'])) {
        $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
        header('Location: inscription.php');
        die();
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Inscription</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="#">Accueil</a></li>
                                <li class="active">Inscription</li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <h3 class="box-title">Inscription</h3>
                                <?php echo flash(); ?>
                                <div class="container">
                                    <div class="" id="loginModal">
                                        <div class="modal-body">
                                            <div class="well">
                                                <ul class="nav nav-tabs">
                                                    <li><a href="connexion.php">Connexion</a></li>
                                                    <li class="active"><a href="inscription.php">Inscription</a></li>
                                                </ul>
                                                    <form class="form-horizontal" action='inscription.php' method="POST">
                                                        <fieldset> 
                                                            <legend></legend>
                                                            <!-- Prénom -->
                                                            <div class="form-group <?php if (isset($errors['taillePrenom']) || isset($errors['prenom'])) echo "has-error"; ?>">
                                                                <label class="col-md-4 control-label" for="prenom">Prénom</label>  
                                                                <div class="col-md-5">
                                                                    <input id="prenom" name="prenom" type="text" placeholder="Prénom" class="form-control input-md" required="">
                                                                    <span class="help-block">
                                                                        <?php
                                                                        if (isset($errors['taillePrenom']))
                                                                            echo strip_tags(htmlspecialchars($errors['taillePrenom']));
                                                                        if (isset($errors['prenom']))
                                                                            echo strip_tags(htmlspecialchars($errors['prenom']));
                                                                        ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <!-- Nom -->
                                                            <div class="form-group <?php if (isset($errors['tailleNom']) || isset($errors['nom'])) echo "has-error"; ?>">
                                                                <label class="col-md-4 control-label" for="nom">Nom</label>  
                                                                <div class="col-md-5">
                                                                    <input id="nom" name="nom" type="text" placeholder="Nom" class="form-control input-md" required="">
                                                                    <span class="help-block">
                                                                        <?php
                                                                        if (isset($errors['tailleNom']))
                                                                            echo strip_tags(htmlspecialchars($errors['tailleNom']));
                                                                        if (isset($errors['nom']))
                                                                            echo strip_tags(htmlspecialchars($errors['nom']));
                                                                        ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <!-- Email-->
                                                            <div class="form-group <?php if (isset($errors['tailleEmail']) || isset($errors['emailUtilise']) || isset($errors['email'])) echo "has-error"; ?>">
                                                                <label class="col-md-4 control-label" for="email">Email</label>  
                                                                <div class="col-md-5">
                                                                    <input id="email" name="email" type="email" placeholder="Email" class="form-control input-md" required="">
                                                                    <span class="help-block">
                                                                        <?php
                                                                        if (isset($errors['tailleEmail']))
                                                                            echo strip_tags(htmlspecialchars($errors['tailleEmail']));
                                                                        if (isset($errors['emailUtilise']))
                                                                            echo strip_tags(htmlspecialchars($errors['emailUtilise']));
                                                                        if (isset($errors['email']))
                                                                            echo strip_tags(htmlspecialchars($errors['email']));
                                                                        ?>
                                                                </span>
                                                                </div>
                                                            </div>
                                                            <!-- Confirmation Email -->
                                                            <div class="form-group <?php if (isset($errors['emailDifferent'])) echo "has-error"; ?>">
                                                                <label class="col-md-4 control-label" for="confirm_email">Confirmation de l'Email</label>
                                                                <div class="col-md-5">
                                                                    <input id="confirm_email" name="confirm_email" type="email" placeholder="Retapez l'email" class="form-control input-md" required="">
                                                                    <span class="help-block">
                                                                        <?php
                                                                        if (isset($errors['emailDifferent']))
                                                                            echo strip_tags(htmlspecialchars($errors['emailDifferent']));
                                                                        ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <!-- Mot de passe -->
                                                            <div class="form-group <?php if (isset($errors['taillePassword'])) echo "has-error"; ?>">
                                                                <label class="col-md-4 control-label" for="password">Mot de passe</label>
                                                                <div class="col-md-5">
                                                                    <input id="password" name="password" type="password" placeholder="Mot de passe" class="form-control input-md" required="">
                                                                    <span class="help-block">
                                                                        <?php
                                                                        if (isset($errors['taillePassword']))
                                                                            echo strip_tags(htmlspecialchars($errors['taillePassword']));
                                                                        ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <!-- Confirmation mot de passe -->
                                                            <div class="form-group <?php if (isset($errors['passwordDifferent'])) echo "has-error"; ?>">
                                                                <label class="col-md-4 control-label" for="confirm_password">Confirmation du mot de passe</label>
                                                                <div class="col-md-5">
                                                                    <input id="confirm_password" name="confirm_password" type="password" placeholder="Retapez le mot de passe" class="form-control input-md" required="">
                                                                    <span class="help-block">
                                                                        <?php
                                                                        if (isset($errors['passwordDifferent']))
                                                                            echo strip_tags(htmlspecialchars($errors['passwordDifferent']));
                                                                        ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <!-- Button -->
                                                            <div class="form-group text-center">
                                                                <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                                    <input type="submit" class="btn btn-success btn-block" value="Inscription" />
                                                                </div>
                                                            </div>
                                                        </fieldset>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
<?php include_once "includes/footer.php"; ?>