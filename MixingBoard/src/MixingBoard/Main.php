<?php
namespace MixingBoard;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

# Command
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;



class Main extends PluginBase implements Listener, CommandExecutor {


	public function onLoad(){
	}

	public function onEnable(){
		$task = new Repeat();
		Server::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
	}

	/**
	*	/mでえりーぜがながれます
	*/
	public function onCommand(CommandSender $s, Command $cmd, string $label, array $a): bool{
		$user = $s->getName();
		switch($cmd->getName()){
			case "m":
				//$player = Server::getInstance()->getPlayer("meu32ki");
				$player = $s;
				$mno = isset($a[0]) ? Music::getMusic($a[0]) : null;
				$sno = isset($a[1]) ? Speaker::getSpeaker($a[1]) : null;
				MusicManager::PlayIndividual($player, $mno, $sno);
				return true;
			break;
			default:

			break;
		}
	}
}

class Repeat extends Task {

	public function onRun(int $tick){
		MusicManager::tick();
	}

}