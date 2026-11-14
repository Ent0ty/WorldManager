<?php

declare(strict_types=1);

namespace WorldManager\command\arg;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

final class ConfirmArgument extends StringEnumArgument{
	protected const VALUES = ['confirm' => true];

	public function getTypeName(): string{
		return 'confirm';
	}

	public function parse(string $argument, CommandSender $sender): bool{
		return true;
	}
}
