<?php

declare(strict_types=1);

namespace WorldManager\whitelist;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use WorldManager\WorldService;

final class WhitelistListener implements Listener{

	public function __construct(private readonly WhitelistService $whitelist, private readonly WorldService $worlds){}

	/**
	 * @priority HIGH
	 */
	public function onEntityTeleport(EntityTeleportEvent $event): void{
		$player = $event->getEntity();
		$target = $event->getTo()->getWorld();
		if($player instanceof Player && $event->getFrom()->getWorld() !== $target && !$this->whitelist->isAllowed($player, $target)){
			$event->cancel();
			$this->sendDenied($player, $target);
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event): void{
		$player = $event->getPlayer();
		$world = $player->getWorld();
		if(!$this->whitelist->isAllowed($player, $world)){
			$player->teleport($this->worlds->requireDefault()->getSafeSpawn());
			$this->sendDenied($player, $world);
		}
	}

	public function onPlayerRespawn(PlayerRespawnEvent $event): void{
		$player = $event->getPlayer();
		if(!$this->whitelist->isAllowed($player, $event->getRespawnPosition()->getWorld())){
			$event->setRespawnPosition($this->worlds->requireDefault()->getSafeSpawn());
		}
	}

	private function sendDenied(Player $player, World $world): void{
		$player->sendMessage(TextFormat::RED . $this->worlds->getTranslator()->get('player.whitelist_denied', ['world' => $world->getFolderName()]));
	}
}
