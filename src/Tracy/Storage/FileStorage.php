<?php declare(strict_types=1);

namespace Tracy;

use Nette\Utils\FileSystem;

class FileStorage extends TracySession
{
	private string $tempDir;

	private ?string $storageFilePath = null;


	public function __construct(string $tempDir)
	{
		$this->tempDir = $tempDir;
	}


	public function save(?array $data, string ...$keys): void
	{
		$storageFilePath = $this->getStorageFilePath();

		$lockFile = $storageFilePath . '.lock';
		$lockHandle = $this->lock($lockFile);

		$storageData = $this->loadFromStorage();

		$target = &$storageData;
		foreach ($keys as $key) {
			$target = &$target[$key];
		}
		$target = $data;

		file_put_contents($storageFilePath, serialize($storageData));

		$this->unlock($lockFile, $lockHandle);
	}


	public function load(string ...$keys): array
	{
		if (!$this->isActive()) {
			return [];
		}

		$storageData = $this->loadFromStorage();

		foreach ($keys as $key) {
			$storageData = $storageData[$key] ?? [];
		}

		return $storageData;
	}


	private function loadFromStorage(): array
	{
		$storagePathPath = $this->getStorageFilePath(); // intentionally saved to the variable, we don't want to suppress errors by a @ operator
		$data = (string) @file_get_contents($storagePathPath); // intentionally @
		return @unserialize($data) ?: []; // intentionally @
	}


	private function getStorageFilePath(): string
	{
		if (!$this->isActive()) {
			throw new \RuntimeException('FileStorage is not activated.');
		}

		if ($this->storageFilePath === null) {
			FileSystem::createDir($this->tempDir);
			$this->storageFilePath = $this->tempDir . DIRECTORY_SEPARATOR . 'tracy-session-' . self::getSessionId();
		}

		return $this->storageFilePath;
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
