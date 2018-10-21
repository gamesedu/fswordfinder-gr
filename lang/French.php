<?php

# Saved in ISO-8859-1

$French = array("Ç","ç","ABCDEFGHIJKLMNOPQRSTUVWXYZ","ISO-8859-1");

# Allowed characters

$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

# Add unique alphabet to standard alphabet, also add unqiue letters

$chars .= $French[2].$French[0].$French[0];

?>