<?php
// check if registration passwords do not match!
if ($_POST['sign-up-password'] != $_POST['sign-up-reenter-password']) { $sreply = "Registration passwords do not match, please try again!"; }
// captcha check
if (!empty($_POST) && $_POST['captcha'] != $_SESSION['captcha']) { $sreply = "Captcha is invalid. Please try again!"; $caperror = true; }
// registration action managment
if ($_POST['sign-up-password'] == $_POST['sign-up-reenter-password'] && $_SESSION['user_logged'] != 1 && $caperror != true) {

// sanitize input!
$_POST['sign-up-username'] = filter_input(INPUT_POST, 'sign-up-username', FILTER_SANITIZE_STRING);
$_POST['sign-up-email'] = filter_input(INPUT_POST, 'sign-up-email', FILTER_SANITIZE_EMAIL);

	if (isset($_POST['sign-up-username']) && isset($_POST['sign-up-email']))	{

	// valid mailcheck function
	function validate_email($email)
	{
	if(!preg_match ("/^[\w\.-]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]+$/", $email))
	return false;

	list($prefix, $domain) = explode("@",$email);

			if(function_exists("getmxrr") && getmxrr($domain, $mxhosts))
			{
			return true;
			}
			elseif (@fsockopen($domain, 25, $errno, $errstr, 5))
			{
			return true;
			}
			else
			{
			return false;
			}

	}

	// check database if mail allready exists
	$res2 = mycustomdb()->prepare("SELECT * FROM users WHERE mail = ? OR nick = ? LIMIT 1");
        $res2->execute(array($_POST['sign-up-email'], $_POST['sign-up-username']));
        $res2 = $res2->fetch();

			if ($res2 === false && $_POST['sign-up-username'] != '' && $_POST['sign-up-email'] != '' && validate_email($_POST['sign-up-email']) != false) {

			$newuserdbpass = md5($_POST['sign-up-password']);
			$res3 = mycustomdb()->prepare("INSERT INTO users (nick, pass, mail, joined) VALUES (?,?,?,?)");
                        $res3->execute(array($_POST['sign-up-username'], $newuserdbpass, $_POST['sign-up-email'], $_SERVER['REQUEST_TIME']));
			// close database and send mail with login & password.
			// headers for php mails
			$headers = 'From: '. $_CONFIG['admin_mail'] . "\r\n" .
			'Reply-To: '. $_CONFIG['admin_mail'] . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
			
			$message = 'Welcome to '.$_CONFIG['site_name']."\n\n".
			'Login:'.$_POST['sign-up-username']."\n".
			'Password: '.$_POST['sign-up-password']."\n\n".
			$_CONFIG['site_url'];

			//mail($_POST['sign-up-email'], $_CONFIG['site_name']." Registration Info!", $message, $headers);
                        // user registered so we mark him login for the php redirect.
			$_SESSION['user_logged'] = 1;
                        $_SESSION['username'] = $_POST['sign-up-username'];
                        
                        if($_SESSION['user_logged'] == 1) {
                            echo '<script type="text/javascript">window.location = "./index.php"</script>';
                        }
                        
			} else { $sreply = "Registration username or mail is invalid or already in use!"; }

	// close db
	}

}
?>
<style>
#CAPTCHA{
    float: right;
    font-size:1px;
    line-height:1px;
}
</style>
<div itemprop="mainContentOfPage">

<?php if ($sreply != NULL) { ?>
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$sreply?></p>
    </div>
<?php } ?>

<h1>Sign up on <?=$_CONFIG['site_name']?></h1>
<form method="post">

<div class="hidden-fields">

</div>
    
<div class="form-group">
    
    <label for="id_username">Username</label>
    <input class="form-control" id="id_username" name="sign-up-username" type="text" value="<?=$_POST['sign-up-username']?>" />
</div>


    
<div class="form-group">
    
    <label for="id_email">Email</label>
    <input class="form-control" id="id_email" name="sign-up-email" type="text" value="<?=$_POST['sign-up-email']?>" />
</div>

<div class="form-group">
    
    <label for="id_password">Password</label>
    <input class="form-control" id="id_password" name="sign-up-password" type="password" />
</div>
    
<div class="form-group">
    
    <label for="id_password2">Password (confirmation)</label>
    <input class="form-control" id="id_password2" name="sign-up-reenter-password" type="password" />
</div>
    
<div class="form-group">
    
    <label for="captcha" id="spambot">Are you human, or spambot?</label>
    <br /><img src="captcha.php">
    <input class="form-control" id="captcha" name="captcha" type="text" maxlength="4" />

</div>

<p><input class="btn btn-default" type="submit" value="Submit" /></p>

</form>

</div>