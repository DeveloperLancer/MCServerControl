<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerControl;


use DevLancer\MCServerControl\Exception\FailedExecute;

/**
 * Class Process
 * @package DevLancer\MCServerControl
 */
class Process implements ProcessInterface
{
    const CMD_SEARCH = "ps -aux | grep --color=never %s\n";

    /**
     * @var int
     */
    public static int $processPid = 1;

    /**
     * @var int
     */
    public static int $processCpu = 2;

    /**
     * @var int
     */
    public static int $processMemory = 3;

    /**
     * @var int
     */
    public static int $processCommand = 10;

    /**
     * @var TerminalInterface
     */
    private TerminalInterface $terminal;

    /**
     * Process constructor.
     * @param TerminalInterface $terminal
     */
    public function __construct(TerminalInterface $terminal)
    {
        $this->terminal = $terminal;
    }

    /**
     * @param string $name
     * @param string $cmd
     * @return string[]|null
     * @throws FailedExecute
     */
    public function getByName(string $name, string $cmd = self::CMD_SEARCH): ?array
    {
        $cmd = sprintf($cmd, $name);
        $result = $this->terminal->exec($cmd);

        $result = explode("\n", $result)[0];
        $result = $this->explode($result);

        if (!isset($result[self::$processCommand])) {
            trigger_error("The process could not be processed properly", E_USER_WARNING);
            return null;
        }

        if (strpos($result[self::$processCommand], $name) === false)
            return null;

        return $result;
    }

    /**
     * @param int $pid
     * @param string $cmd
     * @return string[]|null
     * @throws FailedExecute
     */
    public function getByPid(int $pid, string $cmd = self::CMD_SEARCH): ?array
    {
        $cmd = sprintf($cmd, $pid);
        $result = $this->terminal->exec($cmd);
        $result = explode("\n", $result);

        foreach ($result as $item) {
            $item = $this->explode($item);

            if (!isset($item[self::$processPid])) {
                trigger_error("The process could not be processed properly", E_USER_WARNING);
                continue;
            }

            if ($item[self::$processPid] == $pid)
                return $item;
        }

        return null;
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $separator
     * @return array|string[]
     */
    public function explode(string $string, int $length = -1, string $separator = " "): array
    {
        if ($length == 0)
            return [];

        $key = 0;
        $result = [""];
        $last_char = "";
        for ($i = 0; strlen($string) > $i; $i++) {

            $char = $string[$i];
            if ($char == $separator && ($key < $length || $length < 0)) {
                if ($last_char != $separator) {
                    $key++;
                    $result[] = "";
                }

                $last_char = $separator;
                continue;
            }

            $result[$key] .= $char;
            $last_char = $char;
        }

        return $result;
    }
}