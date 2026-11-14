<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use WorldManager\command\arg\PlayerArgument;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;

final class TeleportSubCommand extends WorldSubCommand{
	private const PERMISSION_OTHERS = 'worldmanager.command.teleport.others';

	public function __construct(WorldService $worlds, string $name = 'teleport'){
		parent::__construct($worlds, $name, 'worldmanager.command.teleport');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('world'));
		$this->registerArgument(1, new PlayerArgument('player', true));
	}

	protected function handle(CommandSender $sender, array $args): void{
		if(isset($args['player'])){
			$target = $sender->getServer()->getPlayerByPrefix($args['player'])
				?? throw new WorldOperationException($this->lang->get('error.player_offline', ['player' => $args['player']]));
		}elseif($sender instanceof Player){
			$target = $sender;
		}else{
			throw new WorldOperationException($this->lang->get('error.teleport_console_player'));
		}

		if($target !== $sender && !$sender->hasPermission(self::PERMISSION_OTHERS)){
			throw new WorldOperationException($this->lang->get('error.teleport_no_permission_others'));
		}

		$world = $this->worlds->teleport($target, $args['world']);
		$target->sendMessage(TextFormat::GREEN . $this->lang->get('teleport.success_self', ['world' => $world->getFolderName()]));
		if($target !== $sender){
			$sender->sendMessage(TextFormat::GREEN . $this->lang->get('teleport.success_other', ['player' => $target->getName(), 'world' => $world->getFolderName()]));
		}
	}
}
