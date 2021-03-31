<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ServerController;


class Command
{
    const IS_RUNNING = Process::CMD_SEARCH;

    const MINECRAFT_START = "cd {PATH}; screen -dmS {NAME} java {PARAMS} -jar {FILE} nogui --port {PORT}";
    const MINECRAFT_STOP = "screen -X -S %s quit";
}