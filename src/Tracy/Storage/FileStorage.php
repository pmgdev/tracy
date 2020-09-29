<?php declare(strict_types=1);

namespace Tracy;

use Nette\Utils\FileSystem;

class FileStorage implements IStorage
{
	private const COOKIE_NAME = 'tracy-session';

	/** @var string */
	private $tempDir;

	/** @var string|null */
	private $storageFilePath;


	public function __construct(string $tempDir)
	{
		FileSystem::createDir($tempDir);

		$this->tempDir = $tempDir;

		if (isset($_COOKIE[self::COOKIE_NAME])) {
			$sessionId = $_COOKIE[self::COOKIE_NAME];
		} else {
			$sessionId = uniqid();
			setcookie(self::COOKIE_NAME, $sessionId, time() + 7200, '/');
		}

		$this->storageFilePath = $this->tempDir . DIRECTORY_SEPARATOR . 'tracy-session-' . $sessionId;
	}


	public function initialize(): void
	{
	}


	public function isActive(): bool
	{
		return true;
	}


	public function save(?array $data, string ...$keys): void
	{
		$lockFile = $this->storageFilePath . '.lock';
		$lockHandle = $this->lock($lockFile);

		$storageData = $this->loadFromStorage();

		$target = &$storageData;
		foreach ($keys as $key) {
			$target = &$target[$key];
		}
		$target = $data;

		file_put_contents($this->storageFilePath, serialize($storageData));

		$this->unlock($lockFile, $lockHandle);
	}


	public function load(string ...$keys): array
	{
		$storageData = $this->loadFromStorage();

		foreach ($keys as $key) {
			$storageData = $storageData[$key] ?? [];
		}

		return $storageData;
	}


	private function loadFromStorage(): array
	{
		$data = (string) @file_get_contents($this->storageFilePath); // intentionally @
		return @unserialize($data) ?: []; // intentionally @
	}


	/**
	 * @param  string  $lockFile
	 * @return resource
	 */
	private function lock(string $lockFile)
	{
		$handle = @fopen($lockFile, 'c+'); // intentionally @
		if ($handle === FALSE) {
			throw new \RuntimeException("Unable to create file '$lockFile' " . error_get_last()['message']);
		} elseif (!@flock($handle, LOCK_EX)) { // intentionally @
			throw new \RuntimeException("Unable to acquire exclusive lock on '$lockFile' ", error_get_last()['message']);
		}
		return $handle;
	}


	/**
	 * @param  string    $lockFile
	 * @param  resource  $lockHandle
	 * @return void
	 */
	private function unlock(string $lockFile, $lockHandle): void
	{
		@flock($lockHandle, LOCK_UN); // intentionally @
		@fclose($lockHandle); // intentionally @
		@unlink($lockFile); // intentionally @
	}
}
