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

SETUP
=====
The following uses default options:

Upload the files:

  fs.wordfinder.php
  main_inc.php
  gridStyles.php
  options.php

in ASCII to your server. Make sure they are all in the same
directory. Feel free to edit the main_inc.php file, which contains
the html for the main table where you input your words.

Then create a lang directory on your server, making sure it's in the
same directory as fs.wordfinder.php, and upload all the files in the
lang directory on your hard drive up to this new directory on your
server.

Adjust all the information to any changes you make in options.php.

You will then go to fs.wordfinder.php in your browser.


INFO
====
Hotscripts will always have the newest website address and version
info:  http://www.hotscripts.com/Detailed/21656.html


FILE LIST
=========
fs.wordfinder.php
main_inc.php
gridStyles.php
options.php
gnu_gpl.txt
readme.txt
lang/Albanian.php
lang/Czech.php
lang/Danish.php
lang/Dutch.php
lang/English.php
lang/English_main.php
lang/Finnish.php
lang/French.php
lang/German.php
lang/Greek.php
lang/Hebrew.php
lang/Irish.php
lang/Italian.php
lang/Numbers.php
lang/Polish.php
lang/Portuguese.php
lang/Russian.php
lang/Spanish.php

If you didn't get one of the files listed above, download the
official release at:  http://fswordfinder.sourceforge.net/


HELP ON LANGUAGE FILES
======================
This is information on the format of the language files, and how to
go about editing the current ones, or making your own.

On top is the encoding used to save the file.  Some languages require
different character sets and if they are saved in a charset that's
wrong the letters will be different or show as boxes or question
marks.  So it's very important that you save the file in the charset
for that language.

Next, for some languages, is the translation table.  The languages
that require a different charset other than ISO-8859-1 will need a
translation table.  Since the main table has the charset of
ISO-8859-1, PHP will translate all characters outside of that charset
into html hex codes.  This translation table will convert the
characters back into their former selves.

Next is an array which contains the following:

 - First:   The captial letters which are not in the latin
            alphabet A-Z.

 - Second:  The lowercase letters which are not in the latin alphabet
            A-Z.  These must be in the exact order as the captial
            letters.

 - Third:   That languages alphabet.  For example, in the Irish
            language, the letters K,Q,V,W,X,Y,Z are foreign letters.
            So I wrote the alphabet without those letters for Irish.

 - Fourth:  That languages charset.  Very important.

Next is the complete list of characters that language can accept.
For example, the Irish language allows for the foreign letters I
listed above.  Do not list the non-latin letters here.  (Anything
that's outside the A-Z alphabet).  You need to list all the letters
this language will accept, just in case the user inputs a word with
a foreign letter.

Next is the combination of the unique alphabet and the complete
alphabet, along with the unique letters.

Why I double up on the alphabets are to insure the script will choose
the letters from the language more than letters foreign to the
language.  But still allow the user to input words that have foreign
letters.


INPUT SAVE GAME VIA URL
=======================
fs.wordfinder.php?savedgame=<savegame_text>

It's that simple.


OUTPUT TO XML
=============
In order to output to XML, you have to call the script like so:

fs.wordfinder.php?xml=1

The file, fs.wordfinder_xml_sample_form.htm, shows a sample form on
what needs to be done so you can have XML returned.

On the coordinates:

Let's say you have a grid that's 10x10, with every cell having a
number. The first row is 1 to 10, the second is 11 to 20, and so on.
In order to get the X and Y from the number you use:

X = ceil(N / cols)
Y = (cols - ((X * cols) - N))

With cols being the width, and N being a coordinate number. In order
to go back (take an X and Y and get a coordinate number), use:

N = ((X - 1) * cols) + Y