<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;

final class DuplicateSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'duplicate', 'worldmanager.command.duplicate');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('world'));
		$this->registerArgument(1, new RawStringArgument('newName'));
	}

	protected function handle(CommandSender $sender, array $args): void{
		$source = $this->worlds->requireName($args['world']);
		$this->worlds->duplicate($source, $args['newName'], function(\pocketmine\world\World|WorldOperationException $result) use ($sender, $source): void{
			$sender->sendMessage($result instanceof WorldOperationException
				? TextFormat::RED . $result->getMessage()
				: TextFormat::GREEN . $this->lang->get('duplicate.success', ['source' => $source, 'copy' => $result->getFolderName()]),
			);
		});
		$sender->sendMessage(TextFormat::GRAY . $this->lang->get('duplicate.progress', ['world' => $source]));
	}
}
