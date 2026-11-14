<?php

declare(strict_types=1);

namespace WorldManager\command\arg;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use pocketmine\world\generator\GeneratorManager;
use function array_values;
use function in_array;
use function strtolower;

final class GeneratorArgument extends StringEnumArgument{

	public function getTypeName(): string{
		return 'generator';
	}

	public function getEnumValues(): array{
		return array_values(GeneratorManager::getInstance()->getGeneratorList());
	}

	public function canParse(string $testString, CommandSender $sender): bool{
		return in_array(strtolower($testString), $this->getEnumValues(), true);
	}

	public function parse(string $argument, CommandSender $sender): string{
		return strtolower($argument);
	}
}
