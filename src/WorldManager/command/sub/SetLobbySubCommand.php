<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;

final class SetLobbySubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'setlobby', 'worldmanager.command.setlobby');
	}

	protected function prepare(): void{
	}

	protected function handle(CommandSender $sender, array $args): void{
		if(!$sender instanceof Player){
			throw new WorldOperationException($this->lang->get('error.ingame_only'));
		}

		$world = $sender->getWorld();
		$position = $sender->getPosition();
		$this->worlds->setLobby($world, $position);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get('setlobby.success', ['world' => $world->getFolderName(), 'x' => $position->getFloorX(), 'y' => $position->getFloorY(), 'z' => $position->getFloorZ()]));
	}
}
