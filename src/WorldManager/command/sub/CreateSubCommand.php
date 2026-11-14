<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\command\arg\DifficultyArgument;
use WorldManager\command\arg\GeneratorArgument;
use WorldManager\WorldService;

final class CreateSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds){
		parent::__construct($worlds, 'create', 'worldmanager.command.create');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new RawStringArgument('name'));
		$this->registerArgument(1, new GeneratorArgument('generator', true));
		$this->registerArgument(2, new RawStringArgument('seed', true));
		$this->registerArgument(3, new DifficultyArgument('difficulty', true));
		$this->registerArgument(4, new RawStringArgument('preset', true));
	}

	protected function handle(CommandSender $sender, array $args): void{
		$generator = $args['generator'] ?? 'normal';
		$world = $this->worlds->create($args['name'], $generator, $args['seed'] ?? null, $args['preset'] ?? '', $args['difficulty'] ?? null);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get('create.success', ['world' => $world->getFolderName(), 'generator' => $generator, 'seed' => $world->getSeed()]));
	}
}
