<?php

declare(strict_types=1);

namespace WorldManager\command\arg;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

final class PlayerArgument extends RawStringArgument{

	public function getNetworkType(): int{
		return AvailableCommandsPacket::ARG_TYPE_TARGET;
	}

	public function getTypeName(): string{
		return 'player';
	}
}
