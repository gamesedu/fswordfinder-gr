<?php

# Saved in ISO-8859-1

$German = array("ΔΛΟΦά","δλοφό","ABCDEFGHIJKLMNOPQRSTUVWXYZ","ISO-8859-1");

# Allowed characters

$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

# Add unique alphabet to standard alphabet, also add unqiue letters

$chars .= $German[2].$German[0].$German[0];

?>