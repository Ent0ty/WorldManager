<?php

declare(strict_types=1);

namespace WorldManager;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\generator\InvalidGeneratorOptionsException;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use Symfony\Component\Filesystem\Path;
use WorldManager\lang\Translator;
use WorldManager\task\CopyWorldTask;
use WorldManager\task\DeleteWorldTask;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function preg_match;
use function preg_replace_callback;
use function random_int;
use function rename;
use function scandir;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function unlink;
use function usort;
use const SCANDIR_SORT_NONE;

final class WorldService{
	private const NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/';

	/**
	 * @var true[]
	 * @phpstan-var array<string, true>
	 */
	private array $busy = [];

	public function __construct(private readonly Server $server, private readonly Translator $lang){}

	public function getTranslator(): Translator{
		return $this->lang;
	}

	/**
	 * Returns the folder names of all worlds that can be loaded or are loaded, sorted naturally.
	 *
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getAvailableNames(): array{
		$worldsPath = Path::join($this->server->getDataPath(), 'worlds');
		$entries = is_dir($worldsPath) ? scandir($worldsPath, SCANDIR_SORT_NONE) : false;
		if($entries === false){
			return [];
		}

		$manager = $this->server->getWorldManager();
		$names = [];
		foreach($entries as $entry){
			if(str_starts_with($entry, '.') || isset($this->busy[strtolower($entry)])){
				continue;
			}
			if(is_dir(Path::join($worldsPath, $entry)) && $manager->isWorldGenerated($entry)){
				$names[] = $entry;
			}
		}
		usort($names, 'strnatcasecmp');

		return $names;
	}

	/**
	 * Maps user input to the exact folder name of an existing world, ignoring case if there is no exact match.
	 */
	public function requireName(string $input): string{
		if(isset($this->busy[strtolower($input)])){
			throw new WorldOperationException($this->lang->get('error.world_busy', ['world' => $input]));
		}

		$fallback = null;
		foreach($this->getAvailableNames() as $name){
			if($name === $input){
				return $name;
			}
			if($fallback === null && strtolower($name) === strtolower($input)){
				$fallback = $name;
			}
		}

		return $fallback ?? throw new WorldOperationException($this->lang->get('error.world_not_found', ['world' => $input]));
	}

	public function getLoaded(string $folderName): ?World{
		return $this->server->getWorldManager()->getWorldByName($folderName);
	}

	public function requireLoaded(string $input): World{
		$folderName = $this->requireName($input);

		return $this->getLoaded($folderName) ?? throw new WorldOperationException($this->lang->get('error.world_not_loaded', ['world' => $folderName]));
	}

	public function isDefault(World $world): bool{
		return $world === $this->server->getWorldManager()->getDefaultWorld();
	}

	public function requireDefault(): World{
		return $this->server->getWorldManager()->getDefaultWorld() ?? throw new WorldOperationException($this->lang->get('error.no_default_world'));
	}

	public function create(string $name, string $generatorName, ?string $seed, string $preset, ?int $difficulty): World{
		$this->assertNameAvailable($name);

		$generators = GeneratorManager::getInstance();
		$entry = $generators->getGenerator($generatorName) ?? throw new WorldOperationException(
			$this->lang->get('error.generator_unknown', ['generator' => $generatorName, 'available' => implode(', ', $generators->getGeneratorList())]),
		);
		try{
			$entry->validateGeneratorOptions($preset);
		}catch(InvalidGeneratorOptionsException $e){
			throw new WorldOperationException($this->lang->get('error.preset_invalid', ['generator' => $generatorName, 'reason' => $e->getMessage()]), 0, $e);
		}

		$convertedSeed = $seed === null || strtolower($seed) === 'random' ? null : Generator::convertSeed($seed);
		$convertedSeed ??= random_int(Limits::INT32_MIN, Limits::INT32_MAX);

		$options = WorldCreationOptions::create()
			->setGeneratorClass($entry->getGeneratorClass())
			->setGeneratorOptions($preset)
			->setSeed($convertedSeed);
		$spawn = $entry->getSpawnPosition($convertedSeed);
		if($spawn !== null){
			$options->setSpawnPosition($spawn);
		}
		if($difficulty !== null){
			$options->setDifficulty($difficulty);
		}

		$manager = $this->server->getWorldManager();
		if(!$manager->generateWorld($name, $options)){
			throw new WorldOperationException($this->lang->get('error.create_failed', ['world' => $name]));
		}

		return $manager->getWorldByName($name) ?? throw new WorldOperationException($this->lang->get('error.create_not_loaded', ['world' => $name]));
	}

	public function load(string $input): World{
		$folderName = $this->requireName($input);
		if($this->getLoaded($folderName) !== null){
			throw new WorldOperationException($this->lang->get('error.world_already_loaded', ['world' => $folderName]));
		}

		$manager = $this->server->getWorldManager();
		if(!$manager->loadWorld($folderName)){
			throw new WorldOperationException($this->lang->get('error.load_failed', ['world' => $folderName]));
		}

		return $manager->getWorldByName($folderName) ?? throw new WorldOperationException($this->lang->get('error.load_not_found', ['world' => $folderName]));
	}

	/**
	 * @return int number of players that were moved to the default world
	 */
	public function unload(string $input): int{
		return $this->unloadSafely($this->requireLoaded($input), $this->lang->get('player.world_unloaded'));
	}

	/**
	 * Unloads the world if loaded, then removes its folder asynchronously.
	 *
	 * @phpstan-param \Closure(bool) : void $onCompletion
	 */
	public function delete(string $input, \Closure $onCompletion): void{
		$folderName = $this->requireName($input);
		$world = $this->getLoaded($folderName);
		if($world !== null){
			$this->unloadSafely($world, $this->lang->get('player.world_deleted'));
		}

		$key = strtolower($folderName);
		$this->busy[$key] = true;
		$this->server->getAsyncPool()->submitTask(new DeleteWorldTask(
			Path::join($this->server->getDataPath(), 'worlds', $folderName),
			function(bool $success) use ($key, $onCompletion): void{
				unset($this->busy[$key]);
				$onCompletion($success);
			},
		));
	}

	public function teleport(Player $player, string $input): World{
		$world = $this->requireLoaded($input);
		if(!$player->teleport($world->getSafeSpawn())){
			throw new WorldOperationException($this->lang->get('error.teleport_cancelled', ['player' => $player->getName()]));
		}

		return $world;
	}

	/**
	 * Makes the world the server's default world, now and after restarts, and sets its spawn point.
	 *
	 * @throws WorldOperationException
	 */
	public function setLobby(World $world, Vector3 $spawn): void{
		$folderName = $world->getFolderName();
		$this->writeDefaultWorldName($folderName);

		$this->server->getConfigGroup()->setConfigString('level-name', $folderName);
		$this->server->getWorldManager()->setDefaultWorld($world);
		$world->setSpawnLocation($spawn);
		$world->save(true);
	}

	private function writeDefaultWorldName(string $folderName): void{
		$path = Path::join($this->server->getDataPath(), 'server.properties');
		$contents = @file_get_contents($path);
		if($contents === false){
			throw new WorldOperationException($this->lang->get('error.properties_read'));
		}

		$line = "level-name=$folderName";
		$updated = preg_replace_callback('/^level-name[ \t]*=[^\r\n]*/m', static fn(): string => $line, $contents, 1, $count);
		if($updated === null){
			throw new WorldOperationException($this->lang->get('error.properties_process'));
		}
		if($count === 0){
			$updated .= ($contents === '' || str_ends_with($contents, "\n") ? '' : "\n") . $line . "\n";
		}

		//write to a temporary file first so a failed write cannot corrupt server.properties
		$temporaryPath = $path . '.tmp';
		if(@file_put_contents($temporaryPath, $updated) === false || !@rename($temporaryPath, $path)){
			@unlink($temporaryPath);
			throw new WorldOperationException($this->lang->get('error.properties_write'));
		}
	}

	/**
	 * Copies the world asynchronously, then loads the copy.
	 *
	 * @phpstan-param \Closure(World|WorldOperationException) : void $onCompletion
	 */
	public function duplicate(string $input, string $newName, \Closure $onCompletion): void{
		$source = $this->requireName($input);
		$this->assertNameAvailable($newName);

		$world = $this->getLoaded($source);
		$autoSave = $world !== null && $world->getAutoSave();
		if($world !== null){
			//flush pending changes and keep the files stable while they are copied
			$world->save(true);
			$world->setAutoSave(false);
		}

		$keys = [strtolower($source), strtolower($newName)];
		foreach($keys as $key){
			$this->busy[$key] = true;
		}
		$worldsPath = Path::join($this->server->getDataPath(), 'worlds');
		$this->server->getAsyncPool()->submitTask(new CopyWorldTask(
			Path::join($worldsPath, $source),
			Path::join($worldsPath, '.' . $newName . '.copying'),
			Path::join($worldsPath, $newName),
			function(bool $success) use ($keys, $world, $autoSave, $source, $newName, $onCompletion): void{
				foreach($keys as $key){
					unset($this->busy[$key]);
				}
				if($world !== null && $world->isLoaded()){
					$world->setAutoSave($autoSave);
				}
				if(!$success){
					$onCompletion(new WorldOperationException($this->lang->get('error.copy_failed', ['world' => $source])));
					return;
				}
				try{
					$copy = $this->load($newName);
					$this->syncDisplayName($copy, $source, $newName);
					$onCompletion($copy);
				}catch(WorldOperationException $e){
					$onCompletion($e);
				}
			},
		));
	}

	/**
	 * @return int number of players that were moved back into the renamed world
	 */
	public function rename(string $input, string $newName): int{
		$old = $this->requireName($input);
		if($old === $newName){
			throw new WorldOperationException($this->lang->get('error.rename_same_name'));
		}
		$this->assertNameAvailable($newName, $old);

		$world = $this->getLoaded($old);
		$locations = [];
		if($world !== null){
			if($this->isDefault($world)){
				throw new WorldOperationException($this->lang->get('error.rename_default'));
			}
			foreach($world->getPlayers() as $player){
				$locations[] = [$player, $player->getLocation()];
			}
			$this->unloadSafely($world, $this->lang->get('player.world_renamed'));
		}

		$worldsPath = Path::join($this->server->getDataPath(), 'worlds');
		$oldPath = Path::join($worldsPath, $old);
		$newPath = Path::join($worldsPath, $newName);
		try{
			if(!@rename($oldPath, $newPath)){
				throw new WorldOperationException($this->lang->get('error.rename_failed', ['world' => $old]));
			}
			try{
				$renamed = $this->load($newName);
			}catch(WorldOperationException $e){
				@rename($newPath, $oldPath);
				throw $e;
			}
		}catch(WorldOperationException $e){
			if($world !== null){
				try{
					$this->teleportBack($this->load($old), $locations);
				}catch(WorldOperationException){
					//players stay in the default world
				}
			}
			throw $e;
		}

		$this->syncDisplayName($renamed, $old, $newName);
		if($world === null){
			$this->server->getWorldManager()->unloadWorld($renamed);
		}else{
			$this->teleportBack($renamed, $locations);
		}
		return count($locations);
	}

	/**
	 * @phpstan-param list<array{Player, Location}> $locations
	 */
	private function teleportBack(World $world, array $locations): void{
		foreach($locations as [$player, $location]){
			if($player->isOnline()){
				$player->teleport(new Location($location->x, $location->y, $location->z, $world, $location->yaw, $location->pitch));
			}
		}
	}

	private function syncDisplayName(World $world, string $oldFolderName, string $newFolderName): void{
		if($world->getDisplayName() === $oldFolderName){
			$world->setDisplayName($newFolderName);
			$world->save(true);
		}
	}

	/**
	 * @param string|null $except existing world that is being renamed, may differ from $name in case only
	 *
	 * @throws WorldOperationException
	 */
	private function assertNameAvailable(string $name, ?string $except = null): void{
		if(preg_match(self::NAME_PATTERN, $name) !== 1){
			throw new WorldOperationException($this->lang->get('error.name_invalid'));
		}

		$key = strtolower($name);
		$caseOnlyChange = $except !== null && strtolower($except) === $key;
		$taken = isset($this->busy[$key]) || (!$caseOnlyChange && file_exists(Path::join($this->server->getDataPath(), 'worlds', $name)));
		foreach($this->getAvailableNames() as $existing){
			$taken = $taken || ($existing !== $except && strtolower($existing) === $key);
		}
		if($taken){
			throw new WorldOperationException($this->lang->get('error.name_taken', ['name' => $name]));
		}
	}

	private function unloadSafely(World $world, string $playerMessage): int{
		$manager = $this->server->getWorldManager();
		if($this->isDefault($world)){
			throw new WorldOperationException($this->lang->get('error.default_protected'));
		}

		$players = $world->getPlayers();
		if(count($players) > 0){
			$default = $manager->getDefaultWorld() ?? throw new WorldOperationException($this->lang->get('error.no_default_world'));
			foreach($players as $player){
				if(!$player->teleport($default->getSafeSpawn())){
					throw new WorldOperationException($this->lang->get('error.move_cancelled', ['player' => $player->getName(), 'world' => $world->getFolderName()]));
				}
				$player->sendMessage(TextFormat::YELLOW . $playerMessage);
			}
		}

		if(!$manager->unloadWorld($world)){
			throw new WorldOperationException($this->lang->get('error.unload_cancelled', ['world' => $world->getFolderName()]));
		}

		return count($players);
	}
}
