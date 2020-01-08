<!doctype html>
<html lang="en-US" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,height=device-height,user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
    <title>Demo 2019 </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keys" content="">

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    
    <link href="https://fonts.googleapis.com/css?family=Lato:400,400i,700&display=swap" rel="stylesheet">

</head>
<body class="home page-template page-template-template-home page-template-template-home-php page page-id-8">

<header>
    <div class="max">
        <div class="social">
        </div>
    </div>
</header>

<main>
    
    <?=$content;?>

</main>

<footer>
    <div class="max">
        <div id="copy">
            &copy; 2019
        </div>
        <div class="links">
            <a href="<?= site_url() ?>privacy">Privacy Policy</a>
        </div>
    </div>
</footer>

<script>
    $(function() {
    });
</script>

<script src="<?=base_url()?>js/cookiechoices.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function(event) {
//    cookieChoices.showCookieConsentBar('<b>About Cookies on This Site</b><br><br>Demo uses cookies which are necessary for the proper functioning of its websites. Subject to your preferences, Demo  may also use cookies to improve your experience, to secure and remember log-in details, for session management, to collect statistics, to optimize site functionality and to deliver content tailored to your interests. We honor the preferences you select, both here and in specific applications where further cookie preferences will specifically be solicited.<br>To provide a smooth navigation, your cookie preferences will be shared across the following  Demo web domains where the purpose and use of the cookies will remain the same: bluemix.net, bluewolf.com, ibm.com, ibmcloud.com, softlayer.com and securityintelligence.com.<br><br>Click “Agree and proceed with  Demo standard Settings” to accept cookies and go directly to the site or click “View cookie settings” for a detailed description of the types of cookies and/or to customize your cookie selection.',
//   'Agree and proceed with  Demo standard Settings', 'Privacy Policy', '<?=site_url("privacy")?>');
  });
</script>

</body>
</html>
