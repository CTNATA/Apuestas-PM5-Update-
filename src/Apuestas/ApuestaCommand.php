<?php

namespace Apuestas;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\CustomForm;
use Utils\PluginUtils;

class ApuestaCommand extends Command {

    private Main $main;

    public function __construct(Main $main) {
        $this->main = $main;
        parent::__construct("apostar", "§l§gPrueba §dTu §aSuerte");
        $this->setPermission("negro.command.apostar");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if ($sender instanceof Player) {
            if ($sender->hasPermission("negro.command.apostar")) {
                $this->openApuestasForm($sender); 
            } else {
                $sender->sendMessage(TextFormat::RED . "No tienes permiso para usar este comando.");
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Este comando solo puede ser utilizado por jugadores.");
        }
    }

    private function openApuestasForm(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) {
                return; 
            }

            $betAmount = intval($data[0]);
            $this->processApuestas($player, $betAmount); 
        });

        $form->setTitle("§l§dApuesta §bTu §eDinero");
        $form->addInput("§l§f>>§l§gEl Dinero Que §dDeseas Apostar §9Introducelo:", "§bPon §cEl §6Monto §bAqui");
        $player->sendForm($form);
    }

    private function processApuestas(Player $player, int $amount): void {
        $minutes = intval($this->main->task->get($player->getName()) / 60);
        $seconds = $this->main->task->get($player->getName()) - $minutes * 60;

        if ($amount <= 0) {
            $player->sendMessage(TextFormat::RED . "Por favor, introduce una cantidad válida");
            return;
        }

        if ($amount < $this->main->getMoney($player)) {
            if ($this->main->task->get($player->getName()) <= 0) {
                $result = mt_rand(0, 100) >= $this->main->config->get("probabilidad");

                if ($result) {
                    
                    $player->sendMessage(TextFormat::colorize($this->main->replaceVars($this->main->getMessage("apuesta-perdida"), [
                        "PREFIX" => $this->main->getMessage("prefix"),
                        "DINERO" => "$amount"
                    ])));

                    $this->main->removeMoney($player, floatval($amount));
                    PluginUtils::playSound($player, "random.break", 20);
                    $player->sendTitle("§l§cLol Acabas §4De Perder", "§0No Es Tu Dia De Suerte", 20, 40, 20);
                } else {
                    $winAmount = $amount * 2;
                    $player->sendMessage(TextFormat::colorize($this->main->replaceVars($this->main->getMessage("apuesta-ganada"), [
                        "PREFIX" => $this->main->getMessage("prefix"),
                        "CANTIDAD" => strval($winAmount)
                    ])));

                    $this->main->addMoney($player, $winAmount);
                    PluginUtils::playSound($player, "firework.twinkle", 20);
                    $player->sendTitle("§l§aUff §dQue §bSuerte", "§gAcabas §eDe §cVenirte §dY §bGanaste §9$winAmount", 20, 40, 20);
                }
                $this->main->task->set($player->getName(), $this->main->config->get("cooldown"));
                $this->main->task->save();

            } else {
                $player->sendMessage(TextFormat::colorize($this->main->replaceVars($this->main->getMessage("cooldown-activo"), [
                    "PREFIX" => $this->main->getMessage("prefix"),
                    "MINUTOS" => strval($minutes),
                    "SEGUNDOS" => strval($seconds)
                ])));
            }

        } else {
            $player->sendMessage(TextFormat::colorize($this->main->replaceVars($this->main->getMessage("falta-dinero"), [
                "PREFIX" => $this->main->getMessage("prefix")
            ])));
        }
    }
}
