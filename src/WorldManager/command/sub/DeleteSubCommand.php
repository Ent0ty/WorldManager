<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\command\arg\ConfirmArgument;
use WorldManager\WorldService;

final class DeleteSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'delete', 'worldmanager.command.delete');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('world'));
		$this->registerArgument(1, new ConfirmArgument('confirm', true));
	}

	protected function handle(CommandSender $sender, array $args): void{
		$name = $this->worlds->requireName($args['world']);
		if(!isset($args['confirm'])){
			$sender->sendMessage(TextFormat::YELLOW . $this->lang->get('delete.confirm_warning', ['world' => $name]));
			$sender->sendMessage(TextFormat::YELLOW . $this->lang->get('delete.confirm_hint', ['world' => $name]));
			return;
		}

		$this->worlds->delete($name, function(bool $success) use ($sender, $name): void{
			$sender->sendMessage($success
				? TextFormat::GREEN . $this->lang->get('delete.success', ['world' => $name])
				: TextFormat::RED . $this->lang->get('delete.failed', ['world' => $name]),
			);
		});
		$sender->sendMessage(TextFormat::GRAY . $this->lang->get('delete.progress', ['world' => $name]));
	}
}
