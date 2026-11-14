<?php

declare(strict_types=1);

namespace WorldManager\gamerule;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\Player;
use pocketmine\world\format\io\data\BedrockWorldData;
use pocketmine\world\World;
use WorldManager\lang\Translator;
use WorldManager\WorldOperationException;
use function count;
use function implode;
use function strtolower;

/**
 * Game rules are stored in the level.dat of each world using the vanilla tag names, so they are world specific,
 * survive restarts and follow the world when it is duplicated or renamed.
 */
final class GameRuleService{

	public function __construct(private readonly Translator $lang){}

	public function get(World $world, GameRule $rule): bool{
		$tag = $this->getWorldData($world)?->getCompoundTag()->getTag($rule->value);

		return $tag instanceof ByteTag ? $tag->getValue() !== 0 : $rule->getDefault();
	}

	/**
	 * Persists the rule, applies its effect to the world and informs the players inside it.
	 *
	 * @throws WorldOperationException
	 */
	public function set(World $world, GameRule $rule, bool $value): void{
		$data = $this->getWorldData($world) ?? throw new WorldOperationException($this->lang->get('error.gamerule_unsupported', ['world' => $world->getFolderName()]));
		$tags = $data->getCompoundTag();
		$previous = $tags->getTag($rule->value);

		$tags->setByte($rule->value, $value ? 1 : 0);
		try{
			$data->save();
		}catch(\RuntimeException $e){
			if($previous === null){
				$tags->removeTag($rule->value);
			}else{
				$tags->setTag($rule->value, $previous);
			}
			throw new WorldOperationException($this->lang->get('error.gamerule_save_failed', ['world' => $world->getFolderName()]), 0, $e);
		}

		if($rule === GameRule::DO_DAYLIGHT_CYCLE){
			if($value){
				$world->startTime();
			}else{
				$world->stopTime();
			}
		}
		$this->send($world->getPlayers(), $world, $rule);
	}

	/**
	 * Re-applies the stored state of rules whose effect is not saved by PocketMine itself, e.g. after a world was loaded.
	 */
	public function applyStored(World $world): void{
		if(!$this->get($world, GameRule::DO_DAYLIGHT_CYCLE)){
			$world->stopTime();
		}
	}

	public function sendAll(Player $player, World $world): void{
		$this->send([$player], $world, ...GameRule::cases());
	}

	/**
	 * @throws WorldOperationException
	 */
	public function resolve(string $input): GameRule{
		return GameRule::tryFrom(strtolower($input)) ?? throw new WorldOperationException(
			$this->lang->get('error.gamerule_unknown', ['rule' => $input, 'available' => implode(', ', GameRule::getNames())]),
		);
	}

	/**
	 * @throws WorldOperationException
	 */
	public function parseValue(GameRule $rule, string $input): bool{
		return match(strtolower($input)){
			'true' => true,
			'false' => false,
			default => throw new WorldOperationException(
				$this->lang->get('error.gamerule_value_invalid', ['rule' => $rule->value, 'value' => $input, 'expected' => 'true, false']),
			),
		};
	}

	/**
	 * @param Player[] $players
	 */
	private function send(array $players, World $world, GameRule ...$rules): void{
		if(count($players) === 0){
			return;
		}

		$values = [];
		foreach($rules as $rule){
			if($rule->isSentToClient()){
				$values[$rule->value] = new BoolGameRule($this->get($world, $rule), false);
			}
		}
		if(count($values) === 0){
			return;
		}
		$packet = GameRulesChangedPacket::create($values);
		foreach($players as $player){
			$player->getNetworkSession()->sendDataPacket($packet);
		}
	}

	private function getWorldData(World $world): ?BedrockWorldData{
		$data = $world->getProvider()->getWorldData();

		return $data instanceof BedrockWorldData ? $data : null;
	}
}
