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

if($_SERVER['PHP_SELF']=="/main_inc.php") { die("You are not allowed to view this page by itself"); }
else {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">
<!-- FS.WordFinder 3.5.1 by Robert (http://fswordfinder.sourceforge.net) -->
<head>
<meta http-equiv="content-type" content="text/html; charset=<?= $charset; ?>" />
<title><?= $title; ?></title>

<style type="text/css">

html,body { height:100%;background:#999;font-family:Arial; }

input {padding:2px;border:1px solid;background:#CCC;}
textarea {padding:2px;border:1px solid;background:#CCC;}
select {background:#CCC;}

a { text-decoration:none;color:black; }

tr,td {
  border:1px solid black;
  padding:5px;
}

.tablist {
  border:1px solid black;
  border-bottom:0px;
  margin-left:5px;
  padding-left:5px;
  padding-right:5px;
}

.tabcontent {
  border:1px solid black;
  padding:5px;
  height:560px;
  background:#DDD;
}

.inputTitle {
  font-weight:bold;
  font-size:12px;
}

.info {
  font-size:12px;
}

  li {font-size:12px;font-weight:normal;margin-bottom:5px;}

</style>

<script type="text/javascript"><!--

var win=null;
function NewWindow(page,w,h){
  LeftPosition=(screen.width)?(screen.width-w)/2:100;
  TopPosition=(screen.height)?(screen.height-h)/2:100;
  win=window.open(page,null,'height=700,width=700,top='+TopPosition+',left='+LeftPosition+',status=no,toolbar=no,menubar=no,location=no,resizable=yes,directories=no,scrollbars=yes');
}

function saveSettings(object) {
  t = object.numRows.value+','+object.numCols.value+','+object.font.value+','+object.backgroundColor.value+','+object.fontColor.value+','+object.highlightColor.value+','+object.language.value+','+object.BorF.value+','+object.diag.value+','+object.upanddown.value+','+((object.alphaSort.checked==true) ? object.alphaSort.checked : '')+','+((object.hideWords.checked==true) ? object.hideWords.checked : '')+','+((object.wordsWindow.checked==true) ? object.wordsWindow.checked : '')+','+((object.forPrint.checked==true) ? object.forPrint.checked : '')+','+object.randomWords.value+','+((object.centerGrid.checked==true) ? object.centerGrid.checked : '')+','+((object.useLetters.checked==true) ? object.useLetters.checked : '')+','+((object.checkered.checked==true) ? object.checkered.checked : '')+','+((object.lowerCase.checked==true) ? object.lowerCase.checked : '');
  var today = new Date();
  var expires = new Date(today.getTime() + (365 * 86400000));
  document.cookie = "fswordfinder" + "=" + t + ";expires="+expires.toGMTString();
  alert('<?= _SETTINGS; ?>');
}

function changeLanguages() {
  var t = document.getElementById('mainLang').options[document.getElementById('mainLang').selectedIndex].value;
  var today = new Date();
  var expires = new Date(today.getTime() + (365 * 86400000));
  document.cookie = "mainLang" + "=" + t + ";expires="+expires.toGMTString();
  window.location.href=window.location.href
}

function externalLinks() {
 if (!document.getElementsByTagName) return;
 var anchors = document.getElementsByTagName("a");
 for (var i=0; i<anchors.length; i++) {
   var anchor = anchors[i];
   if (anchor.getAttribute("href") &&
       anchor.getAttribute("rel") == "external")
     anchor.target = "_blank";
 }
}
window.onload = externalLinks;

<?php if(ereg("MSIE",$_SERVER['HTTP_USER_AGENT'])) { ?>
function ieWarning(CONTROL) {
  if(CONTROL.checked) alert('<?= _IE_WARNING; ?>')
}
<?php } ?>

var prev = 'main';

function tab(id) {
  document.getElementById(prev).style.backgroundColor = '#BBB';
  document.getElementById(id).style.backgroundColor = '#DDD';

  document.getElementById('_'+prev).style.display = 'none';
  document.getElementById('_'+id).style.display = 'block';
  document.getElementById('_'+id).blur;
  prev = id;

  document.getElementById(id).blur();

  return false;
}

function checkForm() {
  if(document.getElementById('inputWords').value=='' && document.getElementById('dbWords').value=='') { alert('<?= _EMPTY_LIST; ?>'); return false; }
  return true;
}

//--></script>

</head>

<body>

<?= $direcho; ?>

<div style="margin-left:auto;margin-right:auto;text-align:left;width:400px;">

<div style="text-align:left;font-size:16px;font-family:Arial;font-weight:bold;float:left;"><?= $title; ?></div><div style="text-align:right;font-size:10px;"><select name="mainLang" id="mainLang" onchange="changeLanguages();" style="font-size:11px;"><?= $mainLanguages; ?></select></div><br />

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post" id="wordsForm" onsubmit="return checkForm(); return false;">
<div><input type="hidden" name="act" value="finish" />
<input type="hidden" name="more" value="no" /></div>

<div><a href="#" id="main" onclick="return tab(this.id);" class="tablist" style="background:#DDD"><?= _MAIN; ?></a> <a href="#" id="options" onclick="return tab(this.id);" class="tablist" style="background:#BBB"><?= _OPTIONS; ?></a> <a href="#" id="options2" onclick="return tab(this.id);" class="tablist" style="background:#BBB"><?= _OPTIONS2; ?></a> <a href="#" id="load" onclick="return tab(this.id);" class="tablist" style="background:#BBB"><?= _LOAD; ?></a> <a href="#" id="information" onclick="return tab(this.id);" class="tablist" style="background:#BBB"><?= _INFORMATION; ?></a></div>

<div id="_main" style="display:block" class="tabcontent">

  <div class="inputTitle"><?= _PUZZLE_TITLE; ?></div><div class="info">(<?= _OPTIONAL; ?>, <?= _MAX; ?> <?= $puzzleTitleSize; ?> <?= _LETTERS_NUMBERS; ?>)<br />
  <input type="text" name="puzzleTitle" size="35" maxlength="<?= $puzzleTitleSize; ?>" /></div><br />
  <div class="inputTitle"><?= _ENTER_WORDS; ?></div><div class="info">(<?= _SEPARATED; ?>)<br />
  <textarea cols="45" rows="15" name="inputWords" id="inputWords"></textarea></div>

<?php if($wordlist==1) {?>
      <p style="font-weight:bold;text-align:center;"><?= _OR; ?></p>
      <table cellpadding="0" cellspacing="0" style="border:0px;width:388px;">
        <tr>
          <td><span class="inputTitle"><?= _NUMBER_WORDS; ?></span><div class="info">(<?= $max_wordlist; ?> <?= _MAX; ?>.)</div></td>
          <td><input type="text" size="2" maxlength="3" name="dbWords" id="dbWords" onblur="if(this.value><?= $max_wordlist; ?>)this.value=<?= $max_wordlist; ?>;" /></td>
        </tr>
        <tr>
          <td><span class="inputTitle"><?= _WORDLIST; ?></span></td>
          <td><select name="wordlist_lang"><?= $wordlist_options; ?></select></td>
        </tr>
      </table>
<?php } ?>
<script type="text/javascript"><!--
document.write('<div style="font-size:12px;"><br /><input type="checkbox" name="customLayout" value="TRUE" /> <?= _MANUALLY; ?></div>');
//--></script>

</div>

<div id="_options" style="display:none" class="tabcontent">

<table cellspacing="0">

    <tr><td><div class="inputTitle"><?= _ROWS; ?></div><div class="info">(<?= $minRows; ?>-<?= $maxRows; ?>)</div></td><td><input type="text" name="numRows" value="<?= $rowsSaved; ?>" size="2" maxlength="3" onblur="if(this.value><?= $maxRows; ?>)this.value=<?= $maxRows; ?>;if(this.value<<?= $minRows; ?>)this.value=<?= $minRows; ?>;" /></td></tr>
    <tr><td><div class="inputTitle"><?= _COLUMNS; ?></div><div class="info">(<?= $minCols; ?>-<?= $maxCols; ?>)</div></td><td><input type="text" name="numCols" value="<?= $colsSaved; ?>" size="2" maxlength="3" onblur="if(this.value><?= $maxCols; ?>)this.value=<?= $maxCols; ?>;if(this.value<<?= $minCols; ?>)this.value=<?= $minCols; ?>;" /></td></tr>
    <tr><td><div class="inputTitle"><?= _GRID_STYLES; ?></div><div class="info">(<?= _CUSTOM_REQ; ?>)</div></td><td><?= $gridStyles; ?><script type="text/javascript"><!--
document.write('<br /><input type="button" value="<?= _MAKE_CUSTOM; ?>" style="margin-top:5px;font-size:10px;" onclick="NewWindow(\'http://<?= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; ?>?act=customGrid\',\'700\',\'700\');" />');
//--></script></td></tr>
    <tr><td><div class="inputTitle"><?= _FONT_SIZE; ?></div><div class="info">(<?= _DEFAULT; ?> <?= $fSize; ?>)</div></td><td><input type="text" name="font" value="<?= $fontSizeSaved; ?>" size="2" maxlength="3" onblur="if(this.value=='' || this.value=='0')this.value='<?= $fSize; ?>'" /> mm</td></tr>
    <tr><td><div class="inputTitle"><?= _BACKGROUND; ?></div><div class="info">(<?= _DEFAULT; ?> <?= $bgColor; ?>)</div></td><td><input type="text" name="backgroundColor" size="10" value="<?= $bgColorSaved; ?>" /></td></tr>
    <tr><td><div class="inputTitle"><?= _FONT; ?></div><div class="info">(<?= _DEFAULT; ?> <?= $fColor; ?>)</div></td><td><input type="text" name="fontColor" size="10" value="<?= $fontColorSaved; ?>" /></td></tr>
    <tr><td><div class="inputTitle"><?= _HIGHLIGHT; ?></div><div class="info">(<?= _DEFAULT; ?> <?= $highColor; ?>)</div></td><td><input type="text" name="highlightColor" size="10" value="<?= $highColorSaved; ?>" /></td></tr>
    <tr><td><div class="inputTitle"><?= _RANDOM_WORDS; ?></div></td><td><input type="text" name="randomWords" size="2" maxlength="3" value="<?= $randomWordsSaved; ?>" /></td></tr>
    <tr><td><div class="inputTitle"><?= _LANGUAGE_GRID; ?></div></td><td><?= $langOptions; ?></td></tr>

</table>

</div>

<div id="_options2" style="display:none" class="tabcontent">

        <div class="inputTitle"><?= _WORDLIST_MAN; ?>:<br /><br />

<?= $borf; ?>

<?= $diag; ?>

<?= $upanddown; ?>

        <div style="margin-left:20px;"><?= _PLACEMENT; ?>: <?= $placement; ?></div><br />

        <input type="checkbox" name="hideWords" style="margin-left:20px;" value="TRUE"<?= $hideWordsSaved; ?> /> <?= _HIDE_LIST; ?><br />
        <input type="checkbox" name="alphaSort" style="margin-left:20px;" value="TRUE"<?= $alphaSortSaved; ?> /> <?= _SORT_LIST; ?><br />

<?php $alert = (ereg("MSIE",$_SERVER['HTTP_USER_AGENT'])) ? " onclick=\"ieWarning(this);\"" : ""; ?>
<script type="text/javascript"><!--
document.write('<input type="checkbox" name="wordsWindow" style="margin-left:20px;" value="TRUE"<?= $wordsWindowSaved; ?><?= $alert; ?> /> <?= _LIST_WINDOW; ?></li></ul>');
//--></script>

        <?= _OTHER; ?>:<br /><br />

        <input type="checkbox" name="useLetters" style="margin-left:20px;" value="TRUE"<?= $useLettersSaved; ?> /> <?= _LIST_LETTERS; ?> (<span onclick="alert('<?= _TIPS_LIST; ?>');"><?= _TIPS; ?></span>)</li></ul>

        <input type="checkbox" name="checkered" style="margin-left:20px;" value="TRUE"<?= $checkeredSaved; ?> onclick="if(document.getElementById('wordsForm').diag.options[2].selected!=true && this.checked==true) alert('<?= _WARN_CHECK; ?>');" /> <?= _CHECKERED; ?><ul><li><?= _CHECK_INFO; ?> (<span onclick="alert('<?= _TIPS_CHECK; ?>');"><?= _TIPS; ?></span>)</li></ul>

        <input type="checkbox" name="lowerCase" style="margin-left:20px;" value="TRUE"<?= $lowerCaseSaved; ?> /> <?= _LOWERCASE; ?><br />

        <input type="checkbox" name="centerGrid" style="margin-left:20px;" value="TRUE"<?= $centerGridSaved; ?> /> <?= _CENTER_GRID; ?><br />

        <input type="checkbox" name="forPrint" style="margin-left:20px;" value="TRUE"<?= $forPrintSaved; ?> /> <?= _FOR_PRINT; ?><ul><li><?= _PRINT_INFO; ?></li></ul>

        </div>

</div>

<div id="_load" style="display:none" class="tabcontent">

  <div style="text-align:center;">
  <br /><?= _PASTE_SAVE; ?>:<br />
  <textarea cols="40" rows="2" name="code"></textarea><br /><input type="button" value="<?= _LOAD_BUTTON; ?>" onclick="document.getElementById('wordsForm').more.value='reloadSaved';document.getElementById('wordsForm').submit();" /></div>

</div>

<div id="_information" style="display:none" class="tabcontent">

      <div style="font-weight:bold"><?= _INFORMATION; ?>:</div>
      <ul>
        <li><?= _LETTERS_ONLY; ?></li>
        <li><?= _DUPLICATES; ?></li>
        <li><?= _MINIMUM; ?>: <?= $minChars; ?></li>
        <li><?= _MAXIMUM ?>: <?= $maxChars; ?></li>
        <li><?= _SKIP; ?></li>
        <li><?= _DICTIONARY; ?></li>
        <li><?= _HIGH_LETTER; ?></li>
        <li><?= _QUESTION; ?></li>
        <li><b><?= _KEYS; ?></b> (<?= _JS_REQUIRED; ?>):
        <br /><br />
        <?= _ENTER_KEY; ?><br />
        <?= _R_KEY; ?><br />
        <?= _S_KEY; ?></li>
      </ul>

  <div style="text-align:center">
    <input type="button" id="remSet" value="<?= _REMOVE_SET_COOK; ?>" onclick="document.cookie = 'fswordfinder=d;expires=Thu, 01-Jan-1970 00:00:01 GMT';alert('<?= _REMOVE_SET_INFO; ?>');" /><br /><br />
    <input type="button" id="remCus" value="<?= _REMOVE_CUS_COOK; ?>" onclick="document.cookie = 'customGrid=d;expires=Thu, 01-Jan-1970 00:00:01 GMT';alert('<?= _REMOVE_CUS_INFO; ?>');" />
  </div>

</div>

<div style="text-align:center;"><br />
    <input type="submit" value="<?= _CREATE_PUZZLE; ?>" onclick="document.getElementById('wordsForm').more.value='javascript';" id="create" />&nbsp;<script type="text/javascript"><!--
document.write('&nbsp;&nbsp;<input type="button" value="<?= _SAVE_SETTINGS; ?>" onclick="saveSettings(this.form);" />&nbsp;')
//--></script>&nbsp;&nbsp;<input type="reset" value="<?= _RESET; ?>" />
</div>

</form>

</div>

<!--
Please keep this link visible and intact.
-->
<div style="text-align:center;font-size:12px;font-family:Arial;"><br /><a href="http://fswordfinder.sourceforge.net/" rel="external">FS.WordFinder 3.5.1</a></div>

</body>
</html>
<?php } ?>
