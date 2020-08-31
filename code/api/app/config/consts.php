<?php
defined('APP_PATH') || define('APP_PATH', realpath('../../app'));

define('REQUEST_PROTOCOL', $isSecure ? 'https' : 'http');

define('APP_LOG_DIR', APP_PATH . DIRECTORY_SEPARATOR . 'logs');

define('WEB_ROOT', DIRECTORY_SEPARATOR . '');

define('BASE_URL', REQUEST_PROTOCOL . '://' . getenv('PHP_SERVER_NAME'));

define('LOG_DIR', APP_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR);

define('REQUEST_ID', uniqid("", true));

define('END_OF_TIME', '2037-01-19 03:14:07');
define('TWO_WEEKS_IN_MINUTES', 20160);

define('ROLE_GLOBAL', 'Global');

define('ACCESS_TOKEN_KEY', 'RLAZ2P9BFM9W9UM2YZ9MM1RGLVGCKTEFB72RGJFI');

$GLOBALS['OMA_URI_BLACKLIST'] = [
];

$GLOBALS['OMA_RELATION_BLACKLIST'] = [
];

$GLOBALS['OMA_COLUMN_BLACKLIST'] = [
];
