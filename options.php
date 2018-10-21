<?php
###########################################################
# Set the title of your page:

$title = "FS.WordFinder - Word Search Builder";

###########################################################
# Set the minimum and maximum number of chars allowed in each word.
# Be warned, the longer the word the harder it is for the script to
# find a spot for it.

$minChars = 3;
$maxChars = 15;

###########################################################
# Set the minimum and maximum number of rows and columns.

$minRows = 10;
$maxRows = 100;
$minCols = 10;
$maxCols = 100;

###########################################################
# Set the default for rows and columns. This will determine what the
# user sees when they visit the page and haven't saved their
# settings. These shouldn't be lower than the minimums you entered
# above.

$defaultRows = 20;
$defaultCols = 20;

###########################################################
# Set the maximum amount of characters the script will accept in the
# 'Enter your words' textarea.  Recommended limit is:
#
# $maxRows * $maxCols + $maxRows + $maxCols
#
# Of course, adjust based on server resources.  If you have a slow
# server set this a little lower.

$maxCharsLimit = 10200;

###########################################################
# Set the default font size for grid page.

$fSize = "6.0";

###########################################################
# Set the default background color for grid page.

$bgColor = "white";

###########################################################
# Set the default font color for grid page.

$fColor = "black";

###########################################################
# Set the default highlight color for grid page.

$highColor = "yellow";

###########################################################
# Set up the different languages.  The first language listed in array
# will be the default language.

$languages = array("Greek","English","German","Irish","Spanish","French","Dutch","Danish","Italian","Portuguese","Finnish","Albanian","Polish","Czech","Russian","Hebrew","Numbers");

###########################################################
# The number of times the script will try to find a spot for a word.
# Raising this increases the load on the server and probably won't
# find a spot for some words anyway. Best just to leave alone.

$retries = 55;

###########################################################
# Set the size limit of the puzzle title.

$puzzleTitleSize = 20;

###########################################################
# Gzip compression; if you get errors, turn this off
# (1 = enabled, 0 = disabled)

$gzip = 1;

###########################################################
# Change to point to the location of the languages directory.
# DO NOT ADD A TRAILING "/"

$pathToLangDir = "./lang";

###########################################################
# Change to point to the location of main_inc.php.

$pathToMaininc = "./main_inc.php";

###########################################################
# Change to point to the location of gridStyles.php.

$pathToGridstyles = "./gridStyles.php";

###########################################################
# Change to point to the location of the default language file.

$defaultLanguageFile = "English_main.php";

###########################################################
# Word list addon options
# You must have the word list addon to use these options.  Enabling
# the word list without having the addon will lead to errors.

# Enable the word list below. 1 = enabled, 0 = disabled

$wordlist = 1;

# Set the maximum number of words to be picked randomly from the db.
# Recommended limit is:
# 
# $maxCharsLimit / $maxChars
#
# Of course, adjust based on server resources.  If you have a slow
# server set this a little lower.

$max_wordlist = 680;

# If you're not using a database for your word list, you'll need to
# upload the word lists you wish to use to your server. Enter the
# path to these word lists in relation to fs.wordfinder.php
# Read the wordlist_readme.txt for more info on this option.
# DO NOT ADD A TRAILING "/"

$pathToWordlists = "./wordlists";

# If you're using a MySQL database for your word list, enable the
# following option

$useMysql = 0;

# Path to your mysql_connection.php file in relation to
# fs.wordfinder.php, only needed if you're using the word list addon.

$pathToMysqlConnection = "./mysql_connection.php";

?>