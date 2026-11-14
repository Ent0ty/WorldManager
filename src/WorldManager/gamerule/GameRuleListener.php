<?php

declare(strict_types=1);

namespace WorldManager\gamerule;

use pocketmine\entity\object\PrimedTNT;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

final class GameRuleListener implements Listener{

	public function __construct(private readonly Plugin $plugin, private readonly GameRuleService $rules){}

	public function onWorldLoad(WorldLoadEvent $event): void{
		$this->rules->applyStored($event->getWorld());
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerJoin(PlayerJoinEvent $event): void{
		$player = $event->getPlayer();
		$this->rules->sendAll($player, $player->getWorld());
	}

	/**
	 * @priority MONITOR
	 */
	public function onEntityTeleport(EntityTeleportEvent $event): void{
		$player = $event->getEntity();
		$target = $event->getTo()->getWorld();
		if($player instanceof Player && $event->getFrom()->getWorld() !== $target){
			$this->rules->sendAll($player, $target);
		}
	}

	public function onPlayerDeath(PlayerDeathEvent $event): void{
		$player = $event->getPlayer();
		$world = $player->getWorld();
		if($this->rules->get($world, GameRule::KEEP_INVENTORY)){
			$event->setKeepInventory(true);
			$event->setKeepXp(true);
		}
		if(!$this->rules->get($world, GameRule::SHOW_DEATH_MESSAGES)){
			$event->setDeathMessage('');
		}
	}

	public function onEntityDeath(EntityDeathEvent $event): void{
		$entity = $event->getEntity();
		if(!$entity instanceof Player && !$this->rules->get($entity->getWorld(), GameRule::DO_MOB_LOOT)){
			$event->setDrops([]);
			$event->setXpDropAmount(0);
		}
	}

	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void{
		$victim = $event->getEntity();
		$damager = $event->getDamager();
		if($victim instanceof Player && $damager instanceof Player && $victim !== $damager && !$this->rules->get($victim->getWorld(), GameRule::PVP)){
			$event->cancel();
		}
	}

	public function onEntityDamage(EntityDamageEvent $event): void{
		$player = $event->getEntity();
		if(!$player instanceof Player){
			return;
		}

		$rule = match($event->getCause()){
			EntityDamageEvent::CAUSE_DROWNING => GameRule::DROWNING_DAMAGE,
			EntityDamageEvent::CAUSE_FALL => GameRule::FALL_DAMAGE,
			EntityDamageEvent::CAUSE_FIRE, EntityDamageEvent::CAUSE_FIRE_TICK, EntityDamageEvent::CAUSE_LAVA => GameRule::FIRE_DAMAGE,
			default => null,
		};
		if($rule !== null && !$this->rules->get($player->getWorld(), $rule)){
			$event->cancel();
		}
	}

	public function onEntityRegainHealth(EntityRegainHealthEvent $event): void{
		$player = $event->getEntity();
		if($player instanceof Player && $event->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION && !$this->rules->get($player->getWorld(), GameRule::NATURAL_REGENERATION)){
			$event->cancel();
		}
	}

	public function onPlayerExhaust(PlayerExhaustEvent $event): void{
		$player = $event->getPlayer();
		if($event->getCause() === PlayerExhaustEvent::CAUSE_HEALTH_REGEN && !$this->rules->get($player->getWorld(), GameRule::NATURAL_REGENERATION)){
			$event->cancel();
		}
	}

	public function onEntityExplode(EntityExplodeEvent $event): void{
		$entity = $event->getEntity();
		if($entity instanceof PrimedTNT && !$this->rules->get($entity->getWorld(), GameRule::TNT_EXPLOSION_DROP_DECAY)){
			$event->setYield(100.0);
		}
	}
}
