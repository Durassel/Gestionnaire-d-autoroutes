<!DOCTYPE html>
<html lang="en">
    <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="description" content="">
      <meta name="author" content="">
      <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
      <title><?php if (isset($title)) echo $title; else echo "Gestion d'autoroutes"; ?></title>
      <!-- Bootstrap Core CSS -->
      <link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
      <!-- Custom CSS -->
      <link href="css/style.css" rel="stylesheet">
      <!-- color CSS -->
      <link href="css/default.css" id="theme" rel="stylesheet">
      <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
      <![endif]-->
    </head>
    <body class="fix-header">
      <div id="wrapper">
        <nav class="navbar navbar-default navbar-static-top m-b-0">
          <div class="navbar-header">
            <div class="top-left-part">
              <!-- Logo -->
              <a class="logo" href="index.php">
                <b><img src="images/admin-logo-dark.png" alt="home" class="light-logo" /></b>
                <span class="hidden-xs">
                  <img src="images/admin-text-dark.png" alt="home" class="light-logo" />
                </span>
              </a>
            </div>
            <!-- User -->
            <?php if (connecte()) : ?>
            <ul class="nav navbar-top-links navbar-right pull-right">
              <li>
                <a class="profile-pic" href="#"><b class="hidden-xs"><?php echo $_SESSION['auth']['Prenom'] . ' ' . $_SESSION['auth']['Nom']; ?></b></a>
              </li>
            </ul>
          <?php endif; ?>
          </div>
        </nav>
        <div class="navbar-default sidebar" role="navigation">
          <div class="sidebar-nav slimscrollsidebar">
            <div class="sidebar-head">
              <h3><span class="fa-fw open-close"><i class="ti-close ti-menu"></i></span> <span class="hide-menu">Navigation</span></h3>
            </div>
            <ul class="nav" id="side-menu">
              <li style="padding: 70px 0 0;">
                <a href="index.php" class="waves-effect">Accueil</a>
              </li>
              <?php if (connecte()) : ?>
                <li>
                  <a href="deconnexion.php" class="waves-effect">Deconnexion</a>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>