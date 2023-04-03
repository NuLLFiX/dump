<?php

// empty get is recieved
//if (empty($alldump)) {
//    echo '<script type="text/javascript">window.location = "./index.php"</script>';
//}

// Populate the page now!! ;p
if (!empty($alldump)) {
    $title = ($alldump['title'] != "") ? '<h3 style="margin-top: 10px; margin-bottom: 20px;">'.$alldump['title'].'</h3>' : null ;

    $userlink = ($seo_user != 'Anonymous') ? '<a href="'.$_CONFIG['site_url'].'/?v=users&user='.$seo_user.'">'.$seo_user.'</a>' : 'Anonymous';

    // setup optimize customize geshi.
    $path = "./geshi/";
    require $path . 'geshi.php';
    // validate some variables.
    if (!is_readable($path.'geshi/'.$alldump['syntax'].'.php')) {
        $sreply = "I cannot find the language used for this dump! <<".$alldump['syntax'].">>";
        $alldump['syntax'] = null;
    }
    // check if password protected!
    if ($alldump['password'] != '') {
        $reply = '<h1>Password Protected</h1>
                    <form class="big-form" method="post">
                    <div class="hidden-fields">
                    </div>
                    <div class="form-group">
                        <label for="id_password">Password</label>
                        <input class="form-control" id="id_password" maxlength="50" name="password" type="text" />
                    </div>

                    <p><input class="btn btn-default" type="submit" value="Submit" /></p>
                    </form>';
        if (md5($_POST['password']) === $alldump['password']) { $passed = true; $reply = null; }
        if (isset($_POST['password']) && md5($_POST['password']) != $alldump['password']) { $passed = false; $sreply = 'Wrong Password!'; }
    } else {
        $passed = true;
    }
    $geshi = new GeSHi(htmlspecialchars_decode($alldump['code'],ENT_QUOTES), $alldump['syntax']);
    // Use the PRE_VALID header. This means less output source since we don't have to output &nbsp;
    // everywhere. Of course it also means you can't set the tab width.
    // HEADER_PRE_VALID puts the <pre> tag inside the list items (<li>) thus producing valid HTML markup.
    // HEADER_PRE puts the <pre> tag around the list (<ol>) which is invalid in HTML 4 and XHTML 1
    // HEADER_DIV puts a <div> tag arount the list (valid!) but needs to replace whitespaces with &nbsp
    //            thus producing much larger overhead. You can set the tab width though.
    $geshi->set_header_type(HEADER_DIV);

    // Enable CSS classes. You can use get_stylesheet() to output a stylesheet for your code. Using
    // CSS classes results in much less output source.
    //$geshi->enable_classes();
    $geshi->set_link_target('_blank');

    // Enable line numbers. We want fancy line numbers, and we want every 5th line number to be fancy
    $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 5);

    // Set the style for the PRE around the code. The line numbers are contained within this box (not
    // XHTML compliant btw, but if you are liberally minded about these things then you'll appreciate
    // the reduced source output).
    $geshi->set_overall_style('font: normal normal 70% monospace; color: #000066; margin-top: 10px;', false);

    // Set the style for line numbers. In order to get style for line numbers working, the <li> element
    // is being styled. This means that the code on the line will also be styled, and most of the time
    // you don't want this. So the set_code_style reverts styles for the line (by using a <div> on the line).
    // So the source output looks like this:
    //
    // <pre style="[set_overall_style styles]"><ol>
    // <li style="[set_line_style styles]"><div style="[set_code_style styles]>...</div></li>
    // ...
    // </ol></pre>
    $geshi->set_line_style('color: #909090;', 'font-weight: bold; color: #707070;', true);
    $geshi->set_code_style('color: #000020;', true);

    // Styles for hyperlinks in the code. GESHI_LINK for default styles, GESHI_HOVER for hover style etc...
    // note that classes must be enabled for this to work.
    $geshi->set_link_styles(GESHI_LINK, 'color: #000060;');
    $geshi->set_link_styles(GESHI_HOVER, 'background-color: #f0f000;');
    
    // export html for modal
    $export_html = $geshi->parse_code();
    $export_html = str_replace("70% monospace","100% monospace",$export_html);
} else {
    $deleted = true;
}
?>

<div itemprop="mainContentOfPage">

<?php if ($sreply != NULL) { ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$sreply?></p>
    </div>
<?php } ?>

<?=$reply?>

<?php if ($sreply === NULL && $passed === true) { ?>

<?=$title?>

<div class="clearfix">
    <div class="pull-left">
        <span class="glyphicon glyphicon-user"></span>
        <?=$userlink?>
        &nbsp; &nbsp;
        <span class="glyphicon glyphicon-flash"></span>
        <?=ucwords($alldump['syntax'])?>
        &nbsp; &nbsp;
        <span class="glyphicon glyphicon-time"></span>
        <?=date("j F Y",$alldump['date'])?>
        &nbsp; &nbsp;
    </div>

    <ul class="nav nav-pills pull-left" style="position: relative; top: -10px">
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown">
                Export <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" role="menu">
                <li><a href="#" data-toggle="modal" data-target="#myModal2">Text Version</a></li>
                <li><a href="#" data-toggle="modal" data-target="#myModal">HTML Export</a></li>
            </ul>
        </li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown">
                Tools <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li><a href="#" onclick="togglew('<?=$alldump['syntax']?>'); return false">Wrap / Unwrap</a></li>
                <li><a href="#" onclick="togglev(); return false">Line Numbers Toggle</a></li>
            </ul>
        </li>
        
</div>
    <div class="clear"></div>
</div>

<!-- Modals -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">HTML Export</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="comment">Copy Below:</label>
          <textarea class="form-control" rows="10" id="html_export"><?php echo htmlspecialchars($export_html); ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<div id="myModal2" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Text Version</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="comment">Copy Below:</label>
          <textarea class="form-control" rows="10" id="html_export"><?php echo htmlspecialchars_decode($alldump['code']); ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>
<!-- Modals End -->

<div class="dump-code">
<?php
    // The fun part :)
    echo $geshi->parse_code();
	$geshi = null;

    // now add a hit to the dump!
	try {
    $add = mycustomdb()->prepare('UPDATE dump SET hits = hits + 1 WHERE id = :key');
	$add->bindValue(':key', $alldump['id'], PDO::PARAM_INT);
    $add->execute(); // WTF is this BUG!!!???!!!!
    //$add = null; // close database connection.
	} catch (PDOException $e) {
	print "Error!: " . $e->getMessage() . "<br/>";
	die();
	}
?>
</div>
<?php } if ($deleted){ ?>
<div align="center">
    <img src="<?=$_CONFIG['site_url']?>/static/img/404-error.jpg" alt="404 error WTF?!" width="95%" />
    <audio autoplay>
        <source src="<?=$_CONFIG['site_url']?>/static/img/babycry.mp3">
    Your browser does not support the audio element.
    </audio>
</div>
<?php } ?>