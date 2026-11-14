<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\WorldService;

final class RenameSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'rename', 'worldmanager.command.rename');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('oldName'));
		$this->registerArgument(1, new RawStringArgument('newName'));
	}

	protected function handle(CommandSender $sender, array $args): void{
		$old = $this->worlds->requireName($args['oldName']);
		$moved = $this->worlds->rename($old, $args['newName']);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get('rename.success', ['old' => $old, 'new' => $args['newName']]) . ($moved > 0 ? ' ' . $this->lang->get('rename.players_moved', ['count' => $moved]) : ''));
	}
}
