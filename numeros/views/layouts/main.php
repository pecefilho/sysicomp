<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use numeros\assets\AppAsset;
use common\widgets\Alert;

AppAsset::register($this);

$this->registerJsFile('@web/js/freelancer.js');

?>

<?php $this->beginPage() ?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <?= Html::csrfMetaTags() ?>
    <title>IComp Números</title>
    <?php $this->head() ?>

    <!-- Bootstrap Core CSS - Uses Bootswatch Flatly Theme: http://bootswatch.com/flatly/ -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
    <link href="http://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet" type="text/css">
    
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body id="page-top" class="index">

    <!-- Navigation -->
    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header page-scroll">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php?r=site">IComp Números</a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">
                    <li class="hidden">
                        <a href="index.php?r=site#page-top"></a>
                    </li>
                    <li class="page-scroll">
                        <a href="index.php?r=site#alunos">Alunos</a>
                    </li>
                    <li class="page-scroll">
                        <a href="index.php?r=site#docentes">Docentes</a>
                    </li>
                    <li class="page-scroll">
                        <a href="index.php?r=site#publicacoes">Publicações</a>
                    </li>
                    <li class="page-scroll">
                        <a href="index.php?r=site#projetos">Projetos</a>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container-fluid -->
    </nav>

    <!-- Header -->
    <header>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div>
                        <img class="img-responsive inlineImage rightGap" src="img/icomp.png" alt="" width="320">
                        <img class="img-responsive inlineImage" src="img/ufam.png" alt="" width="136">
                    </div>
                    <div class="intro-text">
                        <span class="name">IComp em Números</span><br>
                        <span class="skills">Confira abaixo as estatísticas do Instituto de Computação da Universidade Federal do Amazonas (UFAM)</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- VIEWS AQUI! -->
    <?= $content ?>

    <!-- Footer -->
    <footer class="text-center">

        <div class="footer-below">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        Copyright &copy; IComp <?php echo date("Y"); ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>

<?php $this->endBody() ?>
</body>
<?php $this->endPage() ?>
</html>
