<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">

    <title>DEMO Dashboard</title>

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

<div class="container">
        <div class="row logoWrp">
            <a href="<?= base_url() ?>"><span style="color: white; font-size: 30px; font-weight: bold">DEMO</span><?/*<img src="<?= base_url() ?>images/logo.png">*/?></a>
        </div>
    <div class="jumbotron">
        <h1>Welcome!</h1>
        <p>Please login with you credentials below:</p>
        <?/*<p>
            <a class="btn btn-lg btn-primary" href="#" role="button">A button</a>
        </p>*/?>
    </div>

    <form action="<?=site_url("dashboard/login/")?>" method="post">
        <div class="row">
            <div class="form-group col-md-4 col-lg-offset-4  <?=isset($errors['email'])?'has-error':''?> ">
                <label for="email">E-mail</label>
                <input type="text" class="form-control" id="email" name="email" placeholder="your@e-mail.com" value="">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-4 col-lg-offset-4 <?=isset($errors['password'])?'has-error':''?> ">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="" value="">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-4 col-lg-offset-4 text-center">
                <button type="submit" value="submit" name="submit"  class="btn btn-default  btn-primary">Submit</button>
            </div>
        </div>
</div> <!-- /container -->
</body>
</html>
