<?php declare(strict_types=1);

use DevLancer\ServerController\LocatorInterface;
use DevLancer\ServerController\MachineMonitor;
use DevLancer\ServerController\ServerControl;
use DevLancer\ServerController\ServerMonitor;
use DevLancer\ServerController\Terminal;

/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Response
{
    /**
     * @var string[]
     */
    private array $body;

    /**
     * @var int
     */
    private int $status = 200;

    /**
     * @var string[]
     */
    private array $header = [];

    private Terminal $terminal;

    private ServerControl $serverControl;

    public function __construct(Terminal $terminal)
    {
        $this->serverControl = new ServerControl($terminal);
        $this->terminal = $terminal;

    }

    public function start(LocatorInterface $locator): void
    {
        $port = 25565;
        $name = sprintf(ServerControl::SERVER_NAME, $port);
        $command = "screen -dmS $name top";

        if ($this->serverControl->isRunning($port)) {
            $this->body["is_running"] = true;
            $this->body["try_start"] = false;
            return;
        }

        try {
            $result = $this->serverControl->start($locator, $port, $command);
        } catch (Exception $exception) {
            $result = false;
        }

        $this->body["is_running"] = $result;
        $this->body["try_start"] = true;
    }

    public function stop(): void
    {
        $port = 25565;
        $name = sprintf(ServerControl::SERVER_NAME, $port);
        $command = "screen -X -S $name quit";

        if (!$this->serverControl->isRunning($port)) {
            $this->body["is_running"] = false;
            $this->body["try_stop"] = false;
            return;
        }

        try {
            $result = $this->serverControl->stop($port, $command);
        } catch (Exception $exception) {
            $result = false;
        }

        $this->body["is_running"] = $result;
        $this->body["try_stop"] = true;
    }

    public function info(): void
    {
        $port = 25565;
        if (!$this->serverControl->isRunning($port)) {
            $this->body = [
                "is_running" => false,
                "type" => "info"
            ];
            return;
        }

        $this->body = [
            "is_running" => true,
            "type" => "info"
        ];
    }

    public function infoServer(): void
    {
        $port = 25565;
        $pid = $this->serverControl->getPid($port);
        if (!$pid) {
            $this->body = ["is_running" => false, "type" => "server"];
            return;
        }

        $this->body = ["is_running" => true];
        $serverMonitor = new ServerMonitor($this->terminal);

        $this->body = [
            "uptime" => $serverMonitor->getUptime($pid),
            "cpu_usage" => $serverMonitor->getCpuUsage($pid),
            "memory_usage" => $serverMonitor->getMemoryUsage($pid),
            "type" => "server"
        ];
    }

    public function infoMachine(): void
    {
        $machineMonitor = new MachineMonitor($this->terminal);

        $this->body = [
            "cpu_usage" => $machineMonitor->getCpuUsage(),
            "memory_usage" => $machineMonitor->getMemoryUsage(MachineMonitor::FORMAT_PERCENTAGE),
            "memory" => $machineMonitor->getMemory(),
            "memory_free" => $machineMonitor->getMemoryFree(MachineMonitor::FORMAT_PERCENTAGE),
            "type" => "machine"
        ];
    }

    public function get(float $microtime)
    {
        http_response_code($this->status);
        $this->body['ping'] = microtime(true) - $microtime;
        header('Content-Type: application/json');
        foreach ($this->header as $header)
            header($header);

        echo json_encode($this->body);
    }
}