<?php

declare(strict_types=1);

namespace WorldManager\command\arg;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

final class GameRuleValueArgument extends StringEnumArgument{
	protected const VALUES = ['true' => true, 'false' => false];

	public function getTypeName(): string{
		return 'Boolean';
	}

	//validated by the command, so the error message can name the game rule
	public function canParse(string $testString, CommandSender $sender): bool{
		return true;
	}

	public function parse(string $argument, CommandSender $sender): string{
		return $argument;
	}
}
