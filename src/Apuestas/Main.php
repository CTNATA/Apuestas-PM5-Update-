<?php

declare(strict_types=1);

namespace Apuestas;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\Config;
use function array_shift;

class Main extends PluginBase implements Listener {

    public EconomyAPI $plugin;
    public Config $config;
    public Config $task;
    public Config $players;

    public function onEnable() : void {
        $this->plugin = EconomyAPI::getInstance();
        $this->getServer()->getCommandMap()->register("apostar", new ApuestaCommand($this));
        $this->saveResource("config.yml");
        $this->players = new Config($this->getDataFolder() . "/players.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
        $this->task = new Config($this->getDataFolder() . "/task.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getScheduler()->scheduleRepeatingTask(new TimeTask($this), 20);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $join = [];
        if (!$this->task->get($player->getName())) {
            $this->task->set($player->getName(), 0);
            $this->task->save();
        }
        if (!$this->players->get("players")) {
            $join[] = $player->getName();
            $this->players->set("players", $join);
            $this->players->save();
        } else {
            if (!in_array($player->getName(), $this->players->get("players"))) {
                foreach ($this->players->get("players") as $players) {
                    $join[] = $players;
                }
                array_push($join, $player->getName());
                $this->players->set("players", $join);
                $this->players->save();
            }
        }
    }

    public function replaceVars(string $str, array $vars): string {
        foreach ($vars as $key => $value) {
            $str = str_replace("{" . $key . "}", $value, $str);
        }
        return $str;
    }

    public function getMessage(string $file) {
        $config = new Config($this->getDataFolder() . "/config.yml");
        return $config->get($file);
    }

    public function getMoney(Player $player) : float {
        $money = $this->plugin->myMoney($player->getName());
        assert(is_float($money));
        return $money;
    }

    public function addMoney(Player $player, float $money) : void {
        $this->plugin->addMoney($player->getName(), $money);
    }

    public function removeMoney(Player $player, float $money) : void {
        $this->plugin->reduceMoney($player->getName(), $money);
    }

    public function formatMoney(float $money) : string{
        return $this->plugin->getMonetaryUnit() . number_format($money);
    }
}
