<?php
/********************************************************
	Dumpz v1.5
	Release date: Aug 2014
	Copyright (C) nullfix.com
*********************************************************/
session_start();
// basic config start
include('config.php');

// Default meta Tags for any other page exept dump page
$meta_desc = "Post Snippets and code and help others complete projects faster. Long-term memory for coders. Share and store code snippets.";
$meta_title = $_CONFIG['site_name']." | Post your code bits and snippets and get infamous!";
$meta_keywords = "snipets, snippets, snipt, code, highlight, text, functions, class, help, developers, coders";

//var_dump(getenv('HTTP_MOD_REWRITE')); //we can check if apatche mod_rewrite is on!

// php main code in head.
$page_to_load = filter_input(INPUT_GET, 'v', FILTER_SANITIZE_STRING);

switch($page_to_load) {
case 'about':
//Logic to view or HTML for view
$page_final = "views/about.php";
break;	

case 'account':
//Logic to add or HTML for add
$page_final = ($_SESSION['user_logged'] === 1) ? "views/account.php" : "views/login.php";
break;

case 'users':
//Logic to add or HTML for add
$page_final = "views/users.php";
break;

case 'login':
//Logic to add or HTML for add
$page_final = "views/login.php";
break;

case 'signup':
//Logic to add or HTML for add
$page_final = "views/register.php";
break;

case 'forgotpw':
//Logic to add or HTML for add
$page_final = "views/lostpw.php";
break;

case 'logout':
//Logic to add or HTML for add
unset($_SESSION['username']);
unset($_SESSION['user_logged']);
header('Location: '.$_CONFIG['site_url'].'/index.php');
break;

default:
$page_final = "views/home.php";
}

// if user is logged in, we dont want to show login page again so we redirect them to index page.!
if ($_SESSION['user_logged'] === 1 && ($page_final == "views/login.php" || $page_final == "views/register.php")) {
header('Location: '.$_CONFIG['site_url'].'/index.php');
}

// check if we need to open the view page instead!
if(!empty($_GET['i']) && isset($_GET['i'])) {
    $_GET['i'] = filter_input(INPUT_GET, 'i', FILTER_SANITIZE_STRING);
    $alldump = mycustomdb()->prepare("SELECT * FROM dump WHERE key = ? LIMIT 1");
    $alldump->execute(array($_GET['i']));
    $alldump = $alldump->fetch();
    $page_final = "views/view.php";
    // find out user who's dump is this! for meta tags and seo.
    if ($alldump['user_id'] == 0) {
        $seo_user = 'Anonymous';
    } else {
        $seo_user = mycustomdb()->prepare("SELECT nick FROM users WHERE id = ? LIMIT 1");
        $seo_user->execute(array($alldump['user_id']));
        $seo_user = $seo_user->fetch();
        $seo_user = $seo_user['nick'];
    }
    // seo meta tag optimizations
    $meta_desc = "Source Code in ".ucfirst($alldump['syntax'])." syntax from ".$seo_user.". ".$alldump['title'];
    $meta_title = ($alldump['title'] != '') ? $alldump['title']." | ".$_CONFIG['site_name'] : $_CONFIG['site_name'];
    $meta_keywords = extract_keywords($alldump['code']); // todo extract keywords from code function.
    if($alldump === false){
        $sreply = "This Dump is not here anymore or deleted!";
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <!-- Meta tags -->
        <title><?=$meta_title?></title>
        <meta name="description" content="<?=$meta_desc?>" />
        <meta name="keywords" content="<?=$meta_keywords?>" />
        <meta charset="utf-8" />
        <link rel="shortcut icon" href="<?=$_CONFIG['site_url']?>/favicon.ico" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="index,follow,archive">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        
        <!-- SEO -->
		<!-- Global site tag (gtag.js) - Google Analytics -->
		<script async src="//www.googletagmanager.com/gtag/js?id=UA-4771730-16"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());

		  gtag('config', 'UA-4771730-16');
		</script>
        <!-- CSS -->
        <!-- DO NOT LOAD bootstrap via CDN - it breaks IE respond.js library -->
        <!--<link href="static/bootstrap3/css/bootstrap.min.css?1" rel="stylesheet">-->
        <link href="<?=$_CONFIG['site_url']?>/static/css/cosmo/bootstrap.min.css" rel="stylesheet">
        
        <link rel="stylesheet" type="text/css" href="<?=$_CONFIG['site_url']?>/static/css/custom.css?1">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

        <!-- Extra head stuff -->
    </head>
<body>
    
<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <div class="text-center">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#foo">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?=$_CONFIG['site_url']?>/index.php"><?=$_CONFIG['site_name']?></a>
                <ul class="nav navbar-nav pull-left">
                    <li><a class="arrow" href="<?=$_CONFIG['site_url']?>/index.php" style="color: #3fb618;">Upload</a></li>
                </ul>
            </div>
        </div>

        <div class="collapse navbar-collapse" id="foo">
            <ul class="nav navbar-nav pull-left">
                <li><a class="arrow" href="<?=$_CONFIG['site_url']?>/?v=users">Users</a></li>
                <li><a class="arrow" href="<?=$_CONFIG['site_url']?>/?v=about">About</a></li>
            </ul>

            <ul class="nav navbar-nav pull-right">
<?php if($_SESSION[user_logged] === 1) { ?>                
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <?=$_SESSION['username']?>
                        <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?=$_CONFIG['site_url']?>/?v=logout">Log Out</a></li>
                        <li><a class="mydumps arrow" href="<?=$_CONFIG['site_url']?>/?v=users&user=<?=$_SESSION['username']?>">My Snippets</a></li>
                    </ul>
                </li>
<?php } else { ?>
                <li><a href="<?=$_CONFIG['site_url']?>/?v=login">Log in</a></li>
                <li><a href="<?=$_CONFIG['site_url']?>/?v=signup">Sign Up</a></li>
<?php } ?>
            </ul>
        </div>
    </div><!-- /.container-fluid -->
</nav>


    <div class="container">

    <?php
    if (file_exists($page_final)) {
    // Pages and Views Setup
    require($page_final);
    } else {
    require('views/home.php');
    }
    ?>

    </div> <!-- /container -->

<hr style="margin-top: 40px;"/>

<div class="container" style="margin-bottom: 20px;">
    <div class="row">
        <div class="col-md-3">
            <div style="font-size: 12px" class="text-muted">
                Contact: <a class="text-muted" style="text-decoration: underline" href="mailto:<?=$_CONFIG['admin_mail']?>"><?=$_CONFIG['admin_mail']?></a>
            </div>
        </div>
        <div class="col-md-6">
        </div>
        <div class="col-md-3">
            <div style="font-size: 12px" class="pull-right text-muted">
                <?=$_CONFIG['script_copyrights']?> ¤ V <?=$_CONFIG['script_version']?>
                <br/>
            </div>
            <div style="height: 1px; widht: 1px; overflow: hidden">
</div>

        </div>
    </div>
</div>

    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="<?=$_CONFIG['site_url']?>/static/js/util.js?c=1"></script>

<?php // debug line.
//var_dump($alldump);
//var_dump($_GET);
?>
</body>
</html>
