<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\WorldService;

final class UnloadSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'unload', 'worldmanager.command.unload');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('world'));
	}

	protected function handle(CommandSender $sender, array $args): void{
		$name = $this->worlds->requireName($args['world']);
		$moved = $this->worlds->unload($name);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get('unload.success', ['world' => $name]) . ($moved > 0 ? ' ' . $this->lang->get('unload.players_moved', ['count' => $moved]) : ''));
	}
}
