<?php
// tries redirect
if ($_SESSION['tries'] > 4) {
    echo '<script type="text/javascript">window.location = "./index.php"</script>';
}
// check user when the signin form is pressed!
if (isset($_POST['sign-in-username']) && isset($_POST['sign-in-password']))	{
//filter inputs
$_POST['sign-in-username'] = filter_input(INPUT_POST, 'sign-in-username', FILTER_SANITIZE_STRING);
$_POST['sign-in-password'] = filter_input(INPUT_POST, 'sign-in-password', FILTER_SANITIZE_STRING);

$res = mycustomdb()->prepare("SELECT nick,pass,id FROM users WHERE nick = ? AND pass = ? LIMIT 1");
$res->execute(array($_POST['sign-in-username'], md5($_POST['sign-in-password'])));
$res = $res->fetch();
//var_dump($res);
//$_SESSION['tries'] = 0;

$_SESSION['user_logged'] = (md5($_POST['sign-in-password']) === $res['pass'] && $_POST['sign-in-username'] === $res['nick']) ? 1 : 0;

if($_SESSION['user_logged'] == 1) {
    $_SESSION['username'] = $_POST['sign-in-username'];
    echo '<script type="text/javascript">window.location = "./index.php"</script>';
} else {
    $_SESSION['tries']++;
}

// form checks!
if ($res === FALSE) {
    $sreply = "Your password or username is wrong!";
}

if ($_POST['sign-in-password'] == "" || $_POST['sign-in-username'] == "") {
    $sreply = "Both fields are required.";
}

if ($_SESSION['tries'] > 3) {
    $sreply = "To many LOGIN tries you are not allowed to login for now. Try later...";
}

$res = null;
}
?>
<div itemprop="mainContentOfPage">

<?php if ($sreply != NULL) { ?>
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$sreply?></p>
    </div>
<?php } ?>
         
<h1>Log in to <?=$_CONFIG['site_name']?></h1>
<form method="post">

<div class="hidden-fields">

</div>
    
<div class="form-group">
    
    <label for="id_username">Username</label>
    <input class="form-control" id="id_username" name="sign-in-username" type="text" />
</div>

<div class="form-group">
    
    <label for="id_password">Password</label>
    <input class="form-control" id="id_password" name="sign-in-password" type="password" />
</div>

<p><input class="btn btn-default" type="submit" value="Submit" /></p>

</form>
<p>
    <i class="icon-info-sign"></i> Click <a href="./?v=forgotpw">here</a> if you have lost the password.
</p>

</div>

