<?php

declare(strict_types=1);

namespace WorldManager\task;

use pocketmine\scheduler\AsyncTask;
use function is_dir;
use function is_file;
use function is_link;
use function rmdir;
use function unlink;

final class DeleteWorldTask extends AsyncTask{
	private const TLS_ON_COMPLETION = 'onCompletion';

	/**
	 * @phpstan-param \Closure(bool) : void $onCompletion
	 */
	public function __construct(private readonly string $path, \Closure $onCompletion){
		$this->storeLocal(self::TLS_ON_COMPLETION, $onCompletion);
	}

	public function onRun(): void{
		$this->setResult(self::deleteRecursively($this->path));
	}

	public function onCompletion(): void{
		/** @phpstan-var \Closure(bool) : void $onCompletion */
		$onCompletion = $this->fetchLocal(self::TLS_ON_COMPLETION);
		$onCompletion($this->getResult() === true);
	}

	public static function deleteRecursively(string $path): bool{
		//links are removed themselves, their targets are never touched
		if(is_link($path) || is_file($path)){
			return self::removeEntry($path);
		}
		if(!is_dir($path)){
			return true;
		}

		$success = true;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST,
		);
		foreach($iterator as $item){
			$success = self::removeEntry($item->getPathname()) && $success;
		}

		return @rmdir($path) && $success;
	}

	private static function removeEntry(string $path): bool{
		if(is_dir($path) && !is_link($path)){
			return @rmdir($path);
		}
		//directory symlinks on Windows need rmdir()
		return @unlink($path) || (is_link($path) && @rmdir($path));
	}
}
