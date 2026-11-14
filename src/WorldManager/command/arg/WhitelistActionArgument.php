<?php

declare(strict_types=1);

namespace WorldManager\command\arg;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use function strtolower;

final class WhitelistActionArgument extends StringEnumArgument{
	protected const VALUES = ['on' => 'on', 'off' => 'off', 'add' => 'add', 'remove' => 'remove', 'list' => 'list'];

	public function getTypeName(): string{
		return 'action';
	}

	public function parse(string $argument, CommandSender $sender): string{
		return self::VALUES[strtolower($argument)];
	}
}
