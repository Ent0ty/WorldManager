<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\WorldService;

final class LoadSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'load', 'worldmanager.command.load');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('world'));
	}

	protected function handle(CommandSender $sender, array $args): void{
		$world = $this->worlds->load($args['world']);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get('load.success', ['world' => $world->getFolderName()]));
	}
}
