<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\lang\Translator;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;

abstract class WorldSubCommand extends BaseSubCommand{

	protected readonly Translator $lang;

	/**
	 * @param string[] $aliases
	 */
	public function __construct(protected readonly WorldService $worlds, string $name, string $permission, array $aliases = []){
		$this->lang = $worlds->getTranslator();
		parent::__construct($name, $this->lang->get("command.$name.description"), $aliases);
		$this->setPermission($permission);
	}

	/**
	 * @param array<string, mixed> $args
	 *
	 * @throws WorldOperationException
	 */
	abstract protected function handle(CommandSender $sender, array $args): void;

	final public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
		try{
			$this->handle($sender, $args);
		}catch(WorldOperationException $e){
			$sender->sendMessage(TextFormat::RED . $e->getMessage());
		}
	}
}
