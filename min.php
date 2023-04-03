<?php
/********************************************************
	Dumpz v1.5 - Admin Page
	Release date: Aug 2015
	Copyright (C) nullfix.com
*********************************************************/
// basic config start
include('config.php');

// Default meta Tags for any other page exept dump page
$meta_desc = "Admin Secure Page";
$meta_title = $_CONFIG['site_name']." | Admin!";
$meta_keywords = "";

// authentication code!
$valid_passwords = array ("admin" => $_CONFIG['admin_pw']);
$valid_users = array_keys($valid_passwords);

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

//var_dump($_SERVER);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="Dump XXL Script"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized!");
}
// If arrives here, is a valid user. Script starts from here on!

// post delete function
if (is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
    $_GET['delete'] = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);
    $delete = mycustomdb()->prepare("DELETE FROM dump WHERE id='".$_GET['delete']."'");
    $delete->execute();
    if ($delete->rowCount() === 1) {
        $sreply = 'The selected database item was deleted.';
    } else {
        $sreply = 'Nothing was deleted or that item was not found in database!';
    }
    $delete = NULL; // close connection!
}

// Import Function
if($_GET['action'] != '' && $_GET['action'] === 'scrape') {
    $_GET['action'] = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    $main_title = 'Ready to scrape!';
    // generate config form
    $import = '<br /><form class="form-inline" role="form" action="?" method="POST">
                <div class="form-group">
                <label for="sel1">Select Website:</label>
                <select class="form-control" id="sel1" name="site">
                  <option>snipt.net</option>
                  <option>pastebin.com</option>
                </select>
                </div>
                <div class="form-group">
                  <label for="txt">Page Attributes:</label>
                  <input type="text" name="attr" class="form-control" id="txt">
                </div>
                <button type="submit" class="btn btn-default">Scrape It!</button>
              </form>';
} elseif($_POST['attr'] != '' && isset($_POST['site'])) {
    $main_title = 'Scraped results from '.$_POST['site'].'+'.$_POST['attr'];
    $_POST['attr'] = filter_input(INPUT_POST, 'attr', FILTER_SANITIZE_STRING);
    
    switch ($_POST['site']) {
    case 'snipt.net':
        $url = 'http://snipt.net/public/';
        break;
    case 'http://pastebin.com/':
        $url = '';
        break;
    }
    
    include("Snoopy.class.php");
 
    $snoopy = new Snoopy;

    // need an proxy?:
    //$snoopy->proxy_host = "my.proxy.host";
    //$snoopy->proxy_port = "8080";

    // set browser and referer:
    $snoopy->agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
    $snoopy->referer = "http://www.jonasjohn.de/";

    // set some cookies:
    //$snoopy->cookies["SessionID"] = '238472834723489';
    $snoopy->cookies["favoriteColor"] = "blue";

    // set an raw-header:
    $snoopy->rawheaders["Pragma"] = "no-cache";

    // set some internal variables:
    $snoopy->maxredirs = 2;
    $snoopy->offsiteok = false;
    $snoopy->expandlinks = false;

    // set username and password (optional)
    //$snoopy->user = "joe";
    //$snoopy->pass = "bloe";

    // fetch the text of the website www.google.com:
    if($snoopy->fetch($url.$_POST['attr'])){ 
        // other methods: fetch, fetchform, fetchlinks, submittext and submitlinks

        // print the texts of the website:
        //var_dump($snoopy->results);
        preg_match_all(
        // ORIGINAL '/<li>.*?<h1><a href="(.*?)">(.*?)<\/a><\/h1>.*?<span class="date">(.*?)<\/span>.*?<div class="section">(.*?)<\/div>.*?<\/li>/s',
        '/<h3><span>Embed &ldquo;(.*?)&rdquo;<\/span><\/h3>.*?<div class="hide raw-url">(.*?)<\/div>.*?<div class="hide lexer">(.*?)<\/div>.*?<div class="hide modified">(.*?)<\/div>/s',
        $snoopy->results,
        $founddata, // will contain the detected hmtl
        PREG_SET_ORDER // formats data into an array
        );
        $table_data = '<form class="form" role="form" action="?" method="POST">';$i=0;
        foreach ($founddata as $data) {
            // This will print the table!
            //var_dump($data);
            $timestamp = strtotime($data[4]);
            $table_data .= '<tr class="dump">
                            <td class="edit"><input type="checkbox" class="row-select" name="selected['.$i.']"></td>
                            <td class="link"><a style="text-decoration: none" href="'.$data[2].'" target="_blank" title="click to open it in new window...">Code</a><input type="hidden" name="code['.$i.']" value="'.$data[2].'"></td>
                            <td class="lock"></td>
                            <td class="comment">'.$data[1].'<input type="hidden" name="title['.$i.']" value="'.$data[1].'"></td>
                            <td class="lexer">'.ucfirst($data[3]).'<input type="hidden" name="syntax['.$i.']" value="'.$data[3].'"></td>
                            <td class="date col-md-1">'.date("Y-m-d", $timestamp).'<input type="hidden" name="time['.$i.']" value="'.$timestamp.'"></td>
                            <td class="delete"></td></tr>'."\n\n";
        $i++;}
        $table_data .= '<button type="submit" class="btn btn-default" onClick="return; $(this).prop(\'disabled\', true);">Import Selected</button></form>';

    }
    else {
        $sreply = 'Snoopy: error while fetching document - '.$snoopy->error;
    }
    
} elseif(is_array($_POST['selected']) && is_array($_POST['code'])) {
    // now its time to save data if selected is array! ;p
        foreach ($_POST['selected'] as $key => $value) {
            //echo "Key: $key; Value: $value<br />\n";
            $genkey = substr(uniqid(),-8);
            $code = file_get_contents($_POST['code'][$key]);
            $user = '1'; // or we use 0 if we want it to be anonymous snipt ;p
            switch (strtolower($_POST['syntax'][$key])) {
            case 'js':
                $_POST['syntax'][$key] = 'javascript';
                break;
            case '':
                $_POST['syntax'][$key] = 'text';
                break;
            }
            // now save to the database each selected scrape!
            $res = mycustomdb()->prepare("INSERT INTO dump (key, title, code, syntax, date, hits, user_id, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $res->execute(array($genkey, $_POST['title'][$key], htmlspecialchars($code, ENT_QUOTES), strtolower($_POST['syntax'][$key]), $_POST['time'][$key], '0', $user, ''));
        }
        $res = null;
        $sreply = count($_POST['selected']).' scrapes imported!';
        echo '<script type="text/javascript">window.location = "admin.php"</script>'; // redirect to the admin page!
} else {}
//var_dump($_POST);

//echo $import;

// main script function search or display default latest results....
if($_GET['srch-term'] != '' && is_string($_GET['srch-term'])) {
    $_GET['srch-term'] = filter_input(INPUT_GET, 'srch-term', FILTER_SANITIZE_SPECIAL_CHARS);
    $table_data = null; // delete previewsly created data...
    // past here means we are all ok and need to display all users latest dump list.
    $searchterm = "%".strtolower($_GET['srch-term'])."%";
    $dumps = mycustomdb()->prepare("SELECT * FROM dump WHERE key LIKE ? OR title LIKE ? ORDER BY date DESC LIMIT 30");
    $dumps->execute(array($searchterm, $searchterm));
    while ($result = $dumps->fetch()) {
    $title = ($result['title'] != '') ? $result['title'] : '&mdash;';
    $pass = ($result['password'] != '') ? '<img src="./static/img/padlock.gif" alt="locked with password" title="Item is locked with password" style="border:none;width:16px;height:16px;vertical-align:middle;margin-left:5px" />' : '';
    $manage = '<a href="?delete='.$result['id'].'" title="Delete Selected Item!"><img src="./static/img/delete.png" alt="delete icon" style="border:none;width:16px;height:16px;vertical-align:middle;margin-left:5px" /></a>';
    $user_name = mycustomdb()->query("SELECT id, nick FROM users WHERE id='".$result['user_id']."'");
    $user_name = $user_name->fetch(PDO::FETCH_ASSOC);
    $user_name = $user_name['nick'];
if ($result['user_id'] == '') { $user_name = "Anonymous";}
    $table_data .= '<tr class="dump">
                    <td class="edit">'.$manage.'</td>
                    <td class="link"><a style="text-decoration: none" href="'.$_CONFIG['site_url'].'/'.$result['key'].'/" target="_blank" title="Uploaded by '.$user_name.'">'.$result['key'].'</a></td>
                    <td class="lock">'.$pass.'</td>
                    <td class="comment">'.$title.'</td>
                    <td class="lexer">'.ucfirst($result['syntax']).'</td>
                    <td class="date col-md-1">'.date("j/n/Y",$result['date']).'</td>
                    <td class="delete">
                    </td></tr>'."\n\n";
    }
    $main_title = 'Search results for term: '.$_GET['srch-term'];
    $dumps = NULL; // close connection!
} elseif (empty($_POST) && empty($_GET)) {
    // past here means we are all ok and need to display all users latest dump list.
    $table_data = null; // delete previewsly created data...
    $dumps = mycustomdb()->query("SELECT * FROM dump ORDER BY date DESC LIMIT 30");
    while ($result = $dumps->fetch()) {
    $title = ($result['title'] != '') ? $result['title'] : '&mdash;';
    $pass = ($result['password'] != '') ? '<img src="./static/img/padlock.gif" alt="locked with password" title="Item is locked with password" style="border:none;width:16px;height:16px;vertical-align:middle;margin-left:5px" />' : '';
    $manage = '<a href="?delete='.$result['id'].'" title="Delete Selected Item!"><img src="./static/img/delete.png" alt="delete icon" style="border:none;width:16px;height:16px;vertical-align:middle;margin-left:5px" /></a>';
    $user_name = mycustomdb()->prepare("SELECT id, nick FROM users WHERE id=?");
    $user_name->execute(array($result['user_id']));
    $user_name = $user_name->fetch();
    $user_name = $user_name['nick'];
	if ($result['user_id'] == '') { $user_name = "Anonymous";}
    $table_data .= '<tr class="dump">
                    <td class="edit">'.$manage.'</td>
                    <td class="link"><a style="text-decoration: none" href="'.$_CONFIG['site_url'].'/'.$result['key'].'/" target="_blank" title="Uploaded by '.$user_name.'">'.$result['key'].'</a></td>
                    <td class="lock">'.$pass.'</td>
                    <td class="comment">'.$title.'</td>
                    <td class="lexer">'.ucfirst($result['syntax']).'</td>
                    <td class="date col-md-1">'.date("j/n/Y",$result['date']).'</td>
                    <td class="delete">
                    </td></tr>'."\n\n";
    }
    $main_title = 'Most recent snippets added to database';
    $dumps = NULL; // close connection!
} else {}
?>
<!DOCTYPE html>
<html>
    <head>
        <!-- Meta tags -->
        <title><?=$meta_title?></title>
        <meta name="description" content="<?=$meta_desc?>" />
        <meta name="keywords" content="<?=$meta_keywords?>" />
        <meta charset="utf-8" />
        <link rel="shortcut icon" href="favicon.ico" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="index,follow,archive">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <!-- SEO -->

        <!-- CSS -->
        <!-- DO NOT LOAD bootstrap via CDN - it breaks IE respond.js library -->
        <!--<link href="static/bootstrap3/css/bootstrap.min.css?1" rel="stylesheet">-->
        <link href="<?=$_CONFIG['site_url']?>/static/css/cosmo/bootstrap.min.css" rel="stylesheet">
        
        <link rel="stylesheet" type="text/css" href="<?=$_CONFIG['site_url']?>/static/css/custom.css?1">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
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
                <a class="navbar-brand" href="<?=$_SERVER['PHP_SELF']?>"><?=$_CONFIG['site_name']?></a>
                <ul class="nav navbar-nav pull-left">
                    <li><a class="arrow" href="?action=scrape">Import (scrape)</a></li>
                    <li><a class="arrow" href="#" style="color: #c66; text-decoration: none;">Administration</a></li>
                </ul>

                <div class="col-sm-3 col-md-3 pull-right">
                <form class="navbar-form" role="search">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search" name="srch-term" id="srch-term">
                    <div class="input-group-btn">
                        <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
                    </div>
                </div>
                </form>
                </div>
           </div>
        </div>
    </div><!-- /.container-fluid -->
</nav>


<div class="container">

<?php if ($sreply != NULL) { ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$sreply?></p>
    </div>
<?php } ?>

<h1><?=$main_title?></h1>

<?php if ($table_data != NULL) { ?>
<table class="table table-condensed">
    <thead>
        <tr>
            <th><input type="checkbox" id="toggle-colors"></th>
            <th>Link</th>
            <th></th>
            <th>Comment</th>
            <th>Language</th>
            <th>Date</th>
            <th></th>
        </tr>
    </thead>
    
<?=$table_data?>
    
</table>
<?php } else { 
    echo $import;
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
                <?=$_CONFIG['script_copyrights']?> - v<?=$_CONFIG['script_version']?>
                <br/>
            </div>
            <div style="height: 1px; widht: 1px; overflow: hidden">
</div>

        </div>
    </div>
</div>

    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="<?=$_CONFIG['site_url']?>/static/js/util.js?c=1"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/1.8/js/bootstrap-switch.min.js"></script>
    <script type="text/javascript">
    $('#toggle-colors').click(function() {
        $('.row-select').each(function(){
            // toggle checkbox
            $(this).prop('checked',!$(this).prop('checked'));
        });
    });
    </script>
<?php // debug line.
//var_dump($alldump);
//var_dump($_GET);
?>
</body>
</html>