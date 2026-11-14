<?php

declare(strict_types=1);

namespace WorldManager;

use bStats\PocketmineMp\Metrics;
use CortexPE\Commando\PacketHooker;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\plugin\PluginBase;
use WorldManager\command\GameRuleCommand;
use WorldManager\command\WorldCommand;
use WorldManager\gamerule\GameRuleListener;
use WorldManager\gamerule\GameRuleService;
use WorldManager\lang\Translator;
use WorldManager\whitelist\WhitelistListener;
use WorldManager\whitelist\WhitelistService;
use function class_exists;

final class Main extends PluginBase{

	protected function onEnable(): void{
		$this->saveDefaultConfig();
		$translator = new Translator($this, (string) $this->getConfig()->get('language', Translator::DEFAULT_LANGUAGE));

		if(!PacketHooker::isRegistered()){
			if(class_exists(SimplePacketHandler::class)){
				PacketHooker::register($this);
			}else{
				$this->getLogger()->notice($translator->get('log.packet_handler_missing'));
			}
		}

		$server = $this->getServer();
		$worlds = new WorldService($server, $translator);
		$gameRules = new GameRuleService($translator);
		$whitelist = new WhitelistService($server, $worlds);

		$server->getPluginManager()->registerEvents(new GameRuleListener($this, $gameRules), $this);
		$server->getPluginManager()->registerEvents(new WhitelistListener($whitelist, $worlds), $this);
		foreach($server->getWorldManager()->getWorlds() as $world){
			$gameRules->applyStored($world);
		}

		$server->getCommandMap()->registerAll('worldmanager', [
			new WorldCommand($this, $worlds, $gameRules, $whitelist),
			new GameRuleCommand($this, $worlds, $gameRules),
		]);

		$this->registerMetrics();
	}

	private function registerMetrics(): void {
		new Metrics($this, 35339);
	}
}
