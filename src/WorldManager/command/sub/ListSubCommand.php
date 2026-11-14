<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\WorldService;
use function count;
use function implode;

final class ListSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'list', 'worldmanager.command.list');
	}

	protected function prepare(): void{
	}

	protected function handle(CommandSender $sender, array $args): void{
		$names = $this->worlds->getAvailableNames();
		$lines = [TextFormat::GOLD . $this->lang->get('list.header', ['count' => count($names)])];
		foreach($names as $name){
			$world = $this->worlds->getLoaded($name);
			if($world === null){
				$lines[] = TextFormat::GRAY . "- $name [" . $this->lang->get('status.unloaded') . ']';
				continue;
			}
			$status = TextFormat::GREEN . $this->lang->get('status.loaded') . TextFormat::GRAY . ', ' . $this->lang->get('list.players', ['count' => count($world->getPlayers())])
				. ($this->worlds->isDefault($world) ? ', ' . $this->lang->get('status.default') : '');
			$lines[] = TextFormat::WHITE . "- $name " . TextFormat::GRAY . '[' . $status . TextFormat::GRAY . ']';
		}
		$sender->sendMessage(implode("\n", $lines));
	}
}
