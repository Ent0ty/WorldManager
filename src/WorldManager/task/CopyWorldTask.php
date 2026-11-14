<?php

declare(strict_types=1);

namespace WorldManager\task;

use pocketmine\scheduler\AsyncTask;
use function copy;
use function is_dir;
use function mkdir;
use function rename;
use const DIRECTORY_SEPARATOR;

final class CopyWorldTask extends AsyncTask{
	private const TLS_ON_COMPLETION = 'onCompletion';

	/**
	 * @phpstan-param \Closure(bool) : void $onCompletion
	 */
	public function __construct(
		private readonly string $source,
		private readonly string $temporaryPath,
		private readonly string $target,
		\Closure $onCompletion,
	){
		$this->storeLocal(self::TLS_ON_COMPLETION, $onCompletion);
	}

	public function onRun(): void{
		//the copy is built in a temporary folder and renamed last, so a failure never leaves a half-finished world
		$success = DeleteWorldTask::deleteRecursively($this->temporaryPath)
			&& @mkdir($this->temporaryPath, 0777, true)
			&& $this->copyContents()
			&& @rename($this->temporaryPath, $this->target);
		if(!$success){
			DeleteWorldTask::deleteRecursively($this->temporaryPath);
		}
		$this->setResult($success);
	}

	private function copyContents(): bool{
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($this->source, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST,
		);
		foreach($iterator as $item){
			if($item->isLink()){
				continue;
			}
			$destination = $this->temporaryPath . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
			$copied = $item->isDir() ? (is_dir($destination) || @mkdir($destination, 0777)) : @copy($item->getPathname(), $destination);
			if(!$copied){
				return false;
			}
		}

		return true;
	}

	public function onCompletion(): void{
		/** @phpstan-var \Closure(bool) : void $onCompletion */
		$onCompletion = $this->fetchLocal(self::TLS_ON_COMPLETION);
		$onCompletion($this->getResult() === true);
	}
}
