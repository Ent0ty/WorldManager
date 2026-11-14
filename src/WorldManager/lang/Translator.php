<?php

declare(strict_types=1);

namespace WorldManager\lang;

use pocketmine\plugin\PluginBase;
use Symfony\Component\Filesystem\Path;
use function array_diff_key;
use function array_merge;
use function count;
use function fclose;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function preg_match;
use function stream_get_contents;
use function strtolower;
use function strtr;
use function trim;
use function yaml_parse;

final class Translator{
	public const DEFAULT_LANGUAGE = 'en';
	private const BUNDLED_LANGUAGES = [self::DEFAULT_LANGUAGE, 'de'];
	private const LANGUAGE_PATTERN = '/^[a-z]{2,3}(?:_[a-z]{2})?$/';

	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	private array $messages;

	/**
	 * @var array[]
	 * @phpstan-var list<array{string, array<string, string|int>}>
	 */
	private array $warnings = [];

	public function __construct(PluginBase $plugin, string $language){
		foreach(self::BUNDLED_LANGUAGES as $code){
			$plugin->saveResource("lang/$code.yml");
		}

		$default = $this->readBundled($plugin, self::DEFAULT_LANGUAGE) ?? [];
		$code = strtolower(trim($language));
		if(preg_match(self::LANGUAGE_PATTERN, $code) !== 1){
			$this->warnings[] = ['log.language_invalid', ['language' => $language]];
			$code = self::DEFAULT_LANGUAGE;
		}

		$bundled = $this->readBundled($plugin, $code);
		$custom = $this->readCustom($plugin, $code);
		if($bundled === null && $custom === null){
			$this->warnings[] = ['log.language_unknown', ['language' => $code]];
			$code = self::DEFAULT_LANGUAGE;
			$bundled = $default;
		}

		//later entries win, so missing keys fall back to the bundled English text
		$translated = array_merge($bundled ?? [], $custom ?? []);
		$this->messages = array_merge($default, $translated);

		$missing = count(array_diff_key($default, $translated));
		if($missing > 0){
			$this->warnings[] = ['log.language_keys_missing', ['language' => $code, 'count' => $missing]];
		}
		foreach($this->warnings as [$key, $params]){
			$plugin->getLogger()->warning($this->get($key, $params));
		}
		$this->warnings = [];
	}

	/**
	 * Returns the message for the key, or the key itself if no translation exists at all.
	 *
	 * @param array<string, string|int> $params values for the {name} placeholders
	 */
	public function get(string $key, array $params = []): string{
		$message = $this->messages[$key] ?? $key;
		$replacements = [];
		foreach($params as $name => $value){
			$replacements['{' . $name . '}'] = (string) $value;
		}

		return strtr($message, $replacements);
	}

	/**
	 * @return string[]|null
	 * @phpstan-return array<string, string>|null
	 */
	private function readBundled(PluginBase $plugin, string $code): ?array{
		$stream = $plugin->getResource("lang/$code.yml");
		if($stream === null){
			return null;
		}
		$contents = stream_get_contents($stream);
		fclose($stream);

		return self::parse($contents);
	}

	/**
	 * @return string[]|null
	 * @phpstan-return array<string, string>|null
	 */
	private function readCustom(PluginBase $plugin, string $code): ?array{
		$path = Path::join($plugin->getDataFolder(), 'lang', "$code.yml");
		if(!is_file($path)){
			return null;
		}
		$messages = self::parse(file_get_contents($path));
		if($messages === null){
			$this->warnings[] = ['log.language_file_invalid', ['file' => "lang/$code.yml"]];
		}

		return $messages;
	}

	/**
	 * @return string[]|null
	 * @phpstan-return array<string, string>|null
	 */
	private static function parse(string|false $contents): ?array{
		$data = $contents === false ? null : @yaml_parse($contents);
		if(!is_array($data)){
			return null;
		}

		$messages = [];
		foreach($data as $key => $value){
			if(is_string($value)){
				$messages[(string) $key] = $value;
			}
		}

		return $messages;
	}
}
