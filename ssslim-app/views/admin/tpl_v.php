<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="author" content="the Querlo team">
    <link rel="icon" href="../../favicon.ico">

    <title>DEMO</title>

    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">

    <!-- Bootstrap core CSS -->
    <link href="<?= base_url() ?>css/admin/bootstrap.min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="<?= base_url() ?>css/admin/style.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="<?= base_url() ?>js/admin/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>

    <!-- Bootstrap core JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="../../assets/js/vendor/jquery.min.js"><\/script>')</script>
    <script src="<?= base_url() ?>js/admin/bootstrap.min.js"></script>
    <![endif]-->

</head>

<body>

<div class="logoWrp">
    <div class="container">
        <a href="<?= base_url() ?>"><span style="color: white; font-size: 30px; font-weight: bold">Demo</span><?/*<img src="<?= base_url() ?>images/logo.png">*/?></a>
    </div>
</div>

<div class="container">
    <?if(!isset($popup)|| !$popup):?>
    

    <!-- Static navbar -->
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li <?=$section=='dashboard'?'class="active"':''?>><a href="<?=site_url("dashboard")?>"><i class="glyphicon glyphicon-home"></i> Home</a></li>
                    <li <?=$section=='users'?'class="active"':''?>><a href="<?=site_url("dashboard/users")?>"><i class="glyphicon glyphicon-user"></i> Users</a></li>
                    <li <?=$section=='leads'?'class="active"':''?>><a href="<?=site_url("dashboard/leads")?>"><i class="glyphicon glyphicon-filter"></i> Leads</a></li>
                    <li class="logout"><a href="<?=site_url("dashboard/logout")?>"><i class="glyphicon glyphicon-log-out"></i> Logout</a></li>
                </ul>
            </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
    </nav>
    <?endif?>
    <?=$content?>
</div> <!-- /container -->
</body>
</html>
