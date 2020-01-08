<?php
$proto = (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
$config['base_url'] = $proto . "://localhost/vfs_hr_dev/";
$config['AuthTokenSecretKey'] = "1TFGfruOGtvcasSkqr3WCSp2RmQbi280DlTrI5PpcTyJz8wVq4qCfXf0snLXeO5Iw6xpkYXrBoCIT7TmKvvaApa2rTfQiKT1bKxhd867TqG7rcTiKtckiGSgo1fi1PfdQP4dDdTLlKJm7dLmDDrickHH2WqNGVKRnbm9o85c9vCPh6bJzNCGdEwseb9knSB17oA4cXzLe4nVuiTqkgCT9WxFABx7mZ2TKwkn5ORL2oefYxFIzrRmC2pmC3KZS5ir";

define('ONLINE', false);