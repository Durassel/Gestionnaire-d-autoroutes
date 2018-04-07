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

    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $password = sha1($grain.$_POST['password'].$salt);

        $request = $db->prepare('SELECT * FROM utilisateur WHERE Email = :email AND Password = :password');
        $request->execute(array(
            'email'     => $email,
            'password'  => $password
        ));
        $data = $request->fetch();

        if ($data) {
            $_SESSION['auth'] = $data;
            $_SESSION['flash']['success'] = "Vous êtes maintenant connecté.";
            header('Location: index.php');
            die();
        } else {
            $_SESSION['flash']['danger'] = "Votre identifiant ou votre mot de passe est incorrect.";
            header('Location: connexion.php');
            die();
        }
    } else if (!empty($_POST) && (empty($_POST['email']) || empty($_POST['password']))) {
        $_SESSION['flash']['danger'] = "Veuillez remplir l'ensemble du formulaire.";
        header('Location: connexion.php');
        die();
    }

    include_once "includes/header.php";
?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="row bg-title">
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                            <h4 class="page-title">Connexion</h4> </div>
                        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                            <ol class="breadcrumb">
                                <li><a href="#">Accueil</a></li>
                                <li class="active">Connexion</li>
                            </ol>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="white-box">
                                <h3 class="box-title">Connexion</h3>
                                <?php echo flash(); ?>
                                <div class="container">
                                    <div class="" id="loginModal">
                                        <div class="modal-body">
                                            <div class="well">
                                                <ul class="nav nav-tabs">
                                                    <li class="active"><a href="connexion.php">Connexion</a></li>
                                                    <li><a href="inscription.php">Inscription</a></li>
                                                </ul>
                                                    <form class="form-horizontal" action='connexion.php' method="POST">
                                                        <fieldset> 
                                                            <legend></legend>
                                                            <!-- Email-->
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label" for="email">Email</label>  
                                                                <div class="col-md-5">
                                                                    <input id="email" name="email" type="email" placeholder="Email" class="form-control input-md" required="">
                                                                </div>
                                                            </div>
                                                            <!-- Mot de passe -->
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label" for="password">Mot de passe</label>
                                                                <div class="col-md-5">
                                                                    <input id="password" name="password" type="password" placeholder="Mot de passe" class="form-control input-md" required="">
                                                                </div>
                                                            </div>
                                                            <!-- Button -->
                                                            <div class="form-group text-center">
                                                                <div class="col-xs-12 col-sm-12 col-md-offset-4 col-md-5 col-lg-offset-4 col-lg-5">
                                                                    <input type="submit" class="btn btn-success btn-block" value="Connexion" />
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