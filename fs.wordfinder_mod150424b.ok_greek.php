<?php
###########################################################
# FS.WordFinder
# Version: 3.5.1
# Author:  Robert
# Email:   brathna@gmail.com
# Website: http://fswordfinder.sourceforge.net/
###########################################################
# FS.WordFinder, prints out a letter grid with words hidden inside.
# Copyright (C) Robert Klein
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 2 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the
# Free Software Foundation, Inc.
# 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# or visit the following website:
# http://www.opensource.org/licenses/gpl-license.php
###########################################################
# INFO
# Hotscripts will always have the newest website address and version
# info:  http://www.hotscripts.com/Detailed/21656.html
###########################################################
# OPTIONS
# Options moved to options.php

# Change to point to the location of options.php

$pathToOptions = "./options.php";


###########################################################
# YOU DO NOT NEED TO EDIT ANYTHING BELOW THIS LINE UNLESS YOU KNOW
# WHAT YOU ARE DOING
###########################################################

if(!is_file($pathToOptions)) haltError("options.php not found");
@require($pathToOptions);

if ($wordlist && $useMysql) {

if(!is_file($pathToMysqlConnection)) haltError("mysqlconnection.php not found");
@require($pathToMysqlConnection);

}

##### Start output buffering

if($gzip) ini_set("zlib.output_compression", "Off");
ob_start();
ob_implicit_flush(0);


##### Get language of main form

$mainLang = ereg_replace("[@#$%^&*+\\/]","",$_COOKIE['mainLang']);
if($mainLang=="") $mainLang = $defaultLanguageFile;
if(!is_file($pathToLangDir."/".$mainLang)) haltError("default language file not found");
@require_once($pathToLangDir."/".$mainLang);


##### Check PHP version

if(phpversion()<4.1) haltError(_INVALID_PHP);


##### Add custom grid file

if(!is_file($pathToGridstyles)) haltError("gridStyles.php not found");
@require($pathToGridstyles);


##### Check to see if there is a saved game trying to pass through via URL

if(isset($_GET['savedgame'])) {
  $_POST['act'] = "finish";
  $_POST['more'] = "reloadSaved";
  $_POST['code'] = $_GET['savedgame'];
  if(get_magic_quotes_gpc()) {
    $_POST['code'] = stripslashes($_POST['code']);
  }
  $data = urlencode(substr($_POST['code'],4,-40));
  $_POST['code'] = substr($_POST['code'],0,4).$data.substr($_POST['code'],-40);
  unset($data);
}


if($_POST['act']=="finish") {


##### Parse saved game

if($_POST['more']=="reloadSaved") {
  $data = str_replace("\n","",$_POST['code']);
  $data = str_replace("\r","",$data);

  if(empty($data)) haltError(_EMPTY_SAVE);

  $sg_version = $data[0].$data[1];
  $random = $data[2].$data[3];
  $sha1 = substr($data,-40);
  $data = substr($data,4,-40);

  if($sg_version=="01" || $sg_version=="02") {
    if($sha1!=sha1($data)) haltError(_INVALID_SAVE);
    $data = urldecode($data);
    $tdec = "";
    for($a=0;$a<(strlen($data));$a++) {
      $temp = ord($data[$a]);
      $seed = ((100000*($random*$a))+49297) % 233280;
      $seed = ceil(($seed/(233280.0))*20)+20;
      $temp = $temp-$seed;
      $temp = chr($temp);
      $tdec .= $temp;
    }
    $data = $tdec;
  }

  $savedProgress = explode("|",$data);

  if($sg_version=="02") {
    $_POST['customLayout2'] = TRUE;
    $tdec = "";
    $savedProgress[7] = urldecode($savedProgress[7]);
    for($a=0;$a<(strlen($savedProgress[7]));$a++) {
      $temp = ord($savedProgress[7][$a]);
      $temp = $temp - 20;
      $temp = chr($temp);
      $tdec .= $temp;
    }
    $_POST['data'] = $tdec;
  }

  $srand = $savedProgress[0];
  list($_POST['puzzleTitle'],$_POST['numRows'],$_POST['numCols'],$_POST['fontSize'],$_POST['backgroundColor'],$_POST['fontColor'],$_POST['highlightColor'],$_POST['language'],$_POST['BorF'],$_POST['diag'],$_POST['upanddown'],$_POST['alphaSort'],$_POST['hideWords'],$_POST['wordsWindow'],$_POST['forPrint'],$_POST['randomWords'],$_POST['centerGrid'],$_POST['wordList'],$_POST['useLetters'],$_POST['checkered'],$_POST['lowerCase'],$_POST['gridStyle']) = explode(",",$savedProgress[1]);
  $_POST['inputWords'] = $savedProgress[5];
  $counter = $savedProgress[6];
  unset($_POST['dbWords']);
  unset($_POST['customLayout']);
}


##### Check custom layout and parse data

if($_POST['customLayout2']) {
  $cl_words = array();
  $t = explode("|",$_POST['data']);
  array_pop($t);
  foreach($t as $word) {
    $u = explode(",",$word);
    array_push($cl_words,$u[0]);
  }
}


##### Check if javascript is enabled; if not, then make forPrint TRUE

if($_POST['more']=="no") $_POST['forPrint']=TRUE;


##### Check rows and columns

$rows = ereg_replace("[^0-9]","",$_POST['numRows']);
$cols = ereg_replace("[^0-9]","",$_POST['numCols']);

if($rows<$minRows) $rows = $minRows;
elseif($rows>$maxRows) $rows = $maxRows;

if($cols<$minCols) $cols = $minCols;
elseif($cols>$maxCols) $cols = $maxCols;


##### Check fontsize

$fontSize = ereg_replace("[^0-9.]","",$_POST['font']);
if($fontSize=="") $fontSize = $fSize;


##### Check language

$language = ereg_replace("[^0-9]","",$_POST['language']);
if($language>count($languages)-1 || $language<0 || $language=="") $language=0;


##### Random generator

if(empty($srand)) $srand = hexdec(substr(md5(microtime()), -8)) & 0x7fffffff;
mt_srand ($srand);


##### Check number entered to pick words out of db

$dbWords = ereg_replace("[^0-9]","",$_POST['dbWords']);


##### Setup some variables for use in the picking of words

$maxGridSizeCounter = 0;
$rc = ($rows>=$cols) ? $rows+1 : $cols+1;


##### Pick words from db

if(!empty($dbWords) && $wordlist==1 && $useMysql==1) {

  $link = mysql_connect($host,$username,$password);
  mysql_select_db($database);

  if($dbWords>$max_wordlist) $dbWords=$max_wordlist;

  $sql = "SELECT * FROM FSWF_config";
  $res = mysql_query($sql);
  $true = 0;
  while($t = mysql_fetch_assoc($res)) {
    if($t[fsset]==$_POST['wordlist_lang']) { $set = $t[fsset]; $data = $t[fsdata]; $random = $t[random]; $data_len = strlen($data)-1; $word_count = $t[count]; $true=1; break; }
  }
  if(!$true) haltError(_INVALID_WORDLIST);

  $tracker = array();
  $counter = 0;
  for($a=0;$a<$dbWords;$a++) {
    for(;;) {
      if($random) { $temp = $set."_".$data[mt_rand(0,$data_len)]; $counter++; } else { $temp = $data; }
      $sql = "SELECT COUNT(word) FROM FSWF_".$temp;
      $count = mysql_result(mysql_query($sql),0)-1;
      $rand = mt_rand(0,$count);
      $counter++;
      $true = 0;
      foreach($tracker as $track) {
        if($track==$rand) { $true = 1; break; }
      }
      if(!$true) {
        $tracker[$a] = $rand;
        $sql = "SELECT word FROM FSWF_".$temp." LIMIT ".$rand.",1";
        $res = mysql_query($sql);
        $t = mysql_fetch_row($res);
        $len = strlen($t[0]);
        if($len>=$minChars && $len<=$maxChars && $len<$rc) { $words[$a] = $t[0]; $maxGridSizeCounter+=$len; break; }
      }
    }
    if($maxGridSizeCounter>$maxCharsLimit || $word_count<=count($words)) break;
  }

  mysql_close($link);

  unset($tracker);

  if(!is_file($pathToLangDir."/".$languages[$language].".php")) haltError("puzzle language file not found");
  @require_once($pathToLangDir."/".$languages[$language].".php");


##### Pick words from flat file word lists

} else if(!empty($dbWords) && $wordlist==1) {

  if(!is_file($pathToLangDir."/".$languages[$language].".php")) haltError("puzzle language file not found");
  @require_once($pathToLangDir."/".$languages[$language].".php");

  if($dbWords>$max_wordlist) $dbWords=$max_wordlist;

  $_contents = array();
  $counter = 0;

  $contents = array();
  $a = 0;
  if(strlen($_POST['wordlist_lang'])>100) haltError(_TOO_LONG_FILENAME);
  $filename = $pathToWordlists."/".ereg_replace("[@#$%^&*+\\/]","",$_POST['wordlist_lang']);
  $handle = fopen($filename, "r");
  if($handle) {
    while(!feof($handle)) {
      $contents[$a] = fgets($handle, 26);
      $a++;
    }
    fclose($handle);
  } else { haltError(_UNABLE_WORDLIST); }

  if($dbWords>count($contents)) $dbWords=count($contents);

  $count = count($contents)-1;
  for($a=0;$a<$dbWords;) {
    $rand = mt_rand(0,$count);
    $counter++;
    $true = 0;
    foreach($_contents as $c) {
      if($c==$rand) { $true = 1; break; }
    }
    if(!$true) {
      $_contents[$a] = $rand;
      $contents[$_contents[$a]] = strtoupper($contents[$_contents[$a]]);
      $contents[$_contents[$a]] = strtr($contents[$_contents[$a]], ${$languages[$language]}[1], ${$languages[$language]}[0]);
      $contents[$_contents[$a]] = ereg_replace("[^".$chars."]", "", trim($contents[$_contents[$a]]));
      $len = strlen($contents[$_contents[$a]]);
      if($len>=$minChars && $len<=$maxChars && $len<$rc) { $words[$a] = $contents[$_contents[$a]]; $maxGridSizeCounter+=$len; }
      if($maxGridSizeCounter>$maxCharsLimit) break;
      $a++;
    }
  }
  unset($contents);
  unset($_contents);

##### Check user inputted words

} else {

  $temp = array();
  $words = array();

  if($counter) { for($a=0;$a<$counter;$a++) mt_rand(0,1); $tempWords = $_POST['inputWords']; }
  else {
    $tempWords = str_replace("\r\n", ",", $_POST['inputWords']); # Windows
    $tempWords = str_replace("\n", ",", $tempWords);             # *nix
    $tempWords = str_replace("\r", ",", $tempWords);             # Macs
  }

  if(!is_file($pathToLangDir."/".$languages[$language].".php")) haltError("puzzle language file not found");
  @require_once($pathToLangDir."/".$languages[$language].".php");

  if(!$counter) {
    if(strlen($tempWords)>$maxCharsLimit) haltError(_TOO_MANY_CHARS);
    $tempWords = strtoupper($tempWords);
    $tempWords = str_replace(" ", ",", $tempWords);
    $tempWords = ereg_replace("^[,]*","",$tempWords);
    if($languages[$language]!="Numbers") $tempWords = strtr($tempWords, ${$languages[$language]}[1], ${$languages[$language]}[0]);
    $tempWords = ereg_replace("[^".$chars.",]", "", $tempWords);
  }

  $temp = explode(",", $tempWords);
  $numWords = count($temp);
  if($numWords) {
    for($x=0,$y=0,$z=0;$z<$numWords;$y++,$z++) {
      $tmp = strlen($temp[$y]);
      if($tmp>=$minChars && $tmp<=$maxChars && $tmp<$rc) { $words[$x] = $temp[$y]; $maxGridSizeCounter+=$tmp; $x++; }
      if($maxGridSizeCounter>$maxCharsLimit) break;
    }
  }
  if($words[0]=="") haltError(_ONE_WORD);

##### Remove same words

  $words = array_values(array_unique($words));

}

$unsortedWords = $words;


##### Check puzzle title

$puzzleTitle = ereg_replace("[@#$%^&*+\\/]","",$_POST['puzzleTitle']);
if(strlen($puzzleTitle)>$puzzleTitleSize) haltError(_TOO_LONG_TITLE);


##### Pick random words from user inputted word list

if($_POST['randomWords']!="" && $_POST['randomWords']!=0 && empty($dbWords)) {
  $randomWords = ereg_replace("[^0-9]","",$_POST['randomWords']);
  if(count($words)>$randomWords) {

    $temp = array();
    for($a=0;$a<$randomWords;) {
      $true = 0;
      $rand = mt_rand(0,count($words)-1);
      foreach($temp as $track) {
        if($track==$rand) { $true = 1; break; }
      }
      if(!$true) { $temp[$a]=$rand; $a++; }
    }
    $t = array();
    if($randomWords>1) {
      foreach($temp as $key => $v) {
        $t[$key] = $words[$v];
      }
    } else { $t[] = $words[$temp[0]]; }
    $words = $t;
  }
} else $randomWords = "";


##### Another check to make sure $words isn't empty before preceding

if($words[0]=="") haltError(_NO_WORDS);


##### Setup variables, dump anything in them

$xnum = $ynum = $dir = $temp = $tempWords = $xy = "";
$tempInserted = $grid = $gridWords = $xyArray = array();


##### Check custom layout and parse data

unset($d);
if($_POST['customLayout2']) {
  $t = explode("|",$_POST['data']);
  $co = "";
  array_pop($t);
  $d=0;
  foreach($t as $word) {
    $u = explode(",",$word);
    $u_len = strlen($u[0]);
    for($i=1;$i<=$u_len;$i++) {
      $x = ceil($u[$i]/$cols);
      $y = $cols - (($x*$cols)-$u[$i]);
      $co .= $u[$i].",";
      $grid[$x][$y][0] = $u[0][$i-1];
      $gridWords[$x][$y] .= $u[0].",";
    }
    $tempInserted[$d] = $u[0];
    $xyArray[$tempInserted[$d]] = $co;
    $d++;
    $co = "";
  }
}


##### Check for custom grid style

if($_POST['gridStyle']!="square") {

  $gS = "";
  foreach($gridStyleOptions as $t) {
    if($t==$_POST['gridStyle'] || $_POST['gridStyle']=="customGrid") { $gS=$_POST['gridStyle']; break; }
  }
  if(!empty($gS)) {
    if($gS=="customGrid") $customGrid = explode(",",$_COOKIE['customGrid']);

    $rows = ${$gS}[2];
    $cols = ${$gS}[3];
    $v = 0;
    for($t=1;$t<=$rows;$t++) {
      for($u=1;$u<=$cols;$u++,$v++) {
        if(${$gS}[0][$v]==0) $grid[$t][$u]=" ";
      }
    }
  }
}


##### Custom layout

if($_POST['customLayout']) customLayout();


##### Sort $words by length of word

usort($words,"cmp");


##### Main loop

$d = (isset($d)) ? $d : 0;
foreach($words as $word) {

  if($_POST['customLayout2']) {
    $found = 0;
    foreach($cl_words as $cl) {
      if($cl == $word) $found = 1;
    }
    if($found) continue;
  }

  $count = strlen($word);
  if(mt_rand(1,2)==1) {
    $score = 0;
    $scoreXY = $scoreXYTemp = $dirXY = "";
    for($tries=1;$tries<=$retries;$tries++) {
      $number = mt_rand(0,$count-1);
      make_random();
      $scoreTemp=0;
      if($dir==1)     { $xnum+=$number; $ynum+=$number; }
      elseif($dir==2) { $xnum+=$number; }
      elseif($dir==3) { $xnum+=$number; $ynum-=$number; }
      elseif($dir==4) { $ynum+=$number; }
      elseif($dir==5) { $ynum-=$number; }
      elseif($dir==6) { $xnum-=$number; $ynum+=$number; }
      elseif($dir==7) { $xnum-=$number; }
      elseif($dir==8) { $xnum-=$number; $ynum-=$number; }
      if($xnum>0 && $xnum<=$rows && $ynum>0 && $ynum<=$cols) {
        if(check($word,0)) {
          if($scoreTemp>$score && $scoreTemp<$minChars) { $score=$scoreTemp; $scoreXY=$scoreXYTemp; $dirXY=$dir; if($score==$minChars-1) break; }
          elseif($score==0 && $scoreTemp==0) { $scoreXY=$scoreXYTemp; $dirXY=$dir; }
        }
      }
    }
    if(!empty($scoreXY)) {
      $dir=$dirXY;
      list($xnum,$ynum)=explode(",",$scoreXY);
      if(check($word,1)) { $tempInserted[$d]=$word; $xyArray[$tempInserted[$d]]=$xy; $d++; }
    }
  } else {
    for($tries=1;$tries<=$retries;$tries++) {
      make_random();
      if(check($word,1)) { $tempInserted[$d]=$word; $xyArray[$tempInserted[$d]]=$xy; $d++; break; }
    }
  }
}

if($tempInserted[0]=="") haltError(_NO_GRID);

unset($words);


##### Put unsortedWords into inserted

$inserted = array();
$k = 0;
foreach($unsortedWords as $un) {
  foreach($tempInserted as $ti) {
    if($un==$ti) { $inserted[$k] = $un; $k++; break; }
  }
}
unset($tempInserted);


##### Sort alphabetically

if($_POST['alphaSort']) { sort($inserted); }


##### Get colors

$backgroundColor = ereg_replace("[^0-9A-Za-z#]","",$_POST['backgroundColor']);
if($backgroundColor=="") $backgroundColor = $bgColor;
$fontColor = ereg_replace("[^0-9A-Za-z#]","",$_POST['fontColor']);
if($fontColor=="") $fontColor = $fColor;
$highlightColor = ereg_replace("[^0-9A-Za-z#]","",$_POST['highlightColor']);
if($highlightColor=="") $highlightColor = $highColor;


$countInserted = count($inserted);

$xmlOutput = $_REQUEST['xml'];
if($xmlOutput!=1) {

##### Send no cache headers

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


##### Fill grid page
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">

<head><title><?php echo $title; if($_POST['forPrint'] && $_POST['hideWords']) echo " - ".$countInserted._WORDS_IN_GRID; ?></title>
<meta http-equiv="content-type" content="text/html; charset=<?= ${$languages[$language]}[3]; ?>" />

<style type="text/css">
  html,body{background:<?= $backgroundColor; ?>;color:<?= $fontColor; ?>;height:100%;}
  td{font-size: <?= $fontSize; ?>mm; font-family: Courier New;padding:0px <?= ($fontSize+1); ?>px 0px <?= ($fontSize+1); ?>px;}
  #sp{width:15px;}
</style>

<?php if(!$_POST['forPrint'] && !$_POST['wordsWindow']) {
$t = explode(",",$savedProgress[3]);
?>

<script type="text/javascript"><!--
var words = new Array();
var wordcount=0;
var minutes=<?= ($t[0]!=0) ? $t[0] : 0; ?>;
var seconds=<?= ($t[1]!=0) ? $t[1] : 0; ?>;
var rev=0;
var mistakes=0;
var paused=0;
var revealed='';
var wordList=<?= ($_POST['hideWords']) ? 1 : 0; ?>;
var savedGame=1;
var createdMain='';

var mouseIsDown = 0;

<?php if($_POST['more']=="reloadSaved") { ?>
function reload() {
  var re = '<?= $savedProgress[4]; ?>';
  var er = re.split(",");
  if(er[0]!='') {
    mouseIsDown = 1;
    for(i=0;i<er.length;i++) {
      words[words.length] = er[i];
      document.getElementById(er[i]+'r').onmousedown();
    }
    mouseIsDown = 0;
  }
  t = '<?= substr($savedProgress[2],0,-1); ?>';
  if(t!='') {
    u = t.split(',');
    for(i=0;i<u.length;i++) document.getElementById(u[i]).onmousedown();
  }
  savedGame=1;
  mistakes = <?= ($t[2]!=0) ? $t[2] : 0; ?>;
}
<?php } ?>

function eventHandle() {
  document.onkeypress=pause;
  document.onmousedown=omd;
  document.onmouseup=omu;
  document.onselectstart=omd;
}

function omd() { mouseIsDown = 1; return false; }

function omu() { mouseIsDown = 0; if(highlighted.length) clearHighlighted(0,0); return false; }

function pause(e) {
  var code;
  if (!e) var e = window.event;
  if (e.keyCode) code = e.keyCode;
  else if (e.which) code = e.which;
  if(code==13 && paused!=2) {
    if(!paused) {
      document.getElementById('grid').style.visibility = 'hidden';
      document.getElementById('list').style.visibility = 'hidden';
      document.getElementById('paused').style.visibility = 'visible';
      paused=1;
    }
    else {
      document.getElementById('grid').style.visibility = 'visible';
      document.getElementById('list').style.visibility = 'visible';
      document.getElementById('paused').style.visibility = 'hidden';
      paused=0;
    }
  }
  else if(code==114 || code==82) {
    if(!wordList) {
      document.getElementById('list').style.visibility = 'hidden';
      wordList=1;
    }
    else {
      document.getElementById('list').style.visibility = 'visible';
      wordList=0;
    }
  }
  else if(code==115 || code==83) {
    var random = Math.ceil(Math.random()*10)+20;
    var sg_version = '<?= ($_POST['customLayout2']) ? "02" : "01"; ?>';
    <?php
if($_POST['customLayout2']) {
  $tdec = "";
  for($a=0;$a<(strlen($_POST['data']));$a++) {
    $temp = ord($_POST['data'][$a]);
    $temp = $temp + 20;
    $temp = chr($temp);
    $tdec .= $temp;
  }
  $tdec = urlencode($tdec);
}
  echo "var cL = '".$tdec."';";
?>

    var temp = '';
    var tenc = '';

    var save = '<?php echo $srand."|".$puzzleTitle.",".$rows.",".$cols.",".$fontSize.",".$backgroundColor.",".$fontColor.",".$highlightColor.",".$language.",".$_POST['BorF'].",".$_POST['diag'].",".$_POST['upanddown'].",".$_POST['alphaSort'].",".$_POST['hideWords'].",".$_POST['wordsWindow'].",".$_POST['forPrint'].",".$randomWords.",".$_POST['centerGrid'].",".$_POST['wordList'].",".$_POST['useLetters'].",".$_POST['checkered'].",".$_POST['lowerCase']; echo (empty($gS)) ? ",square" : ",".$gS; ?>' + '|' + revealed + '|' + minutes + ',' + seconds + ',' + mistakes + '|' + words + '|<?php foreach($unsortedWords as $in) { echo $in.","; } ?>' + '|' + '<?= ($counter) ? $counter : 0; ?>' + '|' + cL;

    for(a=0;a<save.length;a++) {
      temp = save.charCodeAt(a);
      seed = ((100000*(random*a))+49297) % 233280;
      seed = Math.ceil((seed/233280.0)*20)+20;
      temp = (temp+seed);
      temp = String.fromCharCode(temp);
      tenc += temp;
    }
    save = escape(tenc);

    var saved = '';
    var count = 0;
    var co = 0;
    var t = (Math.ceil(save.length/1000));
    for(h=1;h<=t;h++) {
      var temp = '';
      count = (1000*h);
      temp = save.slice(co,count);
      co = (co+1000);
      saved += temp + '\n';
    }
    saved = saved.substr(0,saved.length-1);
    saved = random+saved+hex_sha1(saved);
    saved = sg_version+saved;

    paused=1;
    savedGame=1;

    document.getElementById('grid').style.visibility = 'hidden';
    document.getElementById('list').style.visibility = 'hidden';

<?php if(ereg("MSIE",$_SERVER['HTTP_USER_AGENT']) && !ereg("Opera",$_SERVER['HTTP_USER_AGENT'])) { ?>
  window.clipboardData.setData('Text', saved);
  alert('<?= _CLIPBOARD; ?>');
  paused=0;
  hideSave();
<?php } else { ?>
    document.getElementById('code').style.display = 'block';
    document.getElementById('area').value = saved;
<?php if(ereg("Gecko",$_SERVER['HTTP_USER_AGENT'])) { ?>
    document.getElementById('area').focus();
    document.getElementById('area').select();
<?php } } ?>
  var echoThis = '<br /><br /><a href="<?= $_SERVER['PHP_SELF']; ?>?savedgame='+saved+'"><?= _SAVE_URL; ?></a>';

  createdMain = document.createElement("DIV");
  createdMain.id = "savegameURL";
  createdMain.innerHTML = echoThis;
  document.getElementById('code').appendChild(createdMain);

  }
}

function hideSave() {
  document.getElementById('code').style.display = 'none';
  document.getElementById('grid').style.visibility = 'visible';
  document.getElementById('list').style.visibility = 'visible';

  document.getElementById('code').removeChild(createdMain);

<?php if($_POST['hideWords']) echo "document.getElementById('list').style.visibility = 'hidden';"; ?>

  paused=0;
  savedGame = 1;
}

function display(){
  if(!paused) seconds++;
  if(seconds==60) { minutes++;seconds=0; }
  remaining(0);
  setTimeout("display()",1000);
}

function lineme(id,revealed) {
  document.getElementById(id).style.textDecoration = 'line-through';
  wordcount++;
  if(revealed) rev++;
  if(wordcount==<?= $countInserted; ?>) {
    if(minutes!=0) {
      if(minutes==1) var minPlural = "minute"; else var minPlural = "minutes";
      if(seconds==1) var secPlural = "second"; else var secPlural = "seconds";
      time = minutes+' '+minPlural+' '+seconds+' '+secPlural;
    } else { time = seconds+' seconds'; }
    paused=2;
    document.getElementById('list').style.visibility = 'visible';
    alert('<?= _PUZZLE_FINISHED;?>\n==========\n<?= _TIME_TAKEN; ?>:\t\t'+time+'\n<?= _MISTAKES; ?>:\t\t'+mistakes+'\n<?= _WORDS_REVEALED; ?>:\t'+rev);
  }
}

function r(cross,revealLetters) {
  tenc = '';
  temp = '';
  revealLetters = unescape(revealLetters);
  for(a=0;a<revealLetters.length;a++) {
    temp = revealLetters.charCodeAt(a);
    temp = (temp-20);
    temp = String.fromCharCode(temp);
    tenc += temp;
  }
  revealLetters = tenc.split(",");
  var id = '';

  if(!mouseIsDown) {
    for(var i=0;i<revealLetters.length;i++) {
      id = revealLetters[i];
      document.getElementById('_'+id).style.color = '<?php echo ($fontColor=="red") ? "blue" : "red"; ?>';
    }
    revealed+=cross+'r,';
    hideQM(cross+'r');
    lineme(cross,1);

  } else {
    for(var i=0;i<revealLetters.length;i++) {
      id = revealLetters[i];
      document.getElementById('_'+id).style.backgroundColor = '<?= $highlightColor; ?>';
    }
    hideQM(cross+'r');
    lineme(cross,0);
  }
  savedGame=0;
}

function remaining(m) {
  if(m) mistakes++;
  if(paused==1) { var p=' (<?= _PAUSED; ?>)'; }
  else if(paused==2) { var p=' (<?= _PUZZLE_FINISHED; ?>)'; savedGame=1; }
  else { var p=''; }
  if(minutes>0) {
    if(minutes==1) var minPlural = "<?= _MINUTE; ?>"; else var minPlural = "<?= _MINUTES; ?>";
    if(seconds==1) var secPlural = "<?= _SECOND; ?>"; else var secPlural = "<?= _SECONDS; ?>";
    time = minutes+' '+minPlural+' '+seconds+' '+secPlural;
  } else { time = seconds+' <?= _SECONDS; ?>'; }
  if(mistakes==1) var misP = "<?= _MISTAKE; ?>"; else var misP = "<?= _MISTAKES; ?>";
  if(rev==1) var revP = "<?= _WORD; ?>"; else var revP = "<?= _WORDS; ?>";
  window.status=<?= $countInserted; ?>-wordcount+' <?= _OF; ?> <?= $countInserted; ?> <?= _WORDS_REMAINING; ?> ('+mistakes+' '+misP+' - '+rev+' '+revP+' <?= _REVEALED; ?>) - <?= _TIME; ?>: '+time+p;
}

function hideQM(id) {
  document.getElementById(id).style.visibility = 'hidden';
}

function showQM(id) {
  document.getElementById(id).style.visibility = 'visible';
}

function hideWords() {
  document.getElementById('list').style.visibility='hidden';
}

function unload(e) {
  if(!savedGame) {
    msg = '<?= _UNSAVED_SETTINGS; ?>';
    if (!e && window.event) {
      e = window.event;
    }
    e.returnValue = msg;
    return msg;
  }
}

window.onbeforeunload = function(e) {
  if (!e) e = event;
  return unload(e);
}



/*
 * A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined
 * in FIPS PUB 180-1
 * Version 2.1 Copyright Paul Johnston 2000 - 2002.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 */

var hexcase = 0;
var b64pad  = "";
var chrsz   = 8;

function hex_sha1(s){return binb2hex(core_sha1(str2binb(s),s.length * chrsz));}
function b64_sha1(s){return binb2b64(core_sha1(str2binb(s),s.length * chrsz));}
function str_sha1(s){return binb2str(core_sha1(str2binb(s),s.length * chrsz));}
function hex_hmac_sha1(key, data){ return binb2hex(core_hmac_sha1(key, data));}
function b64_hmac_sha1(key, data){ return binb2b64(core_hmac_sha1(key, data));}
function str_hmac_sha1(key, data){ return binb2str(core_hmac_sha1(key, data));}

function sha1_vm_test()
{
  return hex_sha1("abc") == "a9993e364706816aba3e25717850c26c9cd0d89d";
}

function core_sha1(x, len)
{
  x[len >> 5] |= 0x80 << (24 - len % 32);
  x[((len + 64 >> 9) << 4) + 15] = len;

  var w = Array(80);
  var a =  1732584193;
  var b = -271733879;
  var c = -1732584194;
  var d =  271733878;
  var e = -1009589776;

  for(var i = 0; i < x.length; i += 16)
  {
    var olda = a;
    var oldb = b;
    var oldc = c;
    var oldd = d;
    var olde = e;

    for(var j = 0; j < 80; j++)
    {
      if(j < 16) w[j] = x[i + j];
      else w[j] = rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1);
      var t = safe_add(safe_add(rol(a, 5), sha1_ft(j, b, c, d)), 
                       safe_add(safe_add(e, w[j]), sha1_kt(j)));
      e = d;
      d = c;
      c = rol(b, 30);
      b = a;
      a = t;
    }

    a = safe_add(a, olda);
    b = safe_add(b, oldb);
    c = safe_add(c, oldc);
    d = safe_add(d, oldd);
    e = safe_add(e, olde);
  }
  return Array(a, b, c, d, e);
  
}

function sha1_ft(t, b, c, d)
{
  if(t < 20) return (b & c) | ((~b) & d);
  if(t < 40) return b ^ c ^ d;
  if(t < 60) return (b & c) | (b & d) | (c & d);
  return b ^ c ^ d;
}

function sha1_kt(t)
{
  return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 :
         (t < 60) ? -1894007588 : -899497514;
}  

function core_hmac_sha1(key, data)
{
  var bkey = str2binb(key);
  if(bkey.length > 16) bkey = core_sha1(bkey, key.length * chrsz);

  var ipad = Array(16), opad = Array(16);
  for(var i = 0; i < 16; i++) 
  {
    ipad[i] = bkey[i] ^ 0x36363636;
    opad[i] = bkey[i] ^ 0x5C5C5C5C;
  }

  var hash = core_sha1(ipad.concat(str2binb(data)), 512 + data.length * chrsz);
  return core_sha1(opad.concat(hash), 512 + 160);
}

function safe_add(x, y)
{
  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}

function rol(num, cnt)
{
  return (num << cnt) | (num >>> (32 - cnt));
}

function str2binb(str)
{
  var bin = Array();
  var mask = (1 << chrsz) - 1;
  for(var i = 0; i < str.length * chrsz; i += chrsz)
    bin[i>>5] |= (str.charCodeAt(i / chrsz) & mask) << (24 - i%32);
  return bin;
}

function binb2str(bin)
{
  var str = "";
  var mask = (1 << chrsz) - 1;
  for(var i = 0; i < bin.length * 32; i += chrsz)
    str += String.fromCharCode((bin[i>>5] >>> (24 - i%32)) & mask);
  return str;
}

function binb2hex(binarray)
{
  var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
  var str = "";
  for(var i = 0; i < binarray.length * 4; i++)
  {
    str += hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8+4)) & 0xF) +
           hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8  )) & 0xF);
  }
  return str;
}

function binb2b64(binarray)
{
  var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
  var str = "";
  for(var i = 0; i < binarray.length * 4; i += 3)
  {
    var triplet = (((binarray[i   >> 2] >> 8 * (3 -  i   %4)) & 0xFF) << 16)
                | (((binarray[i+1 >> 2] >> 8 * (3 - (i+1)%4)) & 0xFF) << 8 )
                |  ((binarray[i+2 >> 2] >> 8 * (3 - (i+2)%4)) & 0xFF);
    for(var j = 0; j < 4; j++)
    {
      if(i * 8 + j * 6 > binarray.length * 32) str += b64pad;
      else str += tab.charAt((triplet >> 6*(3-j)) & 0x3F);
    }
  }
  return str;
}
// End of SHA-1

var highlighted = new Array();
var startingCell = 0;
var endingCell = 0;
var startX = 0;
var startY = 0;
function cellHighlight(cell) {

  if(paused==2) return false;

  var good = true;
  var x = Math.ceil(cell/<?= $cols; ?>);
  var y = (<?= $cols; ?> - ((x*<?= $cols; ?>)-cell));

  endingCell = cell;
  if(startingCell==0) {
    startingCell = cell;
    startX = Math.ceil(cell/<?= $cols; ?>);
    startY = (<?= $cols; ?> - ((x*<?= $cols; ?>)-cell));
  }

  var relX = x - startX;
  var relY = y - startY;

  if(startingCell==endingCell) {
    document.getElementById('_'+cell).style.backgroundColor = '<?= $highlightColor; ?>';
    highlighted[highlighted.length]=cell;
  } else if(relY==0 && relX<0) {
    cleanUp();
    for(var a=startingCell;;a=(a-<?= $cols; ?>)) {
      highlightMe(a);
      if(a==endingCell) break;
    }
  } else if(relY==0 && relX>0) {
    cleanUp();
    for(var a=startingCell;;a=(a+<?= $cols; ?>)) {
      highlightMe(a);
      if(a==endingCell) break;
    }
  } else if(relX==0 && relY<0) {
    cleanUp();
    for(var a=startingCell;;a=(a-1)) {
      highlightMe(a);
      if(a==endingCell) break;
    }
  } else if(relX==0 && relY>0) {
    cleanUp();
    for(var a=startingCell;;a=(a+1)) {
      highlightMe(a);
      if(a==endingCell) break;
    }
  } else if(Math.abs(relX)==Math.abs(relY)) {
    cleanUp();
    if(relX<0 && relY<0) {
      for(var a=startingCell;;a=(a-<?= $cols+1 ?>)) {
        highlightMe(a);
        if(a==endingCell) break;
      }
    } else if(relX<0 && relY>0) {
      for(var a=startingCell;;a=(a-<?= $cols-1 ?>)) {
        highlightMe(a);
        if(a==endingCell) break;
      }
    } else if(relX>0 && relY<0) {
      for(var a=startingCell;;a=(a+<?= $cols-1 ?>)) {
        highlightMe(a);
        if(a==endingCell) break;
      }
    } else if(relX>0 && relY>0) {
      for(var a=startingCell;;a=(a+<?= $cols+1 ?>)) {
        highlightMe(a);
        if(a==endingCell) break;
      }
    }
  } else { good = false; }

}

function clearHighlighted(xy,word) {
  var good = false;
  var temp = 0;
  if(xy!=0) {
    for(var x=xy.length-1;x>=0;x--) {
      var xyTemp = xy[x].split("|");
      var xyt = '';
      if(highlighted.length==xyTemp.length) {
        for(var a=xyTemp.length-1;a>=0;a--) {
          for(var b=xyTemp.length-1;b>=0;b--) {
            var highTemp = highlighted[b].toString();
            if(highTemp.charAt(0)=='x') xyt='x'+xyTemp[a];
            else xyt = xyTemp[a];
            if(xyt==highlighted[b]) { temp++; break; }
          }
        }
        if(temp==xyTemp.length) {
          tenc = '';
          temp = '';
          for(var a=0;a<word.length;a++) {
            word[a] = unescape(word[a]);
            for(b=0;b<word[a].length;b++) {
              temp = word[a].charCodeAt(b);
              temp = (temp-20);
              temp = String.fromCharCode(temp);
              tenc += temp;
            }
            word[a] = tenc;
            tenc = '';
          }
          if(document.getElementById(word[x]).style.textDecoration!='line-through') {
            words[words.length] = word[x];
            hideQM(word[x]+'r');
            lineme(word[x],0);
            good = true;
          } else if(document.getElementById('_'+highlighted[0]).style.color=='<?php echo ($fontColor=="red") ? "blue" : "red"; ?>') {
            good = false;
          } else { good = true; }
        }
      }
    }
  }

  if(!good) {
    if(highlighted.length>0) mistakes++;
    for(var a=highlighted.length-1;a>=0;a--) {
      var highTemp = highlighted[a].toString();
      if(highTemp.charAt(0)!='x') {
        document.getElementById('_'+highlighted[a]).style.backgroundColor = '<?= $backgroundColor; ?>';
      }
    }
  }
  highlighted = new Array();
  startingCell = 0;
  savedGame = 0;
}

function cleanUp() {
  for(var a=highlighted.length-1;a>=0;a--) {
    var highTemp = highlighted[a].toString();
    if(highTemp.charAt(0)!='x') {
      document.getElementById('_'+highlighted[a]).style.backgroundColor = '<?= $backgroundColor; ?>';
    }
  }
  highlighted = new Array();
}

function highlightMe(a) {
  if(document.getElementById('_'+a).style.backgroundColor != '<?= $highlightColor; ?>') {
    document.getElementById('_'+a).style.backgroundColor = '<?= $highlightColor; ?>';
    highlighted[highlighted.length]=a;
  } else {
    highlighted[highlighted.length]='x'+a;
  }
}

//--></script>

<?php

}

if($_POST['wordsWindow'] && !$_POST['hideWords']) {

  $tmp = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?act=listwords&font=".$fontSize."&charset=".${$languages[$language]}[3]."&wordlist=";

  foreach($inserted as $in) {
    $tmp .= $in.",";
  }

?>
<script language="javascript" type="text/javascript"><!--
window.onload=window.open("<?= $tmp; ?>",null,"height=700,width=700,status=no,toolbar=no,menubar=yes,location=no,resizable=yes,directories=no,scrollbars=yes");
//--></script>

<?php
}

if($_POST['forPrint'] || $_POST['wordsWindow']) echo "</head><body>";
elseif($_POST['more']=="reloadSaved") echo "</head><body style=\"cursor:crosshair\" onload=\"eventHandle();display();reload();\">";
else echo "</head><body style=\"cursor:crosshair;\" onload=\"eventHandle();display();\">";

if(!empty($puzzleTitle))
  echo "<div style=\"text-align:center;font-weight:bold;font-size:35px;text-decoration:underline;font-family:Arial;margin-bottom:20px;\">".$puzzleTitle."</div>";


##### Print out the grid

$border = ($_POST['wordList']=="bottom") ? "bottom" : "right";

$echoThis = "";

if($_POST['useLetters']) {
  $ch = 0;
  $chars = array();
  for($tok=strlen($inserted)-1;$tok>=0;$tok--) {
    for($kot=strlen($inserted[$tok])-1;$kot>=0;$kot--) {
      $chars[$ch] = $inserted[$tok][$kot];
      $ch++;
    }
  }
  $chars = array_values(array_unique($chars));
  $chars = implode("",$chars);
}

$charsLen = strlen($chars)-1;

$hideThis = "style=\"color:".$backgroundColor."\"";
$hiding = 1;

if($_POST['centerGrid']) { $echoThis .= "<table style=\"height:100%;margin-left:auto;margin-right:auto;text-align: left;\"><tr><td>"; }
$echoThis .= "<table style=\"border:0px;\"><tr><td valign=\"top\"><div id=\"grid\">";
$echoThis .= (!$_POST['wordsWindow'] && !$_POST['hideWords']) ? "<table border=\"0\" cellspacing=\"0\" style=\"border-".$border.":1px solid ".$fontColor.";\">" : "<table style=\"border:0px;\" cellspacing=\"0\">";
for($x=1,$ran=1;$x<=$rows;$x++) {
  $echoThis .= "<tr>";
  $hiding = ($hiding) ? 0 : 1;
  for($y=1;$y<=$cols;$y++,$ran++) {
    $random = mt_rand(0,$charsLen);

    if($gridWords[$x][$y]!="") {
      $gridWordsTemp = explode(",",$gridWords[$x][$y]);
      $c = count($gridWordsTemp)-1;

$p=array();
$m=0;
foreach($gridWordsTemp as $g) {
  for($q=0;$q<strlen($g);$q++) {
    $r = ord($g[$q]);
    $r = $r + 20;
    $r = urlencode(chr($r));
    $p[$m] .= $r;
  }
  $m++;
}

      $gridTemp = "w=new Array('".$p[0]."'";

      $xyTemp = "x=new Array('".substr(str_replace(",","|",$xyArray[$gridWordsTemp[0]]),0,-1)."'";

      for($z=1;$z<$c;$z++) { $gridTemp .= ",'".$p[$z]."'"; $xyTemp .= ",'".substr(str_replace(",","|",$xyArray[$gridWordsTemp[$z]]),0,-1)."'"; }

      $gridTemp .= ")";
      $xyTemp .= ")";
    }

    $per1 = (mt_rand(0,1)==1) ? "%" : "";
    $per2 = (mt_rand(0,1)==1) ? "%" : "";

    $hideTemp = ($hiding && $_POST['checkered']) ? $hideThis : "";

    if($_POST['lowerCase'] && $languages[$language]!="Numbers") {
      $ranChar = strtr($chars[$random], ${$languages[$language]}[0], ${$languages[$language]}[1]);
      $ranChar = strtolower($ranChar);
      $gridChar = strtr($grid[$x][$y][0], ${$languages[$language]}[0], ${$languages[$language]}[1]);
      $gridChar = strtolower($gridChar);
    } else {
      $ranChar = $chars[$random];
      $gridChar = $grid[$x][$y][0];
    }

    if(!$_POST['forPrint'] && !$_POST['wordsWindow']) {
      $echoThis .= ($grid[$x][$y][0]=="") ? "<td id=\"_".$ran."\" onmouseover=\"if(mouseIsDown) cellHighlight(".$ran.");\" onmousedown=\"mouseIsDown=1;cellHighlight(".$ran.");\" onmouseup=\"w=new Array('".$per1.substr(md5(microtime()),-(mt_rand(2,7))).$per2.substr(md5(microtime()),-(mt_rand(2,7)))."');x=new Array('21|31|41|51|61');w=x=0;clearHighlighted(x,0);\" ".$hideTemp.">".$ranChar."</td>" : "<td id=\"_".$ran."\" onmouseover=\"if(mouseIsDown) { ".$xyTemp."; cellHighlight(".$ran.",x); }\" onmousedown=\"".$xyTemp."; mouseIsDown=1;cellHighlight(".$ran.",x);\" onmouseup=\"".$gridTemp.";".$xyTemp.";clearHighlighted(x,w);\" ".$hideTemp.">".$gridChar."</td>";
    } else { $echoThis .= ($grid[$x][$y][0]=="") ? "<td id=\"_".$ran."\" ".$hideTemp.">".$ranChar."</td>" : "<td id=\"_".$ran."\">".$gridChar."</td>"; }

    $hiding = ($hiding) ? 0 : 1;

  }
  if ($_POST['wordList']=="right") $echoThis .= "<td>&nbsp;</td>";
  $echoThis .= "</tr>";
}
if ($_POST['wordList']=="bottom") $echoThis .= "<tr><td>&nbsp;</td></tr>";
$echoThis .= "</table>";
$echoThis .= "</div></td>";


##### List words on grid page


if ($_POST['wordList']=="bottom") {
  $echoThis .= "</tr><tr>";
  $margin = "top";
} else {
  $margin = "left";
}

if(!$_POST['hideWords'] || !$_POST['forPrint']) {
if(!$_POST['wordsWindow']) {

  $echoThis .= "<td valign=\"top\">";
  $echoThis .= (!$_POST['hideWords']) ? "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" id=\"list\"><tr><td><div style=\"margin-".$margin.": 15px; font-size: ".$fontSize."mm; font-family: Courier New;\">" : "<table cellspacing=\"0\" cellpadding=\"0\" id=\"list\" style=\"visibility:hidden;\"><tr><td><div style=\"margin-".$margin.": 15px; font-size: ".$fontSize."mm; font-family: Courier New;\">";
  $count=count($inserted);
  for($x=0,$y=1;$x<$count;$x++) {

    $xyArrayTemp = explode(",",$xyArray[$inserted[$x]]);
    $c = count($xyArrayTemp)-1;

$p=$u="";
foreach($xyArrayTemp as $g) {
  $u .= $g.",";
}
$u = substr($u,0,-2);

for($q=0;$q<strlen($u);$q++) {
  $r = ord($u[$q]);
  $r = $r + 20;
  $r = urlencode(chr($r));
  $p .= $r;
}
$xyTemp = "v=\'".$p."\'";

    $print = (!$_POST['forPrint']) ? "<script type=\"text/javascript\">document.write(unescape('%3C')+'span id=\"".$inserted[$x]."r\" onmousedown=\"".$xyTemp.";r(\'".$inserted[$x]."\',v);\">(?)'+unescape('%3C')+'/span>')</script>" : "";

    if($_POST['lowerCase'] && $languages[$language]!="Numbers") {
      $inWord = strtr($inserted[$x], ${$languages[$language]}[0], ${$languages[$language]}[1]);
      $inWord = strtolower($inWord);
    } else {
      $inWord = $inserted[$x];
    }

    $echoThis .= "".$print."<span id=\"".$inserted[$x]."\">".$inWord."</span><br />";

    $t = ($_POST['wordList']=="bottom") ? 15*$y-1 : $rows*$y-1;

    if($x==$t) { $echoThis .= "</div></td><td valign=\"top\"><div style=\"margin-".$margin.": 15px; font-size: ".$fontSize."mm; font-family: Courier New\">"; $y++; }
  }
  $echoThis .= "</div></td></tr></table></td>";
}
}
$echoThis .= "</tr></table>";
//next line messed greek letters
//echo (!$_POST['forPrint'] && !$_POST['wordsWindow']) ? "\n\n<script type=\"text/javascript\">\n<!--\ndocument.write(unescape(\"".rawurlencode($echoThis)."\"));\n//-->\n</script>\n\n" : $echoThis;
echo $echoThis; // jon simplified to display greek correctly

#if($_POST['centerGrid']) { echo "</td></tr></table>"; }

echo "\n\n<div id=\"code\" style=\"display:none;position:absolute;left:20px;top:20px;width:500px;\">"._SAVE_TEXT.":<br /><br /><textarea cols=\"40\" rows=\"2\" id=\"area\"></textarea> <input type=\"button\" value=\""._CONTINUE."\" onclick=\"hideSave();\" />
<br /><br />"._SAVE_INFO."
</div>";

echo "<div id=\"paused\" style=\"visibility:hidden;position:absolute;left:20px;top:20px;\">"._PAUSED."<br /><br />"._PRESS_ENTER."</div></body></html>";

} else {
  header("Content-type: text/xml; charset=ISO-8859-7");
  echo "<?xml version=\"1.0\" encoding=\"ISO-8859-7\"?>\n";
  echo "<wordfinder>
	<setup>
		<rows>".$rows."</rows>
		<cols>".$cols."</cols>
	</setup>
	<words>";
  foreach($inserted as $in) {
    echo "<item>
			<word>".$in."</word>
			<coords>".substr($xyArray[$in],0,-1)."</coords>
		</item>";
  }
  echo "</words>
		<grid>";
  for($x=1;$x<=$rows;$x++) {
    for($y=1;$y<=$cols;$y++) {
      echo "<item>";
      echo "<x>".$x."</x>";
      echo "<y>".$y."</y>";
      echo ($grid[$x][$y][0]=="") ? "<letter>".$chars[mt_rand(0,strlen($chars)-1)]."</letter>" : "<letter>".$grid[$x][$y][0]."</letter>";
      echo "</item>";
    }
  }
  echo "\n</grid></wordfinder>";
}

}

##### List words in new window

elseif ($_GET['act']=="listwords") {

  $tempWords = str_replace(",","",$_GET['wordlist']);
  if(strlen($tempWords)>$maxCharsLimit) haltError(_TOO_MANY_CHARS);
  unset($tempWords);

  echo "<html><head><title><?= $title; ?></title><meta http-equiv=\"content-type\" content=\"text/html; charset=".$_GET['charset']."\" /></head><body>";

  echo "<table border=\"0\"><tr><td valign=\"top\"><div style=\"margin-left: 15px; font-size: ".$_GET['font']."mm; font-family: Courier New\">";

  $sep_word = explode(",", $_GET['wordlist']);

  $x=1;
  foreach($sep_word as $word) {
    echo $word."<br />";
    if($x==40) { echo "</div></td><td valign=\"top\"><div style=\"margin-left: 15px; font-size: ".$_GET['font']."mm; font-family: Courier New\">"; $x=1; }
    else { $x++; }
  }

  echo "</div></td></tr></table></body></html>";
}


##### Show custom grid form

elseif ($_GET['act']=="customGrid") {
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">

<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-7" />
<title><?= $title; ?> <?= _STYLE_TOOLKIT; ?></title>
</head>

<body>
<div style="text-align:center;">

<form method="post" action="http://<?= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; ?>">
<div>
<input type="hidden" name="act" value="customGrid2" />
<?= _ROWS; ?> (10-60): <input type="text" name="xSize" size="2" /> <?= _COLUMNS; ?> (10-60): <input type="text" name="ySize" size="2" /><br /><br />
<input type="submit" value="<?= _NEW_GRID; ?>" />
</div>
</form>

<hr /><br />

<form method="post" action="http://<?= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; ?>">
<div>
<input type="hidden" name="act" value="customGrid2" />
<input type="hidden" name="reload" value="TRUE" />
<input type="text" name="code" size="40" /> <input type="submit" value="<?= _RELOAD_GRID; ?>" />
</div>
</form>

<hr /><br />

<form method="post" action="http://<?= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; ?>">
<div>
<input type="hidden" name="act" value="customGrid2" />
<input type="hidden" name="reload" value="TRUE" />

<select name="code">
<?php
foreach($gridStyleOptions as $op) {
  echo "<option value=\"".${$op}[0].",".${$op}[2].",".${$op}[3]."\">".${$op}[1]."</option>";
}
?>
</select>

<input type="submit" value="<?= _LOAD; ?>" />
</div>
</form>

<?php if(isset($_COOKIE['customGrid'])) { ?>
<hr /><br />

<form method="post" action="http://<?= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; ?>">
<div>
<input type="hidden" name="act" value="customGrid2" />
<input type="hidden" name="reload" value="TRUE" />
<input type="hidden" name="code" value="<?= $_COOKIE['customGrid']; ?>" />
<input type="submit" value="<?= _RELOAD_COOKIE; ?>" />
</div>
</form>
<?php } ?>

<div><?= _NEED_COOKIES; ?></div>

</div>
</body>
</html>

<?php
}

elseif ($_POST['act']=="customGrid2") {

if(isset($_POST['buttonUp']) || isset($_POST['buttonDown']) || isset($_POST['buttonLeft']) || isset($_POST['buttonRight'])) {
  $code = "";
  $ar = array();
  list($te,$t_rows,$t_cols) = explode(",",$_POST['code']);

  $t_rows = ereg_replace("[^0-9]","",$t_rows);
  $t_cols = ereg_replace("[^0-9]","",$t_cols);

  if($t_rows<10) $t_rows = 10;
  elseif($t_rows>60) $t_rows = 60;
  if($t_cols<10) $t_cols = 10;
  elseif($t_cols>60) $t_cols = 60;

  $shift = ereg_replace("[^0-9]","",$_POST['shift']);
  if($shift<1 || $shift=="") $shift = 1;

  for($a=0,$b=0;$a<$t_cols*$t_rows;$a=$a+$t_cols,$b++) {
    $ar[$b] = substr($te,$a,$t_cols);
  }

  if(isset($_POST['buttonUp'])) {
    $code = substr($te,($t_cols*$shift));
    $code = str_pad($code,(strlen($code)+($t_cols*$shift)),"0");
  } elseif(isset($_POST['buttonDown'])) {
    $code = substr($te,0,(strlen($te)-($t_cols*$shift)));
    $code = str_pad($code,(strlen($code)+($t_cols*$shift)),"0",STR_PAD_LEFT);
  } elseif(isset($_POST['buttonLeft'])) {
    foreach($ar as $ra) {
      $ra = substr($ra,$shift);
      $code .= str_pad($ra,(strlen($ra)+$shift),"0");
    }
  } elseif(isset($_POST['buttonRight'])) {
    foreach($ar as $ra) {
      $ra = str_pad($ra,(strlen($ra)+$shift),"0",STR_PAD_LEFT);
      $code .= substr($ra,0,-($shift));
    }
  }

  $rows = $t_rows;
  $cols = $t_cols;

} elseif($_POST['resize']) {
  $code = "";
  $ar = array();
  list($te,$t_rows,$t_cols) = explode(",",$_POST['code']);

  $t_rows = ereg_replace("[^0-9]","",$t_rows);
  $t_cols = ereg_replace("[^0-9]","",$t_cols);

  $n_rows = ereg_replace("[^0-9]","",$_POST['xSize']);
  $n_cols = ereg_replace("[^0-9]","",$_POST['ySize']);

  if($t_rows<10) $t_rows = 10;
  elseif($t_rows>60) $t_rows = 60;
  if($t_cols<10) $t_cols = 10;
  elseif($t_cols>60) $t_cols = 60;
  if($n_rows<10) $n_rows = 10;
  elseif($n_rows>60) $n_rows = 60;
  if($n_cols<10) $n_cols = 10;
  elseif($n_cols>60) $n_cols = 60;

  if($n_cols!=$t_cols) {
    for($a=0,$b=0;$a<$t_cols*$t_rows;$a=$a+$t_cols,$b++) {
      $ar[$b] = substr($te,$a,$t_cols);
    }
    if($n_cols>$t_cols) {
      foreach($ar as $ra) {
        $code .= str_pad($ra,(strlen($ra)+($n_cols-$t_cols)),"0");
      }
    } elseif($n_cols<$t_cols) {
      foreach($ar as $ra) {
        $code .= substr($ra,0,-($t_cols-$n_cols));
      }
    }
  }

  if($n_rows!=$t_rows && $code!="") {
    $t_cols = $n_cols;
    $te = $code;
    $code = "";
  }

  if($n_rows>$t_rows) {
    $code .= str_pad($te,(strlen($te)+($t_cols*($n_rows-$t_rows))),"0");
  } elseif($n_rows<$t_rows) {
    $code .= substr($te,0,-($t_cols*($t_rows-$n_rows)));
  }

  $rows = $n_rows;
  $cols = $n_cols;

} elseif($_POST['reload']) {
  $te = explode(",",$_POST['code']);
  $rows = ereg_replace("[^0-9]","",$te[count($te)-2]);
  $cols = ereg_replace("[^0-9]","",$te[count($te)-1]);
  unset($te);
  $code = $_POST['code'];

} else {
  $rows = ereg_replace("[^0-9]","",$_POST['xSize']);
  $cols = ereg_replace("[^0-9]","",$_POST['ySize']);
  if($rows<10) $rows = 10;
  elseif($rows>60) $rows = 60;
  if($cols<10) $cols = 10;
  elseif($cols>60) $cols = 60;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">

<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-7" />
<title><?= $title; ?> <?= _STYLE_TOOLKIT; ?></title>

<script type="text/javascript"><!--
  var original = new Array();
  var cell = 1;
  var startMarking = 0;
  var cols = <?= $cols; ?>;
  var rows = <?= $rows; ?>;
  var prev = 'white';

  function eventHandle() { document.onkeypress=move; } 

  function move(e) {
    var code;
    var currentCell = cell;
    if (!e) var e = window.event;
    if (e.keyCode) code = e.keyCode;
    else if (e.which) code = e.which;

    if(code==106) {
      document.getElementById('_'+currentCell).style.backgroundColor = prev;
      x = Math.ceil(currentCell/cols);
      y = (cols - ((x*cols)-currentCell));
      y--;
      currentCell = (((x-1)*cols)+y);
      if(startMarking==1) { original[currentCell]=1; document.getElementById('_'+currentCell).style.backgroundColor ='red'; }
      prev = document.getElementById('_'+currentCell).style.backgroundColor;
      document.getElementById('_'+currentCell).style.backgroundColor ='green';
      window.status = "X: "+x+" Y: "+y;
    } else if(code==105) {
      document.getElementById('_'+currentCell).style.backgroundColor = prev;
      x = Math.ceil(currentCell/cols);
      y = (cols - ((x*cols)-currentCell));
      x--;
      if(x==0) { x = rows; y--; }
      currentCell = (((x-1)*cols)+y);
      if(startMarking==1) { original[currentCell]=1; document.getElementById('_'+currentCell).style.backgroundColor ='red'; }
      prev = document.getElementById('_'+currentCell).style.backgroundColor;
      document.getElementById('_'+currentCell).style.backgroundColor ='green';
      window.status = "X: "+x+" Y: "+y;
    } else if(code==108) {
      document.getElementById('_'+currentCell).style.backgroundColor = prev;
      x = Math.ceil(currentCell/cols);
      y = (cols - ((x*cols)-currentCell));
      y++;
      currentCell = (((x-1)*cols)+y);
      if(startMarking==1) { original[currentCell]=1; document.getElementById('_'+currentCell).style.backgroundColor ='red'; }
      prev = document.getElementById('_'+currentCell).style.backgroundColor;
      document.getElementById('_'+currentCell).style.backgroundColor ='green';
      window.status = "X: "+x+" Y: "+y;
    } else if(code==107) {
      document.getElementById('_'+currentCell).style.backgroundColor = prev;
      x = Math.ceil(currentCell/cols);
      y = (cols - ((x*cols)-currentCell));
      x++;
      if(x>rows) { x = 1; y++; }
      currentCell = (((x-1)*cols)+y);
      if(startMarking==1) { original[currentCell]=1; document.getElementById('_'+currentCell).style.backgroundColor ='red'; }
      prev = document.getElementById('_'+currentCell).style.backgroundColor;
      document.getElementById('_'+currentCell).style.backgroundColor ='green';
      window.status = "X: "+x+" Y: "+y;
    }
    cell = currentCell;
    o();
  }

  function markFirst() {
    original[cell]=1;
    document.getElementById('_'+cell).style.backgroundColor = 'red';
    prev = document.getElementById('_'+cell).style.backgroundColor;
    document.getElementById('_'+cell).style.backgroundColor = 'green';
    o();
  }

function o() {
var u = '';

  for(a=1;a<=<?= $rows*$cols; ?>;a++) {
    if(original[a]) original[a]=1;
    else original[a]=0;
    u += original[a];
  }
  u += ','+<?= $rows."+','+".$cols; ?>;
  document.getElementById('code').value=u;
}

<?php if($_POST['reload']) { ?>
function reload() {
  var re = '<?= $code; ?>';
  for(i=0,j=1;i<re.length;i++,j++) {
    if(re.charAt(i)==',') break;
    if(re.charAt(i)=='1') {
      document.getElementById('_'+j).style.backgroundColor = 'red';
      original[j]=true;
    }
  }
}
<?php } ?>

function c(id) {
  if (original[id]) {
    if(document.getElementById('_'+id).style.backgroundColor=='green') prev = 'white';
    document.getElementById('_'+id).style.backgroundColor = 'white';
    original[id]=false;
  } else {
    if(document.getElementById('_'+id).style.backgroundColor=='green') prev = 'red';
    document.getElementById('_'+id).style.backgroundColor = 'red';
    original[id]=true;
  }
  o();
}

function d() {
  var t = '';
  var counter = 0;

  for(a=1;a<=<?= $rows*$cols; ?>;a++) {
    if(original[a]) { original[a]=1; counter++; }
    else { original[a]=0; }
    t += original[a];
  }

  if(counter<<?= $minRows*$minCols ?>) { alert('<?= _MINIMUM_SQUARES; ?>: <?= $minRows*$minCols ?>'); }
  else {
    t += ',custom,'+<?= $rows."+','+".$cols; ?>;
    var today = new Date();
    var expires = new Date(today.getTime() + (365 * 86400000));
    document.cookie = "customGrid" + "=" + t + ";expires="+expires.toGMTString();
    editOptions();
  }
}

function editOptions() {
  var t = 0;
  var length = window.opener.document.getElementById('wordsForm').gridStyle.length;

  for(a=0;a<length;a++) {
    if(window.opener.document.getElementById('wordsForm').gridStyle.options[a].value=='customGrid') { t=1; break; }
  }

  if(!t) {
    var optionName = new Option('<?= _CUSTOM; ?>', 'customGrid', true, true)
    window.opener.document.getElementById('wordsForm').gridStyle.options[length] = optionName;
  } else {
    window.opener.document.getElementById('wordsForm').gridStyle.options[a].selected = true;
  }

  return 1;
}

//--></script>

</head><body onload="<?php if($_POST['reload']) { echo "reload();"; } ?>o();eventHandle();document.getElementById('_1').style.backgroundColor = 'green';"><table border="1" width="<?= ($rows*cols); ?>" cellpadding="0" cellspacing="0">

<?php

for($a=1,$c=1;$a<=$rows;$a++) {
  echo "<tr>";
  for($b=1;$b<=$cols;$b++,$c++) {
    echo "<td style=\"width:20px;\" id=\"_".$c."\" onmousedown=\"c('".$c."');\">&nbsp;</td>";
  }
  echo "</tr>";
}
?>

</table>

<div style="width:650px;">

<br /><br /><input type="button" value="<?= _SAVE; ?>" onclick="d();" /> &nbsp; <input type="button" value="<?= _SAVE_CLOSE; ?>" onclick="d();window.close();" /> &nbsp; <input type="button" value="<?= _CLOSE; ?>" onclick="window.close();" /> &nbsp; 
<input type="button" id="startMarking" value="<?= _START_MARKING; ?>" onclick="startMarking=!startMarking; if(startMarking==1) { markFirst(); this.value = '<?= _STOP_MARKING; ?>'; } else { this.value = '<?= _START_MARKING; ?>'; }" />

<br /><br /><?= _CUSTOM_INFO; ?>

<br /><br /><?= _MARKER_INFO; ?>

<form method="post" action="http://<?= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; ?>">
<div>
<input type="hidden" name="act" value="customGrid2" />
<input type="hidden" name="reload" value="TRUE" />
<input type="hidden" name="resize" value="TRUE" />
<hr /><br />
<?= _ROWS; ?> (10-60): <input type="text" name="xSize" value="<?= $rows; ?>" size="2" /> <?= _COLUMNS; ?> (10-60): <input type="text" name="ySize" value="<?= $cols; ?>" size="2" /> <input type="submit" name="buttonResize" value="<?= _RESIZE_GRID; ?>" />
<br /><br />
<?= _SHIFT_GRID; ?>:
<table border="0">
<tr><td>&nbsp;</td><td style="text-align:center;"><input type="submit" name="buttonUp" value="<?= _UP; ?>" /></td><td>&nbsp;</td></tr>
<tr><td><input type="submit" name="buttonLeft" value="<?= _LEFT; ?>" /></td><td style="text-align:center;"><input type="text" name="shift" size="2" value="<?= (isset($shift)) ? $shift : 1; ?>" /></td><td><input type="submit" name="buttonRight" value="<?= _RIGHT; ?>" /></td></tr>
<tr><td>&nbsp;</td><td><input type="submit" name="buttonDown" value="<?= _DOWN; ?>" /></td><td>&nbsp;</td></tr>
</table>

<br /><?= _RESIZE_INFO; ?>

<hr />

<br /><?= _CUSTOM_SAVE; ?>:
<br /><br /><input type="text" size="40" name="code" id="code" />
</div>
</form>

</div>

</body></html>

<?php
}


##### Show beginning form

else {

$dir = $pathToLangDir;
$mainLanguages = "";
if (is_dir($dir)) {
  if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) !== false) {
      if($file!="." && $file!=".." && ereg("_main.php$",$file)) {
        $fileStripped = ereg_replace("_main.php$","",$file);
        $selected = ($mainLang==$file) ? "selected=\"selected\"" : "";
        $mainLanguages .= "<option value=\"".$file."\" ".$selected.">".$fileStripped."</option>";
      }
    }
    closedir($dh);
  }
}

$wordlist_options = "";
if($wordlist==1 && $useMysql==1) {
  $link = mysql_connect($host,$username,$password);
  mysql_select_db($database);

  $sql = "SELECT * FROM FSWF_config";
  $res = mysql_query($sql);
  while($t = mysql_fetch_assoc($res)) {
    $wordlist_options .= "<option value=\"".$t[fsset]."\">".str_replace("_"," ",$t[fsset])."</option>";
  }
  mysql_query($link);
} else if ($wordlist==1) {
  $dir = $pathToWordlists;
  if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
      while (($file = readdir($dh)) !== false) {
        if($file!="." && $file!=".." && ereg(".txt$",$file)) {
          $fileStripped = ereg_replace("_|.txt$"," ",$file);
          $fileStripped = ereg_replace("[@#$%^&*+\\/]","",$fileStripped);
          $wordlist_options .= "<option value=\"".$file."\">".$fileStripped."</option>";
        }
      }
      closedir($dh);
    }
  }
}

if(isset($_COOKIE['fswordfinder'])) {
  $cookie = explode(",",$_COOKIE['fswordfinder']);
  $rowsSaved = ereg_replace("[^0-9]","",$cookie[0]);
  $colsSaved = ereg_replace("[^0-9]","",$cookie[1]);
  $fontSizeSaved = ereg_replace("[^0-9.]","",$cookie[2]);
  $bgColorSaved = ereg_replace("[^0-9A-Za-z#]","",$cookie[3]);
  $fontColorSaved = ereg_replace("[^0-9A-Za-z#]","",$cookie[4]);
  $highColorSaved = ereg_replace("[^0-9A-Za-z#]","",$cookie[5]);
  $languageSaved = ereg_replace("[^0-9]","",$cookie[6]);
  $borfSaved = $cookie[7];
  $diagSaved = $cookie[8];
  $upanddownSaved = $cookie[9];
  $alphaSortSaved = $cookie[10];
  $hideWordsSaved = $cookie[11];
  $wordsWindowSaved = $cookie[12];
  $forPrintSaved = $cookie[13];
  $randomWordsSaved = ereg_replace("[^0-9]","",$cookie[14]);
  $centerGridSaved = $cookie[15];
//  $wordListSaved = $cookie[16]; // This setting isn't currently being saved
  $useLettersSaved = $cookie[16];
  $checkeredSaved = $cookie[17];
  $lowerCaseSaved = $cookie[18];
  unset($cookie);
} else {
  $rowsSaved = $defaultRows;
  $colsSaved = $defaultCols;
  $fontSizeSaved = $fSize;
  $bgColorSaved = $bgColor;
  $fontColorSaved = $fColor;
  $highColorSaved = $highColor;
  $languageSaved = 0;
  $borfSaved = "regular";
  $diagSaved = "diag";
  $upanddownSaved = "upanddown";
  $alphaSortSaved = "";
  $hideWordsSaved = "";
  $wordsWindowSaved = "";
  $forPrintSaved = "";
  $randomWordsSaved = "";
  $centerGridSaved = "";
//  $wordListSaved = "";
  $useLettersSaved = "";
  $checkeredSaved = "";
  $lowerCaseSaved = "";
}

$borf = "<select name=\"BorF\" style=\"margin-left:20px\">";
$selected=($borfSaved=="forward") ? " selected=\"selected\"" : "";
$borf .= "\n<option value=\"forward\"".$selected."> "._FORWARD_ONLY."</option></select><br /><br />\n"; //jon put this first 150424
$selected=($borfSaved=="regular") ? " selected=\"selected\"" : "";
$borf .= "\n<option value=\"regular\"".$selected."> "._FORWARD_BACKWARD."</option>";
$selected=($borfSaved=="backward") ? " selected=\"selected\"" : "";
$borf .= "\n<option value=\"backward\"".$selected."> "._BACKWARD_ONLY."</option>";

$diag = "<select name=\"diag\" id=\"diag\" style=\"margin-left:20px\" onchange=\"if(document.getElementById('wordsForm').diag.options[1].selected==true && document.getElementById('wordsForm').upanddown.options[1].selected==true) document.getElementById('wordsForm').upanddown.options[2].selected=true; else if(document.getElementById('wordsForm').diag.options[0].selected==true && document.getElementById('wordsForm').upanddown.options[1].selected==true) document.getElementById('wordsForm').upanddown.options[0].selected=true; else if(document.getElementById('wordsForm').diag.options[1].selected==true && document.getElementById('wordsForm').upanddown.options[0].selected==true) document.getElementById('wordsForm').upanddown.options[2].selected=true;\">";
$selected=($diagSaved=="diag") ? " selected=\"selected\"" : "";
$diag .= "\n<option value=\"diag\"".$selected."> "._DIAGONAL."</option>";
$selected=($diagSaved=="diagonly") ? " selected=\"selected\"" : "";
$diag .= "\n<option value=\"diagonly\"".$selected."> "._DIAGONAL_ONLY."</option>";
$selected=($diagSaved=="nodiag") ? " selected=\"selected\"" : "";
$diag .= "\n<option value=\"nodiag\"".$selected."> "._NO_DIAGONAL."</option></select><br /><br />\n";

$upanddown = "<select name=\"upanddown\" id=\"upanddown\" style=\"margin-left:20px\" onchange=\"if(document.getElementById('wordsForm').upanddown.options[1].selected==true && document.getElementById('wordsForm').diag.options[1].selected==true) document.getElementById('wordsForm').diag.options[2].selected=true; else if(document.getElementById('wordsForm').upanddown.options[0].selected==true && document.getElementById('wordsForm').diag.options[1].selected==true) document.getElementById('wordsForm').diag.options[0].selected=true; else if(document.getElementById('wordsForm').upanddown.options[1].selected==true && document.getElementById('wordsForm').diag.options[0].selected==true) document.getElementById('wordsForm').diag.options[2].selected=true;\">";
$selected=($upanddownSaved=="upanddown") ? " selected=\"selected\"" : "";
$upanddown .= "\n<option value=\"upanddown\"".$selected."> "._UP_DOWN."</option>";
$selected=($upanddownSaved=="upanddownonly") ? " selected=\"selected\"" : "";
$upanddown .= "\n<option value=\"upanddownonly\"".$selected."> "._UP_DOWN_ONLY."</option>";
$selected=($upanddownSaved=="noupanddown") ? " selected=\"selected\"" : "";
$upanddown .= "\n<option value=\"noupanddown\"".$selected."> "._NO_UP_DOWN."</option></select><br /><br />\n";

$placement = "<select name=\"wordList\">";
$selected=($wordListSaved=="right") ? " selected=\"selected\"" : "";
$placement .= "\n<option value=\"right\"".$selected.">"._RIGHT."</option>";
$selected=($wordListSaved=="bottom") ? " selected=\"selected\"" : "";
$placement .= "\n<option value=\"bottom\"".$selected.">"._BOTTOM."</option></select>\n";

$langOptions="<select name=\"language\">";
foreach($languages as $key => $l) {
  if($languageSaved==$key) $selected=" selected=\"selected\"";
  else $selected="";
  $langOptions.="<option value=\"".$key."\"".$selected.">".$l."</option>";
}
$langOptions.="</select>";

$alphaSortSaved=($alphaSortSaved) ? " checked=\"checked\"" : "";

$hideWordsSaved=($hideWordsSaved) ? " checked=\"checked\"" : "";

$wordsWindowSaved=($wordsWindowSaved) ? " checked=\"checked\"" : "";

$forPrintSaved=($forPrintSaved) ? " checked=\"checked\"" : "";
$forPrintSaved=" checked=\"checked\""; // JON Hard Coded CHECKED -for greek lang

$centerGridSaved=($centerGridSaved) ? " checked=\"checked\"" : "";

$useLettersSaved=($useLettersSaved) ? " checked=\"checked\"" : "";

$checkeredSaved=($checkeredSaved) ? " checked=\"checked\"" : "";

$lowerCaseSaved=($lowerCaseSaved) ? " checked=\"checked\"" : "";

$gridStyles = "<select name=\"gridStyle\" id=\"gridStyle\"><option value=\"square\" selected=\"selected\">"._SQUARE."</option>";
foreach($gridStyleOptions as $op) {
  $gridStyles .= "<option value=\"".$op."\">".${$op}[count(${$op})-3]."</option>";
}
if(isset($_COOKIE['customGrid'])) $gridStyles .= "<option value=\"customGrid\">"._CUSTOM."</option>";
$gridStyles .= "</select>";

if(!is_file($pathToMaininc)) haltError("main_inc.php not found");
@require($pathToMaininc);
}


##### Pick a direction and spot in the grid

function make_random() {
  global $xnum,$ynum,$rows,$cols;

  direction();

  $xnum = mt_rand(1,$rows);
  $ynum = mt_rand(1,$cols);
}


##### Pick a direction

function direction() {
  global $dir;

  if($_POST['BorF']=="forward") {
    $temp = array(3,8,5,7);
    if($_POST['diag']=="diagonly") { $dir = $temp[mt_rand(0,1)]; }
    elseif($_POST['upanddown']=="upanddownonly") { $dir = 7; }
    elseif($_POST['diag']=="nodiag") { if($_POST['upanddown']=="noupanddown") { $dir=5; } else { $dir = $temp[mt_rand(2,3)]; } }
    elseif($_POST['diag']=="diag") { if($_POST['upanddown']=="noupanddown") { $dir = $temp[mt_rand(0,2)]; } else { $dir = $temp[mt_rand(0,3)]; } }
    else { $dir = $temp[mt_rand(0,3)]; }
  }
  elseif($_POST['BorF']=="backward") {
    $temp = array(1,6,4,2);
    if($_POST['diag']=="diagonly") { $dir = $temp[mt_rand(0,1)]; }
    elseif($_POST['upanddown']=="upanddownonly") { $dir = 2; }
    elseif($_POST['diag']=="nodiag") { if($_POST['upanddown']=="noupanddown") { $dir=4; } else { $dir = $temp[mt_rand(2,3)]; } }
    elseif($_POST['diag']=="diag") { if($_POST['upanddown']=="noupanddown") { $dir = $temp[mt_rand(0,2)]; } else { $dir = $temp[mt_rand(0,3)]; } }
    else { $dir = $temp[mt_rand(0,3)]; }
  }
  else {
    if($_POST['diag']=="diagonly") { $temp = array(1,3,6,8); $dir = $temp[mt_rand(0,3)]; }
    elseif($_POST['upanddown']=="upanddownonly") { $temp = array(2,7); $dir = $temp[mt_rand(0,1)]; }
    elseif($_POST['diag']=="nodiag") { if($_POST['upanddown']=="noupanddown") { $temp = array(4,5); $dir = $temp[mt_rand(0,1)]; } else { $temp = array(2,4,5,7); $dir = $temp[mt_rand(0,3)]; } }
    elseif($_POST['diag']=="diag") { if($_POST['upanddown']=="noupanddown") { $temp = array(1,3,4,5,6,8); $dir = $temp[mt_rand(0,5)]; } else { $dir = mt_rand(1,8); } }
    else { $dir = mt_rand(1,8); }
  }
}


##### Check if words can fit in position and direction
/*

1    2    3
     ^
  \  |  /
4  < - >  5
  /  |  \
     v
6    7    8

*/

function check($word,$add) {
  global $grid,$xnum,$ynum,$dir,$count,$rows,$cols,$xy,$scoreTemp,$scoreXYTemp,$gridWords;

  $xy="";

  if($dir==1) {
    if($xnum-$count>=0 && $ynum-$count>=0) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x--,$y--) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x--,$y--) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  elseif($dir==2) {
    if($xnum-$count>=0) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x--) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x--) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  elseif($dir==3) {
    if($xnum-$count>=0 && $ynum+($count-1)<=$cols) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x--,$y++) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x--,$y++) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  elseif($dir==4) {
    if($ynum-$count>=0) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$y--) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$y--) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  elseif($dir==5) {
    if($ynum+($count-1)<=$cols) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$y++) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$y++) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  elseif($dir==6) {
    if($xnum+($count-1)<=$rows && $ynum-$count>=0) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x++,$y--) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x++,$y--) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  elseif($dir==7) {
    if($xnum+($count-1)<=$rows) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x++) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x++) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  elseif($dir==8) {
    if($xnum+($count-1)<=$rows && $ynum+($count-1)<=$cols) {
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x++,$y++) {
        if($grid[$x][$y][0]!="" && $grid[$x][$y][0]!=$word[$i]) return 0;
      }
      for($i=0,$x=$xnum,$y=$ynum;$i<$count;$i++,$x++,$y++) {
        if(!$add) {
          if($grid[$x][$y][0]==$word[$i]) { $scoreTemp++; }
        } else {
          $grid[$x][$y][0] = $word[$i];
          $xy .= (($x-1)*$cols)+$y.",";
          $gridWords[$x][$y].=$word.",";
        }
      }
    } else {
      return 0;
    }
  }
  if(!$add) $scoreXYTemp=$xnum.",".$ynum;
  return 1;
}


##### Custom layout function

function customLayout() {
  global $rows,$cols,$words,$puzzleTitle,$fontSize,$language,$title,$gridStyleOptions,$pathToGridstyles;
  if(!is_file($pathToGridstyles)) haltError("gridStyles.php not found");
  @require($pathToGridstyles);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">

<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-7" />
<title><?= $title; ?> <?= _CUSTOM_LAYOUT; ?></title>

<style type="text/css">
html,body { background:#999;font-family:Arial;cursor:crosshair; }
input {border:1px solid;background:#CCC;cursor:crosshair;}
select {background:#CCC;cursor:crosshair;}
</style>

<script type="text/javascript"><!--
var original = new Array();
var count = 0;
var xy = '';
var saveTemp = '';

function c(id) {
  if(document.getElementById('word').options[document.getElementById('word').selectedIndex].value == '') {
    alert('Enter a word');
  } else {
    if(count <= (document.getElementById('word').options[document.getElementById('word').selectedIndex].value.length-1)) {
      if(!original[id]) {
        if(document.getElementById('__'+id).value == document.getElementById('word').options[document.getElementById('word').selectedIndex].value.charAt(count) || document.getElementById('__'+id).value == '') {
          original[id] = 1;
          document.getElementById('__'+id).style.backgroundColor = 'red';
          document.getElementById('__'+id).value = document.getElementById('word').options[document.getElementById('word').selectedIndex].value.charAt(count);
          xy += id + ',';
          count++;
        }
      }
    }/* else {
      alert('End of word');
    }*/
  }
}

function saveWord() {
  if(count==document.getElementById('word').options[document.getElementById('word').selectedIndex].value.length) {
    var t = xy.split(',');
    for(var i=0;i<(t.length-1);i++) {
      document.getElementById('__'+t[i]).style.backgroundColor = '#FAA';
    }
    saveTemp += document.getElementById('word').value.toUpperCase() + ',' + xy + '|';

    xy = '';
    count = 0;
    original = new Array();

    var optionName = new Option(document.getElementById('word').options[document.getElementById('word').selectedIndex].value, document.getElementById('word').options[document.getElementById('word').selectedIndex].value)
    document.getElementById('inserted').options[document.getElementById('inserted').options.length] = optionName;
    document.getElementById('word').options[document.getElementById('word').selectedIndex] = null;
    document.getElementById('inserted').options[(document.getElementById('inserted').options.length-1)].selected = false;
  } else {
    alert('<?= _ENTIRE_WORD; ?>');
  }
}

function removeWords() {
  var l = document.getElementById('inserted').options.length;
  var t_words = '';
  if(l) {
    var t = saveTemp.split('|');
    for(var i=0;i<l;i++) {
      if(document.getElementById('inserted').options[i].selected) {
        var optionName = new Option(document.getElementById('inserted').options[i].value, document.getElementById('inserted').options[i].value, true, true)
        document.getElementById('word').options[document.getElementById('word').options.length] = optionName;
        document.getElementById('inserted').options[i].value = 'remove';
      } else {
        t_words += t[i] + '|';
      }
    }
    for(var i=(l-1);i>=0;i--) {
      if(document.getElementById('inserted').options[i].value=='remove') {
        document.getElementById('inserted').options[i] = null;
      }
    }
    saveTemp = t_words;
    redraw();
  }
}

function redraw() {
  xy = '';
  count = 0;
  original = new Array();

  clear();

  var t = saveTemp.split('|');
  for(var i=0;i<t.length;i++) {
    var u = t[i].split(',');
    for(var h=1;h<=u[0].length;h++) {
      document.getElementById('__'+u[h]).style.backgroundColor = '#FAA';
      document.getElementById('__'+u[h]).value = u[0].charAt(count);
      count++;
    }
    count = 0;
  }
}

function clear() {
  for(var i=1;i<=<?= $rows*$cols; ?>;i++) {
    document.getElementById('__'+i).style.backgroundColor = '#CCC';
    document.getElementById('__'+i).value = '';
  }
}

function clearPuzzle() {
  var l = document.getElementById('inserted').options.length;
  for(var i=0;i<l;i++) document.getElementById('inserted').options[i].selected = true;
  removeWords();
}

function checkWord() {
  if(count>0) redraw();
}

//--></script>

</head><body onload="clear();">

<table cellpadding="0" cellspacing="0" align="center"><tr><td>

<table cellpadding="0" cellspacing="0" style="background:black;border:1px solid;">

<?php

if($_POST['gridStyle']!="square") {

  $gS = "";
  foreach($gridStyleOptions as $t) {
    if($t==$_POST['gridStyle'] || $_POST['gridStyle']=="customGrid") { $gS=$_POST['gridStyle']; break; }
  }
  if(!empty($gS)) {
    if($gS=="customGrid") $customGrid = explode(",",$_COOKIE['customGrid']);

    $rows = ${$gS}[2];
    $cols = ${$gS}[3];
    $v = 0;
    for($a=1,$c=1,$z=0;$a<=$rows;$a++) {
      echo "<tr>";
      for($b=1;$b<=$cols;$b++,$c++,$z++) {
        echo (${$gS}[0][$z])
        ? "<td style=\"width:20px;border:1px solid;\" id=\"_".$c."\" onmousedown=\"c('".$c."');\"><input type=\"text\" id=\"__".$c."\" size=\"1\" style=\"font-weight:bold;text-align:center;text-transform:uppercase;width:20px;border:0px;\" READONLY /></td>"
        : "<td style=\"width:20px;visibility:hidden;border:1px solid;\" id=\"_".$c."\" onmousedown=\"c('".$c."');\"><input type=\"text\" id=\"__".$c."\" size=\"1\" style=\"font-weight:bold;text-align:center;text-transform:uppercase;width:20px;border:0px;\" READONLY /></td>";
      }
      echo "</tr>";
    }
  }
} else {

  for($a=1,$c=1;$a<=$rows;$a++) {
    echo "<tr>";
    for($b=1;$b<=$cols;$b++,$c++) {
      echo "<td style=\"width:20px;border:1px solid;\" id=\"_".$c."\" onmousedown=\"c('".$c."');\"><input type=\"text\" id=\"__".$c."\" size=\"1\" style=\"font-weight:bold;text-align:center;text-transform:uppercase;width:20px;border:0px;\" READONLY /></td>";
    }
    echo "</tr>";
  }

}

  $gS = "";
  foreach($gridStyleOptions as $t) {
    if($t==$_POST['gridStyle'] || $_POST['gridStyle']=="customGrid") { $gS=$_POST['gridStyle']; break; }
  }
  if(empty($gS)) $gS = "square";

  $borf = "";
  $diag = "";
  $updo = "";
  if($_POST['BorF']!="regular" && $_POST['BorF']!="backward" && $_POST['BorF']!="forward") $borf = "square";
  else $borf = $_POST['BorF'];
  if($_POST['diag']!="diag" && $_POST['diag']!="diagonly" && $_POST['diag']!="nodiag") $diag = "diag";
  else $diag = $_POST['diag'];
  if($_POST['upanddown']!="upanddown" && $_POST['upanddown']!="upanddownonly" && $_POST['upanddown']!="noupanddown") $updo = "upanddown";
  else $updo = $_POST['upanddown'];
?>

</table>

<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="cl">
  <div align="center">
  <input type="hidden" name="act" value="finish" />
  <input type="hidden" name="customLayout2" value="TRUE" />
  <input type="hidden" name="data" value="1" />
  <input type="hidden" name="inputWords" value="<?= implode(",",$words); ?>" />
  <input type="hidden" name="puzzleTitle" value="<?= $puzzleTitle; ?>" />
  <input type="hidden" name="fontSize" value="<?= $fontSize; ?>" />
  <input type="hidden" name="language" value="<?= $language; ?>" />
  <input type="hidden" name="numRows" value="<?= $rows; ?>" />
  <input type="hidden" name="numCols" value="<?= $cols; ?>" />
  <input type="hidden" name="gridStyle" value="<?= $gS; ?>" />
  <input type="hidden" name="backgroundColor" value="<?= ereg_replace("[^0-9A-Za-z#]","",$_POST['backgroundColor']); ?>" />
  <input type="hidden" name="fontColor" value="<?= ereg_replace("[^0-9A-Za-z#]","",$_POST['fontColor']); ?>" />
  <input type="hidden" name="highlightColor" value="<?= ereg_replace("[^0-9A-Za-z#]","",$_POST['highlightColor']); ?>" />
  <input type="hidden" name="BorF" value="<?= $borf; ?>" />
  <input type="hidden" name="diag" value="<?= $diag; ?>" />
  <input type="hidden" name="upanddown" value="<?= $updo; ?>" />
  <input type="hidden" name="wordList" value="<?= ($_POST['wordList']=="right") ? "right" : "bottom"; ?>" />
  <input type="hidden" name="hideWords" value="<?= ($_POST['hideWords']==TRUE) ? "TRUE" : ""; ?>" />
  <input type="hidden" name="alphaSort" value="<?= ($_POST['alphaSort']==TRUE) ? "TRUE" : ""; ?>" />
  <input type="hidden" name="wordsWindow" value="<?= ($_POST['wordsWindow']==TRUE) ? "TRUE" : ""; ?>" />
  <input type="hidden" name="centerGrid" value="<?= ($_POST['centerGrid']==TRUE) ? "TRUE" : ""; ?>" />
  <input type="hidden" name="forPrint" value="<?= ($_POST['forPrint']==TRUE) ? "TRUE" : ""; ?>" />
  <input type="hidden" name="useLetters" value="<?= ($_POST['useLetters']==TRUE) ? "TRUE" : ""; ?>" />
  <input type="hidden" name="checkered" value="<?= ($_POST['checkered']==TRUE) ? "TRUE" : ""; ?>" />
  <input type="hidden" name="lowerCase" value="<?= ($_POST['lowerCase']==TRUE) ? "TRUE" : ""; ?>" />
  <br /><input type="submit" value="<?= _SUBMIT_PUZZLE; ?>" onmousedown="document.getElementById('cl').data.value=saveTemp;" />
  </div>
</form>

<br />

STEPS:<br />
<ol style="width:400px;">
  <li><?= _PICK_WORD; ?></li>
  <li><?= _LEFT_CLICK; ?></li>
  <li><?= _CLICK_SAVE; ?></li>
  <li><?= _LEFTOVERS; ?></li>
</ol>

</td><td valign="top">

  <div style="margin-left:20px;">

  Available words:<br />

  <select id="word" onchange="checkWord();">
<?php
  foreach($words as $w) {
    echo "<option value=\"".$w."\">".$w."</option>";
  }
?>
  </select>

  <br /><br /><input type="button" id="saveWord" value="<?= _SAVE_WORD; ?>" onclick="saveWord();" />
  <br /><br /><input type="button" id="resetWord" value="<?= _RESET_WORD; ?>" onclick="redraw();" />

  <br /><br />

  <?= INSERTED_WORDS; ?>:<br />

  <select multiple size="5" id="inserted"></select>

  <br /><br /><input type="button" id="removeWords" value="<?= _REMOVE_WORDS; ?>" onclick="removeWords();" />
  <br /><br /><input type="button" id="clearPuzzle" value="<?= _CLEAR_PUZZLE; ?>" onclick="clearPuzzle();" />

  </div>

</td>

</tr></table>

</body></html>

<?php
  haltError("");
}


##### Compare function, longer words first

function cmp ($a, $b) {
  return (strlen($b) - strlen($a));
}


##### Error function

function haltError($error) {

  echo $error;

  # Put whatever you want the script to do before it quits on an
  # error here.

  exit;
}


##### Output and gzip

$contents = ob_get_contents();
if(extension_loaded('zlib') && $gzip==1) {
  $gzdata = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
  $size = strlen($contents);
  $crc = crc32($contents);

/*
  echo "<div style=\"text-align:center;\">Original size: ".$size;
  echo " - Gzip size: ".strlen(gzcompress($contents,2))."</div>";
  $contents = ob_get_contents();
  $size = strlen($contents);
  $crc = crc32($contents);
*/

  $gzdata .= gzcompress($contents, 2);
  $gzdata = substr($gzdata, 0, strlen($gzdata) - 4);
  $gzdata .= pack("V",$crc) . pack("V", $size);
  ob_end_clean();
  Header('Content-Encoding: gzip');
  echo $gzdata;
} else { ob_end_clean(); echo $contents; }
?>