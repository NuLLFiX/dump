<?php
//filter inputs
$_GET['id'] = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);

// first check what form we need to display!
if(is_string($_GET['id']) && strlen($_GET['id']) === 32) {
    $reset = true;
    // check database if password md5 exists
    $res = mycustomdb()->prepare("SELECT * FROM users WHERE pass = ? LIMIT 1");
    $res->execute(array($_GET['id']));
    $res = $res->fetch();

    if($res === false){
        $sreply = "No user exist with this password id.";
        $reset = false;
    }
    $res = null; // close database connection.
}
//filter inputs
$_POST['new_pwd'] = filter_input(INPUT_POST, 'new_pwd', FILTER_SANITIZE_STRING);
$_POST['new_pwd2'] = filter_input(INPUT_POST, 'new_pwd2', FILTER_SANITIZE_STRING);

// check for empty fields.
if($_POST['new_pwd'] === "" || $_POST['new_pwd2'] === ""){
    $sreply = "Empty Fields are not allowed.";
}

// check the new password form now
if($_POST['new_pwd'] != null || $_POST['new_pwd2'] != null){
    if ($_POST['new_pwd'] != $_POST['new_pwd2']){
        $sreply = "Passwords don't match! Please try again.";
    }
    if ($_POST['new_pwd'] === $_POST['new_pwd2']){
        // pass ok attempt to insert it to the database!
        $newuserdbpass = md5($_POST['new_pwd']);
	$res3 = mycustomdb()->prepare("UPDATE users SET pass=? WHERE pass=?");
        $res3->execute(array($newuserdbpass, $_POST['token']));
        //var_dump($res3);
        if (is_object($res3)){
           $creply = "Password successfully changed";
           $reset = false;
        } else {
           $sreply = "Something is wrong nothing written in the database!";
        }
    }
}

//filter inputs
$_POST['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// check for errors in the email field.
if($_POST['email'] === false){
    $sreply = "Invalid E-Mail provided!";
}
if(is_string($_POST['email'])) {
// check database if mail allready exists
$res2 = mycustomdb()->prepare("SELECT * FROM users WHERE mail = ? LIMIT 1");
$res2->execute(array($_POST['email']));
$res2 = $res2->fetch();
    if($res2 === false){
        $sreply = "No user exist with this email id.";
    } else { // user found! now send the mail!
        // headers for php mails
	$headers = 'From: '. $_CONFIG['admin_mail'] . "\r\n" .
	'Reply-To: '. $_CONFIG['admin_mail'] . "\r\n" .
	'X-Mailer: PHP/' . phpversion();
        
	$message = 'Welcome to '.$_CONFIG['site_name']."\n\n".
	'Hi, If you want to change password of your account, please click on <a href="'.$_CONFIG['site_url'].'/?v=forgotpw&id='.$res2[pass].'">this link</a>. You\'ll be prompted to enter new password.'."\n\n".
	$_CONFIG['site_url'];

	mail($res2['mail'], "Password reset on ".$_CONFIG['site_name'], $message, $headers);
        //var_dump($message);
        $creply = "The mail with the link has been mailed to you.";
    }
$res2 = null; // close database connection.
}
?>
<div itemprop="mainContentOfPage">

<?php if ($sreply != NULL) { ?>
    <div class="alert alert-warning">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$sreply?></p>
    </div>
<?php } ?>
<?php if ($creply != NULL) { ?>
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$creply?></p>
    </div>
<?php } ?>

<h1>Password Reset</h1>
<?php if($reset) { ?>
<form method="post">
<div class="hidden-fields">
    <input type='hidden' name='token' value='<?=$_GET['id']?>' />
</div>
  
<div class="form-group">
    <label for="id_new_pwd">New password</label>
    <input class="form-control" id="id_new_pwd" name="new_pwd" type="password" />
</div>

<div class="form-group"> 
    <label for="id_new_pwd2">New password (confirmation)</label>
    <input class="form-control" id="id_new_pwd2" name="new_pwd2" type="password" />
</div>


<p><input class="btn btn-default" type="submit" value="Submit" /></p>

</form>
<?php } else { ?>
<p>Enter email that your account was registered with. You will receive further instructions on that email.</p>
<form method="post">

<div class="hidden-fields">

</div>

<div class="form-group">   
    <label for="id_email">Email</label>
    <input class="form-control" id="id_email" name="email" type="text" />
</div>


<p><input class="btn btn-default" type="submit" value="Submit" /></p>

</form>
<?php } ?>
</div>
