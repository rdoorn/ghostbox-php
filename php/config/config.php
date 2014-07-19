<?php

# Set proper locale to support utf8 - however on windows you might want to remove this or set this to en_US.ISO-8859-1
setlocale(LC_CTYPE, "en_US.UTF-8");

# Debug levels
# 1 = ...
# 2 = Plugins
# 4 = ...
# 16 = shell exec
define ('DEBUG' , 16);
define ('DEBUG_SQL_ERROR', 1);
define ('DEBUG_SQL_ALL', 1);

# Benchmarking on? 
define ('BENCHMARK' , true);

# Main path settings
define ('MAIN_DIR', '/storage/shared/www/ghostbox.org/www');
define ('DOMAIN_URL', 'http://www.ghostbox.org');

# Database settings
define ('DB_HOST', '127.0.0.1');
define ('DB_NAME', 'ghostbox');
define ('DB_USER', 'ghostbox');
define ('DB_PASS', 'ph0t0s!');

# Display tweaks
define ('THUMB_SIZE', 400);
define ('THUMB_QUALITY', 60);
define ('IMAGE_QUALITY', 90);

define ('PALETTE_AVERAGE', 7);

define ('IMAGE_HEIGHTS', serialize(array( NULL, THUMB_SIZE, 1600, 1200, 1080 )) );
define ('IMAGE_WIDTHS', serialize(array( NULL, THUMB_SIZE, 2560, 1920, 1600 )) );




define ('METADATA_HANDLER', 'Exiv2\metaData');
define ('IMAGE_HANDLER', 'ImageMagick\imageHandler');

/*
 *   The values below should not need changing
 *
 */



# Sub URL's
define ('LOGIN_URL', DOMAIN_URL.'/login'); // FIXME: will need something pointing it to the correct plugin -> facebook or google
define ('CACHE_PATH', '/images/cache'); // url path to cache
define ('CACHE_URL', DOMAIN_URL.CACHE_PATH); // full path to cache

# Sub Directories
define ('DATA_DIR', MAIN_DIR.'/data'); // location of images on disk
define ('TEMPLATE_DIR', MAIN_DIR.'/templates'); // location of templates on disk
define ('CACHE_DIR', MAIN_DIR.'/html'.CACHE_PATH); // location of cache on disk


?>
