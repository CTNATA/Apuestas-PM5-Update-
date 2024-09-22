<?php
namespace Apuestas;

use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;

class TimeTask extends Task {

    private Main $main;

    public function __construct(Main $main)
    {
        $this->main = $main;
    }

    public function onRun(): void
    {
        if($this->main->players->get("players")) {
            foreach($this->main->players->get("players") as $player) {
                if($this->main->task->get($player) > 0) {
                    $this->main->task->set($player, $this->main->task->get($player) - 1);
                    $this->main->task->save();
                }
            }
        }
    }
}