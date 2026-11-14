<?php

declare(strict_types=1);

namespace WorldManager\command;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use WorldManager\command\sub\CreateSubCommand;
use WorldManager\command\sub\DeleteSubCommand;
use WorldManager\command\sub\DuplicateSubCommand;
use WorldManager\command\sub\InfoSubCommand;
use WorldManager\command\sub\ListSubCommand;
use WorldManager\command\sub\LoadSubCommand;
use WorldManager\command\sub\RenameSubCommand;
use WorldManager\command\sub\SetLobbySubCommand;
use WorldManager\command\sub\TeleportSubCommand;
use WorldManager\command\sub\UnloadSubCommand;
use WorldManager\command\sub\WhitelistSubCommand;
use WorldManager\gamerule\GameRuleService;
use WorldManager\whitelist\WhitelistService;
use WorldManager\WorldService;
use function implode;

final class WorldCommand extends BaseCommand{

	public function __construct(Plugin $plugin, private readonly WorldService $worlds, private readonly GameRuleService $gameRules, private readonly WhitelistService $whitelist){
		parent::__construct($plugin, 'worldmanager', $worlds->getTranslator()->get('command.worldmanager.description'), ['wm']);
	}

	protected function prepare(): void{
		$lang = $this->worlds->getTranslator();
		$this->setPermission('worldmanager.command');
		$this->setErrorFormats([
			self::ERR_INVALID_ARG_VALUE => TextFormat::RED . $lang->get('error.argument_invalid'),
			self::ERR_INSUFFICIENT_ARGUMENTS => TextFormat::RED . $lang->get('error.arguments_missing'),
			self::ERR_TOO_MANY_ARGUMENTS => TextFormat::RED . $lang->get('error.arguments_too_many'),
			self::ERR_NO_ARGUMENTS => TextFormat::RED . $lang->get('error.arguments_unknown'),
		]);

		$this->registerSubCommand(new CreateSubCommand($this->worlds));
		$this->registerSubCommand(new LoadSubCommand($this->worlds));
		$this->registerSubCommand(new UnloadSubCommand($this->worlds));
		$this->registerSubCommand(new TeleportSubCommand($this->worlds));
		$this->registerSubCommand(new TeleportSubCommand($this->worlds, 'tp'));
		$this->registerSubCommand(new DuplicateSubCommand($this->worlds));
		$this->registerSubCommand(new RenameSubCommand($this->worlds));
		$this->registerSubCommand(new SetLobbySubCommand($this->worlds));
		$this->registerSubCommand(new WhitelistSubCommand($this->worlds, $this->whitelist));
		$this->registerSubCommand(new DeleteSubCommand($this->worlds));
		$this->registerSubCommand(new ListSubCommand($this->worlds));
		$this->registerSubCommand(new InfoSubCommand($this->worlds, $this->gameRules));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
		$lines = [TextFormat::GOLD . $this->worlds->getTranslator()->get('help.header')];
		foreach($this->getSubCommands() as $label => $subCommand){
			if($label === $subCommand->getName() && $subCommand->testPermissionSilent($sender)){
				$lines[] = TextFormat::YELLOW . "/$aliasUsed " . $subCommand->getUsageMessage() . TextFormat::GRAY . ' - ' . $subCommand->getDescription();
			}
		}
		$sender->sendMessage(implode("\n", $lines));
	}
}
