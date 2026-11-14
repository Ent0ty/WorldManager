<?php

declare(strict_types=1);

namespace WorldManager\whitelist;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Filesystem;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use Symfony\Component\Filesystem\Path;
use WorldManager\lang\Translator;
use WorldManager\WorldOperationException;
use WorldManager\WorldService;
use function array_filter;
use function array_map;
use function array_search;
use function array_unique;
use function array_values;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_bool;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function sort;
use function strtolower;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * The whitelist of a world is stored in its folder, so it follows the world when it is duplicated or renamed.
 * The default world is never restricted, because it is the fallback destination for rejected players.
 */
final class WhitelistService{
	private const FILE = 'worldmanager_whitelist.json';

	private readonly Translator $lang;

	public function __construct(private readonly Server $server, private readonly WorldService $worlds){
		$this->lang = $worlds->getTranslator();
	}

	/**
	 * @throws WorldOperationException
	 */
	public function isEnabled(string $folderName): bool{
		return $this->read($folderName)['enabled'];
	}

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 *
	 * @throws WorldOperationException
	 */
	public function getPlayers(string $folderName): array{
		return $this->read($folderName)['players'];
	}

	/**
	 * @return int number of players that were moved out of the world
	 *
	 * @throws WorldOperationException
	 */
	public function setEnabled(string $folderName, bool $enabled): int{
		$world = $this->worlds->getLoaded($folderName);
		if($enabled && $world !== null && $this->worlds->isDefault($world)){
			throw new WorldOperationException($this->lang->get('error.whitelist_default', ['world' => $folderName]));
		}

		$players = $this->read($folderName)['players'];
		$this->write($folderName, $enabled, $players);

		return $enabled && $world !== null ? $this->evict($world, $players) : 0;
	}

	/**
	 * @throws WorldOperationException
	 */
	public function add(string $folderName, string $player): void{
		$name = $this->normalize($player);
		$data = $this->read($folderName);
		if(in_array($name, $data['players'], true)){
			throw new WorldOperationException($this->lang->get('error.whitelist_already_listed', ['player' => $player, 'world' => $folderName]));
		}

		$data['players'][] = $name;
		$this->write($folderName, $data['enabled'], $data['players']);
	}

	/**
	 * @return int number of players that were moved out of the world
	 *
	 * @throws WorldOperationException
	 */
	public function remove(string $folderName, string $player): int{
		$name = $this->normalize($player);
		$data = $this->read($folderName);
		$index = array_search($name, $data['players'], true);
		if($index === false){
			throw new WorldOperationException($this->lang->get('error.whitelist_not_listed', ['player' => $player, 'world' => $folderName]));
		}

		unset($data['players'][$index]);
		$this->write($folderName, $data['enabled'], $data['players']);

		$world = $this->worlds->getLoaded($folderName);

		return $data['enabled'] && $world !== null ? $this->evict($world, $data['players']) : 0;
	}

	public function isAllowed(Player $player, World $world): bool{
		if($this->worlds->isDefault($world)){
			return true;
		}

		try{
			$data = $this->read($world->getFolderName());
		}catch(WorldOperationException){
			//unreadable data must never grant access
			return false;
		}

		return !$data['enabled'] || in_array(strtolower($player->getName()), $data['players'], true);
	}

	/**
	 * @param string[] $allowed
	 * @phpstan-param list<string> $allowed
	 *
	 * @return int number of players that were moved to the default world
	 */
	private function evict(World $world, array $allowed): int{
		$default = $this->worlds->requireDefault();
		$moved = 0;
		foreach($world->getPlayers() as $player){
			if(!in_array(strtolower($player->getName()), $allowed, true) && $player->teleport($default->getSafeSpawn())){
				$player->sendMessage(TextFormat::YELLOW . $this->lang->get('player.whitelist_denied', ['world' => $world->getFolderName()]));
				++$moved;
			}
		}

		return $moved;
	}

	/**
	 * @throws WorldOperationException
	 */
	private function normalize(string $player): string{
		if(!Player::isValidUserName($player)){
			throw new WorldOperationException($this->lang->get('error.whitelist_player_invalid', ['player' => $player]));
		}

		return strtolower($player);
	}

	/**
	 * @return array<string, mixed>
	 * @phpstan-return array{enabled: bool, players: list<string>}
	 *
	 * @throws WorldOperationException
	 */
	private function read(string $folderName): array{
		$path = $this->getPath($folderName);
		if(!is_file($path)){
			return ['enabled' => false, 'players' => []];
		}

		$contents = @file_get_contents($path);
		$data = $contents === false ? null : json_decode($contents, true);
		if(!is_array($data) || !is_bool($data['enabled'] ?? null) || !is_array($data['players'] ?? null)){
			throw new WorldOperationException($this->lang->get('error.whitelist_read', ['world' => $folderName]));
		}

		return ['enabled' => $data['enabled'], 'players' => array_values(array_map(strtolower(...), array_filter($data['players'], is_string(...))))];
	}

	/**
	 * @param string[] $players
	 * @phpstan-param list<string> $players
	 *
	 * @throws WorldOperationException
	 */
	private function write(string $folderName, bool $enabled, array $players): void{
		$players = array_values(array_unique($players));
		sort($players);

		try{
			Filesystem::safeFilePutContents($this->getPath($folderName), json_encode(['enabled' => $enabled, 'players' => $players], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
		}catch(\RuntimeException | \JsonException $e){
			throw new WorldOperationException($this->lang->get('error.whitelist_write', ['world' => $folderName]), 0, $e);
		}
	}

	private function getPath(string $folderName): string{
		return Path::join($this->server->getDataPath(), 'worlds', $folderName, self::FILE);
	}
}
