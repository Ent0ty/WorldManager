<?php

declare(strict_types=1);

namespace WorldManager\command\sub;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use WorldManager\command\arg\PlayerArgument;
use WorldManager\command\arg\WhitelistActionArgument;
use WorldManager\whitelist\WhitelistService;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;
use function count;
use function implode;

final class WhitelistSubCommand extends WorldSubCommand{

	public function __construct(WorldService $worlds, private readonly WhitelistService $whitelist){
		parent::__construct($worlds, 'whitelist', 'worldmanager.command.whitelist');
	}

	protected function prepare(): void{
		$this->registerArgument(0, new WhitelistActionArgument('action'));
		$this->registerArgument(1, new RawStringArgument('world'));
		$this->registerArgument(2, new PlayerArgument('player', true));
	}

	protected function handle(CommandSender $sender, array $args): void{
		$world = $this->worlds->requireName($args['world']);
		match($args['action']){
			'on', 'off' => $this->toggle($sender, $world, $args['action'] === 'on'),
			'add' => $this->add($sender, $world, $this->requirePlayer($args)),
			'remove' => $this->remove($sender, $world, $this->requirePlayer($args)),
			default => $this->list($sender, $world),
		};
	}

	private function toggle(CommandSender $sender, string $world, bool $enabled): void{
		$moved = $this->whitelist->setEnabled($world, $enabled);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get($enabled ? 'whitelist.enabled' : 'whitelist.disabled', ['world' => $world]) . ($moved > 0 ? ' ' . $this->lang->get('whitelist.players_moved', ['count' => $moved]) : ''));
	}

	private function add(CommandSender $sender, string $world, string $player): void{
		$this->whitelist->add($world, $player);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get('whitelist.added', ['player' => $player, 'world' => $world]));
	}

	private function remove(CommandSender $sender, string $world, string $player): void{
		$moved = $this->whitelist->remove($world, $player);
		$sender->sendMessage(TextFormat::GREEN . $this->lang->get('whitelist.removed', ['player' => $player, 'world' => $world]) . ($moved > 0 ? ' ' . $this->lang->get('whitelist.players_moved', ['count' => $moved]) : ''));
	}

	private function list(CommandSender $sender, string $world): void{
		$players = $this->whitelist->getPlayers($world);
		$status = $this->whitelist->isEnabled($world) ? TextFormat::GREEN . $this->lang->get('whitelist.status.enabled') : TextFormat::RED . $this->lang->get('whitelist.status.disabled');
		$sender->sendMessage(implode("\n", [
			TextFormat::GOLD . $this->lang->get('whitelist.list.header', ['world' => $world, 'count' => count($players)]) . TextFormat::GRAY . ' [' . $status . TextFormat::GRAY . ']',
			$players === [] ? TextFormat::GRAY . $this->lang->get('whitelist.list.empty') : TextFormat::WHITE . implode(', ', $players),
		]));
	}

	/**
	 * @param array<string, mixed> $args
	 *
	 * @throws WorldOperationException
	 */
	private function requirePlayer(array $args): string{
		return $args['player'] ?? throw new WorldOperationException($this->lang->get('error.arguments_missing'));
	}
}
