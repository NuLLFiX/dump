<?php
if(isset($_GET['user']) && $_GET['user'] != '') {
    // sanitize variable fpr security and do some checks!
    $user = filter_input(INPUT_GET, 'user', FILTER_SANITIZE_STRING);
    if($user != 'anon') {
        $res = mycustomdb()->prepare("SELECT nick,id FROM users WHERE nick = ? LIMIT 1");
        $res->execute(array($user));
        $res = $res->fetch();
        if (empty($res)) { $error404 = true; $sreply = "Unable to find a user called ".$user." in our database."; }
    }
    // check if user is logged in and display manage icons.
    if ($_SESSION['user_logged'] === 1 && $_SESSION['username'] === $res['nick']) {
        $show_usermanageicons = true;
        // post delete function
        if (is_numeric($_GET['delete']) && $_GET['delete'] > 0) {
            $_GET['delete'] = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);
            $delete = mycustomdb()->prepare("DELETE FROM dump WHERE id = ?");
            $delete->execute(array($_GET['delete']));
            if ($delete->rowCount() === 1) {
                $sreply = 'The selected database item was deleted.';
            }
            $delete = NULL; // close connection!
        }
    }
    if($user != 'anon') {
        // past here means we are all ok and need to display the user dump list.
        $dumps = mycustomdb()->prepare("SELECT id, key, title, syntax, date, password FROM dump WHERE user_id = ? ORDER BY date DESC");
        $dumps->execute(array($res['id']));
    } else {
        $dumps = mycustomdb()->prepare("SELECT id, key, title, syntax, date, password FROM dump WHERE user_id = 0 ORDER BY date DESC");
        $dumps->execute();
    }
    while ($result = $dumps->fetch()) {
        $title = ($result['title'] != '') ? $result['title'] : '&mdash;';
        $pass = ($result['password'] != '') ? '<img src="./static/img/padlock.gif" alt="locked with password" title="Item is locked with password" style="border:none;width:16px;height:16px;vertical-align:middle;margin-left:5px" />' : '';
        $manage = ($show_usermanageicons) ? '<a href="?v=users&user='.$user.'&delete='.$result['id'].'" title="Delete Selected Item!"><img src="./static/img/delete.png" alt="delete icon" style="border:none;width:16px;height:16px;vertical-align:middle;margin-left:5px" /></a>' : '';
        $table_data .= '<tr class="dump">
                        <td class="edit">'.$manage.'</td>
                        <td class="link"><a style="text-decoration: none" href="'.$_CONFIG['site_url'].'/'.$result['key'].'/">'.$result['key'].'</a></td>
                        <td class="lock">'.$pass.'</td>
                        <td class="comment">'.$title.'</td>
                        <td class="lexer">'.ucfirst($result['syntax']).'</td>
                        <td class="date col-md-1">'.date("j/n/Y",$result['date']).'</td>
                        <td class="delete">
                        </td>'."\n\n";
    }
    //$res = NULL; // close connection!
} else {
    // get the user list from the database!
    $users = mycustomdb()->query("SELECT id, nick FROM users ORDER BY nick ASC");
    $i = 0;
    foreach($users as $row) {
    $user[$i]['nick'] = $row['nick'];
    $user[$i]['id'] = $row['id'];
    $post_count = mycustomdb()->prepare('SELECT COUNT(*) FROM dump WHERE user_id = ?');
    $post_count->execute(array($row['id']));
    $post_count = $post_count->fetch(PDO::FETCH_NUM);
    $user[$i]['dumps'] = $post_count[0];
    $i++;
    }
    $post_count = null; // close database
    // count anonymous dumps
    $post_count = mycustomdb()->prepare('SELECT COUNT(*) FROM dump WHERE user_id = 0');
    $post_count->execute();
    $post_count = $post_count->fetch(PDO::FETCH_NUM);
    $anoncount = $post_count[0];
    $post_count = null; // close database
    
    $row = NULL; // clear the array data...
    $i = NULL; // clear number ;p
    $post_count = NULL; // close database
    $users = NULL; // close database
    // now we have thwe $user multi array populated lets generate the html table data!
    foreach($user as $row) {
        $table_data .= '<tr><td><a href="./?v=users&user='.$row['nick'].'">'.$row['nick'].'</a></td><td>'.$row['dumps'].'</td></tr>'. "\n";
    }
    // add the anonymous user line here ;p ....
    $table_data .= '<tr><td><a href="./?v=users&user=anon">Anonymous</a></td><td>'.$anoncount.'</td></tr>'. "\n";
    $user = NULL; // close connection!
    $row = NULL;

    // now get some stats from the database!
    $count_users = mycustomdb()->query('SELECT COUNT(*) FROM users');
    $count_users = $count_users->fetch(PDO::FETCH_NUM);
    $count_users = $count_users[0];

    $count_dumps = mycustomdb()->query('SELECT COUNT(*) FROM dump');
    $count_dumps = $count_dumps->fetch(PDO::FETCH_NUM);
    $count_dumps = $count_dumps[0];
}
?>

<?php if ($sreply != NULL) { ?>
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$sreply?></p>
    </div>
<?php } ?>

<?php if(isset($_GET['user']) && $_GET['user'] != '' && !$error404) { ?>

<h1>Information about <?php echo (isset($res['nick'])) ? $res['nick'] : 'Anonymous'; ?></h1>

<table class="table table-condensed">
    <thead>
        <tr>
            <th></th>
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

<?php } elseif (!$error404) { ?>
<h1>User list</h1>

<div class="row">
    <div class="col-md-4">
        <table class="table">
            <thead>
                <tr>
                    <th>User name</th>
                    <th>No. of snipt uploaded</th>
                </tr>
            </thead>
            
            <?=$table_data?>

        </table>
    </div>
    <div class="col-md-offset-1 col-md-7">
        <dl>
            <dt>The most popular format</dt>
            <dd>Text</dd>
            <dt>Number of users</dt>
            <dd><?=$count_users?></dd>
            <dt>Number of snippets</dd>
            <dd><?=$count_dumps?></dd>
        </dl>
    </div>
</div>
<?php } else { ?>
<div align="center">
    <img src="./static/img/404-error.jpg" alt="404 error WTF?!" width="95%" />
    <audio autoplay>
        <source src="./static/img/babycry.mp3">
    Your browser does not support the audio element.
    </audio>
</div>
<?php } ?>