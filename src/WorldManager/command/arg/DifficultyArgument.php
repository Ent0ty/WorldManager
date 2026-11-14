<?php

declare(strict_types=1);

namespace WorldManager\command\arg;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use pocketmine\world\World;
use function strtolower;

final class DifficultyArgument extends StringEnumArgument{
	public const VALUES = [
		'peaceful' => World::DIFFICULTY_PEACEFUL,
		'easy' => World::DIFFICULTY_EASY,
		'normal' => World::DIFFICULTY_NORMAL,
		'hard' => World::DIFFICULTY_HARD,
	];

	public function getTypeName(): string{
		return 'difficulty';
	}

	public function parse(string $argument, CommandSender $sender): int{
		return self::VALUES[strtolower($argument)];
	}
}
