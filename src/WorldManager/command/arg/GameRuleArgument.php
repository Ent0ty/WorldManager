<?php

declare(strict_types=1);

namespace WorldManager\command\arg;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use WorldManager\gamerule\GameRule;

final class GameRuleArgument extends StringEnumArgument{

	public function getTypeName(): string{
		return 'BoolGameRule';
	}

	public function getEnumValues(): array{
		return GameRule::getNames();
	}

	//the enum only provides client hints, unknown rules are reported by the command with a more helpful message
	public function canParse(string $testString, CommandSender $sender): bool{
		return true;
	}

	public function parse(string $argument, CommandSender $sender): string{
		return $argument;
	}
}
