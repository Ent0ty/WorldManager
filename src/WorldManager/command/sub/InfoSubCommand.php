<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use WorldManager\command\arg\DifficultyArgument;
use WorldManager\gamerule\GameRule;
use WorldManager\gamerule\GameRuleService;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;
use function array_search;
use function count;
use function implode;

final class InfoSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds, private readonly GameRuleService $rules){
		parent::__construct($worlds, 'info', 'worldmanager.command.info');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('world', true));
	}

	protected function handle(CommandSender $sender, array $args): void{
		if(isset($args['world'])){
			$name = $this->worlds->requireName($args['world']);
		}elseif($sender instanceof Player){
			$name = $sender->getWorld()->getFolderName();
		}else{
			throw new WorldOperationException($this->lang->get('error.info_console_world'));
		}

		$line = fn(string $labelKey, string|int $value): string => TextFormat::YELLOW . $this->lang->get($labelKey) . ': ' . TextFormat::WHITE . $value;

		$world = $this->worlds->getLoaded($name);
		if($world === null){
			$sender->sendMessage(implode("\n", [
				TextFormat::GOLD . $this->lang->get('info.title', ['world' => $name]),
				$line('info.label.status', TextFormat::GRAY . $this->lang->get('status.unloaded')),
				TextFormat::GRAY . $this->lang->get('info.hint_load', ['world' => $name]),
			]));
			return;
		}

		$data = $world->getProvider()->getWorldData();
		$spawn = $world->getSpawnLocation();
		$difficulty = array_search($world->getDifficulty(), DifficultyArgument::VALUES, true);
		$lines = [
			TextFormat::GOLD . $this->lang->get('info.title', ['world' => $name]) . ($this->worlds->isDefault($world) ? TextFormat::GRAY . ' (' . $this->lang->get('status.default') . ')' : ''),
			$line('info.label.status', TextFormat::GREEN . $this->lang->get('status.loaded')),
			$line('info.label.generator', $data->getGenerator()),
		];
		if($data->getGeneratorOptions() !== ''){
			$lines[] = $line('info.label.preset', $data->getGeneratorOptions());
		}
		$lines[] = $line('info.label.seed', $world->getSeed());
		$lines[] = $line('info.label.spawn', "{$spawn->getFloorX()}, {$spawn->getFloorY()}, {$spawn->getFloorZ()}");
		$lines[] = $line('info.label.difficulty', $this->lang->get($difficulty === false ? 'difficulty.unknown' : "difficulty.$difficulty"));
		$lines[] = $line('info.label.time', $world->getTime());
		$lines[] = $line('info.label.players', count($world->getPlayers()));
		$lines[] = $line('info.label.chunks', count($world->getLoadedChunks()));
		$lines[] = $line('info.label.entities', count($world->getEntities()));
		$states = [];
		foreach(GameRule::cases() as $rule){
			$states[] = $rule->value . ': ' . ($this->rules->get($world, $rule) ? TextFormat::GREEN . 'true' : TextFormat::RED . 'false') . TextFormat::WHITE;
		}
		$lines[] = $line('info.label.gamerules', implode(', ', $states));
		$sender->sendMessage(implode("\n", $lines));
	}
}
