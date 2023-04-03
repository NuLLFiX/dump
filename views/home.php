<?php
if($_POST['code'] === "") {
    $sreply = "Empty snippets are not allowed.";
}
// deal with magic quotes!!!
if (get_magic_quotes_gpc()) {
    $process = array(&$_POST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}
// end dealing ;p

// filter imputs
$_POST['comment'] = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
$_POST['password'] = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
// now save the dump if its not empty!
if ($_POST['code'] != "" && !empty($_POST)) {
    // if the user is loged in assign this dump to that user!
    if(isset($_POST['user'])) {
        $user = mycustomdb()->prepare("SELECT id FROM users WHERE nick = ? LIMIT 1");
        $user->execute(array($_POST['user']));
        $user = $user->fetch();
        $user = (is_numeric($user['id'])) ? $user['id'] : 0; // try to validate it.
    } else {
        $user = 0;
    }
    // now we can write it to the database!
    $genkey = substr(uniqid(),-8);
    $title = ($_POST['comment'] != "") ? $_POST['comment'] : null;
    $pass = ($_POST['password'] != "") ? md5($_POST['password']) : null;
    $res = mycustomdb()->prepare("INSERT INTO dump (key, title, code, syntax, date, hits, user_id, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $res->execute(array($genkey, $title, htmlspecialchars($_POST['code'], ENT_QUOTES), $_POST['lexer'], $_SERVER['REQUEST_TIME'], '0', $user, $pass));
    $user = null; // close database connection if its not closed anyways...
    if(is_object($res)){ // saved completed! without errors
        echo '<script type="text/javascript">window.location = "'.$_CONFIG['site_url'].'/'.$genkey.'/"</script>'; // redirect to the dump url
        exit();
    } else {
        $sreply = "Something went wrong this dump its not saved!";
    }
}
//generate available languages js box
if (!($dir = @opendir(dirname(__FILE__) . '/../geshi/geshi'))) {
    $sreply = 'No languages available!';
}
$languages = array();
while ($file = readdir($dir)) {
    if ( $file[0] == '.' || strpos($file, '.', 1) === false) {
        continue;
    }
    $lang = substr($file, 0,  strpos($file, '.'));
    $languages[] = $lang;
}
closedir($dir);
sort($languages);
foreach ($languages as $lang) {
    //if ($lang) { break; }
    $llist .= '<li><a href="#" class="lexer-'. $lang .'" onclick="chooseLexer(this); return false">'. ucfirst($lang) .'</a></li>'."\n\n";
}
?>
<script type="text/javascript" src="<?=$_CONFIG['site_url']?>/static/js/lexer_control.js?c=1"></script>
<script src="<?=$_CONFIG['site_url']?>/static/codemirror/codemirror.js"></script>
<link rel="stylesheet" href="<?=$_CONFIG['site_url']?>/static/codemirror/codemirror.css">
<script type="text/javascript">
    $(function() {
        var codeMirrorConfig = {
            indentUnit: 4,
            tabMode: "shift",
            lineNumbers: true,
            matchBrackets: true
        }
        var codemirror = CodeMirror.fromTextArea($('#id_code')[0], codeMirrorConfig);
        
        var quotes = $(".quotes");
        var quoteIndex = -1;

        function showNextQuote() {
            ++quoteIndex;
            quotes.eq(quoteIndex % quotes.length)
                .fadeIn(2000)
                .delay(2000)
                .fadeOut(2000, showNextQuote);
        }
        showNextQuote();
    });
</script>

<style>
.large-header {
	position: relative;
	width: 100%;
	background: #333;
	overflow: hidden;
	background-size: cover;
	background-position: center center;
	z-index: 1;
        margin: 10px 0 30px 0;
}

#large-header {
	background-image: url('static/img/code2.jpg');
}

.main-title {
        font-family: impact;
	position: absolute;
	margin: 0;
        font-size: 45px;
	padding: 0;
	color: #f9f1e9;
	text-align: center;
	top: 50%;
	left: 50%;
	-webkit-transform: translate3d(-50%,-50%,0);
	transform: translate3d(-50%,-50%,0);
        text-shadow: 3px 0 0 #000, 0 -3px 0 #000, 0 3px 0 #000, -3px 0 0 #000;
        display: none;
}

.thin {
    font-weight: lighter;
}

@media only screen and (max-width : 768px) {
	.main-title {
	font-size: 30px;
	}
}
</style>
<script src="//cdnjs.cloudflare.com/ajax/libs/gsap/1.18.0/TweenMax.min.js"></script>

<div id="large-header" class="large-header">
  <canvas id="demo-canvas"></canvas>
    <p class="main-title quotes">SHARE YOUR CODE. ITS GOOD!</p>
    <p class="main-title quotes">ALL YOUR CODE ARE BELONG TO US!</p>
    <p class="main-title quotes">IT'S NOT A BUG - IT'S AN UNDOCUMENTED FEATURE</p>
</div>

<script>

(function() {

    var width, height, largeHeader, canvas, ctx, points, target, animateHeader = true;

    // Main
    initHeader();
    initAnimation();
    addListeners();

    function initHeader() {
        width = window.innerWidth;
        height = window.innerHeight/5;
        
        target = {x: width/2, y: height/2};

        largeHeader = document.getElementById('large-header');
        largeHeader.style.height = height+'px';

        canvas = document.getElementById('demo-canvas');
        canvas.width = width;
        canvas.height = height;
        ctx = canvas.getContext('2d');

        // create points
        points = [];
        for(var x = 0; x < width; x = x + width/20) {
            for(var y = 0; y < height; y = y + height/20) {
                var px = x + Math.random()*width/20;
                var py = y + Math.random()*height/20;
                var p = {x: px, originX: px, y: py, originY: py };
                points.push(p);
            }
        }

        // for each point find the 5 closest points
        for(var i = 0; i < points.length; i++) {
            var closest = [];
            var p1 = points[i];
            for(var j = 0; j < points.length; j++) {
                var p2 = points[j]
                if(!(p1 == p2)) {
                    var placed = false;
                    for(var k = 0; k < 5; k++) {
                        if(!placed) {
                            if(closest[k] == undefined) {
                                closest[k] = p2;
                                placed = true;
                            }
                        }
                    }

                    for(var k = 0; k < 5; k++) {
                        if(!placed) {
                            if(getDistance(p1, p2) < getDistance(p1, closest[k])) {
                                closest[k] = p2;
                                placed = true;
                            }
                        }
                    }
                }
            }
            p1.closest = closest;
        }

        // assign a circle to each point
        for(var i in points) {
            var c = new Circle(points[i], 2+Math.random()*2, 'rgba(255,255,255,0.3)');
            points[i].circle = c;
        }
    }

    // Event handling
    function addListeners() {
        if(!('ontouchstart' in window)) {
            window.addEventListener('mousemove', mouseMove);
        }
        window.addEventListener('scroll', scrollCheck);
        window.addEventListener('resize', resize);
    }

    function mouseMove(e) {
        var posx = posy = 0;
        if (e.pageX || e.pageY) {
            posx = e.pageX/2;
            posy = e.pageY;
        }
        else if (e.clientX || e.clientY)    {
            posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
            posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
        }
        target.x = posx;
        target.y = posy;
    }

    function scrollCheck() {
        if(document.body.scrollTop > height) animateHeader = false;
        else animateHeader = true;
    }

    function resize() {
        width = window.innerWidth;
        height = window.innerHeight/5;
        largeHeader.style.height = height+'px';
        canvas.width = width;
        canvas.height = height;
    }

    // animation
    function initAnimation() {
        animate();
        for(var i in points) {
            shiftPoint(points[i]);
        }
    }

    function animate() {
        if(animateHeader) {
            ctx.clearRect(0,0,width,height);
            for(var i in points) {
                // detect points in range
                if(Math.abs(getDistance(target, points[i])) < 4000) {
                    points[i].active = 0.3;
                    points[i].circle.active = 0.6;
                } else if(Math.abs(getDistance(target, points[i])) < 20000) {
                    points[i].active = 0.1;
                    points[i].circle.active = 0.3;
                } else if(Math.abs(getDistance(target, points[i])) < 40000) {
                    points[i].active = 0.02;
                    points[i].circle.active = 0.1;
                } else {
                    points[i].active = 0;
                    points[i].circle.active = 0;
                }

                drawLines(points[i]);
                points[i].circle.draw();
            }
        }
        requestAnimationFrame(animate);
    }

    function shiftPoint(p) {
        TweenLite.to(p, 1+1*Math.random(), {x:p.originX-50+Math.random()*100,
            y: p.originY-50+Math.random()*100, ease:Circ.easeInOut,
            onComplete: function() {
                shiftPoint(p);
            }});
    }

    // Canvas manipulation
    function drawLines(p) {
        if(!p.active) return;
        for(var i in p.closest) {
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            ctx.lineTo(p.closest[i].x, p.closest[i].y);
            ctx.strokeStyle = 'rgba(156,217,249,'+ p.active+')';
            ctx.stroke();
        }
    }

    function Circle(pos,rad,color) {
        var _this = this;

        // constructor
        (function() {
            _this.pos = pos || null;
            _this.radius = rad || null;
            _this.color = color || null;
        })();

        this.draw = function() {
            if(!_this.active) return;
            ctx.beginPath();
            ctx.arc(_this.pos.x, _this.pos.y, _this.radius, 0, 2 * Math.PI, false);
            ctx.fillStyle = 'rgba(156,217,249,'+ _this.active+')';
            ctx.fill();
        };
    }

    // Util
    function getDistance(p1, p2) {
        return Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2);
    }
    
})();

</script>

<div class="breakingNews" id="bn8">
    <div class="bn-title"><h2>Latest</h2><span></span></div>
    <ul id="ticker">
        <?=newsticker()?>
    </ul>
</div>
<p>&nbsp;</p>

<div itemprop="mainContentOfPage">

<?php if ($sreply != NULL) { ?>
    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <p><?=$sreply?></p>
    </div>
<?php } ?>

<form method="post" class="dump-form" action="" onsubmit="return processForm(this)">

    <div class="row">
        <div class="col-md-12">
            
            <div class="form-group" style="border: 1px solid #999">
                <textarea class="form-control" cols="40" id="id_code" name="code" rows="10"></textarea>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            


<a href="#" class="lexer-template" onclick="chooseLexer(this); return false"></a>
<?php if($_SESSION['user_logged'] == 1) { ?>
<input type="hidden" name="user" id="id_username" value="<?=$_SESSION['username']?>" />
<?php } ?>
<input type="hidden" name="lexer" id="id_lexer" value="text" />
<div class="lexers">
    
    <a href="#" class="lexer-text" onclick="chooseLexer(this); return false">Text only</a>
    
    <a href="#" class="lexer-php" onclick="chooseLexer(this); return false">PHP</a>
    
    <a href="#" class="lexer-python" onclick="chooseLexer(this); return false">Html5</a>
    
    <a href="#" class="lexer-cpp" onclick="chooseLexer(this); return false">Csharp</a>
    
    <a href="#" class="lexer-javascript" onclick="chooseLexer(this); return false">JavaScript</a>

    <span class="more-box"></span>
    <a class="more" href="#" onclick="toggleMoreList(); return false">More</a>
</div>

<div class="more-lexers" style="">
<ul class="list-unstyled">

<?=$llist?>

</ul>
</div>

<script type="text/javascript">
$(function() {
    chooseLexer($('[class="lexer-text"]').get(0));
});
function tick(){
    $('#ticker li:first').animate({'opacity':0}, 200, function () {
        $(this).appendTo($('#ticker')).css('opacity', 1);
    });
}
setInterval(function(){ tick () }, 5000);
</script>

        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                
                <label style="font-weight: normal">Comment (not required)</label>
                <input class="form-control" id="id_comment" maxlength="255" name="comment" type="text" />
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                
                <label style="font-weight: normal">Password (not required)</label>
                <input class="form-control" id="id_password" maxlength="50" name="password" type="text" />
            </div>
        </div>
    </div>

    <div class="submit">
        <input type="submit" class="btn btn-success" value="Submit" />     
    </div>
</form>


</div>