<?php

/**
 * Front controller.
 * Seluruh request masuk ke sini (dokumen root: folder public).
 */

require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once APP_ROOT . '/app/helpers/auth.php';

start_session();

purge_old_logs();

require_once APP_ROOT . '/app/core/App.php';
require_once APP_ROOT . '/app/core/Controller.php';

$app = new App();
$app->run();
