<?php

declare(strict_types=1);

namespace WorldManager\command;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use WorldManager\command\arg\GameRuleArgument;
use WorldManager\command\arg\GameRuleValueArgument;
use WorldManager\gamerule\GameRuleService;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;

final class GameRuleCommand extends BaseCommand{
	public function __construct(Plugin $plugin, private readonly WorldService $worlds, private readonly GameRuleService $rules){
		parent::__construct($plugin, 'gamerule', $worlds->getTranslator()->get('command.gamerule.description'));
	}

	protected function prepare(): void{
		$lang = $this->worlds->getTranslator();
		$syntaxError = TextFormat::RED . $lang->get('error.gamerule_syntax');
		$this->setPermission('worldmanager.command.gamerule');
		$this->setErrorFormats([
			self::ERR_INVALID_ARG_VALUE => TextFormat::RED . $lang->get('error.argument_invalid'),
			self::ERR_INSUFFICIENT_ARGUMENTS => $syntaxError,
			self::ERR_TOO_MANY_ARGUMENTS => $syntaxError,
		]);

		$this->registerArgument(0, new GameRuleArgument('rule'));
		$this->registerArgument(1, new GameRuleValueArgument('value', true));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void{
		$lang = $this->worlds->getTranslator();
		try{
			$rule = $this->rules->resolve($args['rule']);
			$value = isset($args['value']) ? $this->rules->parseValue($rule, $args['value']) : null;
			$world = $sender instanceof Player ? $sender->getWorld() : $this->worlds->requireDefault();

			if($value === null){
				$sender->sendMessage($lang->get('gamerule.query', [
					'rule' => $rule->value,
					'value' => $this->rules->get($world, $rule) ? 'true' : 'false',
					'world' => $world->getFolderName(),
				]));
				return;
			}

			$this->rules->set($world, $rule, $value);
			$sender->sendMessage(TextFormat::GREEN . $lang->get('gamerule.success', [
				'rule' => $rule->value,
				'value' => $value ? 'true' : 'false',
				'world' => $world->getFolderName(),
			]));
		}catch(WorldOperationException $e){
			$sender->sendMessage(TextFormat::RED . $e->getMessage());
		}
	}
}
