<?php
// Temporary cache-buster — delete this file after use
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "OPcache cleared!<br>";
} else {
    echo "OPcache not active.<br>";
}
echo "Done! Now visit /cards — the 3-button UI should appear.";
