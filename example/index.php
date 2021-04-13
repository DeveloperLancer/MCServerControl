<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
$view = file_get_contents("view.html");
$host = "http://" . $_SERVER["HTTP_HOST"];
$uri = explode("/", $_SERVER["REQUEST_URI"]);
$uri[(count($uri) - 1)] = "api.php";
$host .= implode("/", $uri);

echo str_replace("{HOST}", $host, $view);
