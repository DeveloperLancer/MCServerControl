<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use DevLancer\ServerController\Locator;
use DevLancer\ServerController\Terminal;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

require_once ("../vendor/autoload.php");
require ("Response.php");

$host = "";
$login = "";
$password = "";

$microtime = microtime(true);
$ssh = new SSH2($host);
$ssh->login($login, $password);
$terminal = new Terminal($ssh, $password);
$response = new Response($terminal);

if (!isset($_GET['action'])) {
    $response->info();
} else {
    switch (strtolower($_GET['action'])) {
        case "start":
            $sftp = new SFTP($host);
            $sftp->login($login, $password);
            $locator = new Locator($sftp, "");
            $response->start($locator);
            break;
        case "stop":
            $response->stop();
            break;
        case "info-server":
            $response->infoServer();
            break;
        case "info-machine":
            $response->infoMachine();
            break;
        default:
            $response->info();
            break;
    }
}

$response->get($microtime);