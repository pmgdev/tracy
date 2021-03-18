<?php declare(strict_types=1);

namespace Tracy;

class ApcuStorage extends TracySession
{
	private const NAMESPACE = 'tracy.';
	private const LOCK_EXPIRE_IN_SECONDS = 10;

	private ?string $key = null;


	public function isActive(): bool
	{
		return extension_loaded('apcu') && parent::isActive();
	}


	public function save(?array $data, string ...$keys): void
	{
		$storageKey = $this->getKey();

		$this->lock($storageKey);

		$storageData = $this->loadFromStorage();

		$target = &$storageData;
		foreach ($keys as $key) {
			$target = &$target[$key];
		}
		$target = $data;

		apcu_store($storageKey, $storageData, 60);

		$this->unlock($storageKey);
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
		$data = apcu_fetch($this->getKey());
		return $data === FALSE ? [] : $data;
	}


	private function lock(string $key): void
	{
		$lockKey = $key . '#lock';

		$retryCount = 2000;
		do {
			if (!apcu_exists($lockKey) || (time() > apcu_fetch($lockKey))) {
				$timeout = time() + self::LOCK_EXPIRE_IN_SECONDS;
				apcu_store($lockKey, $timeout, self::LOCK_EXPIRE_IN_SECONDS);
				return;
			}
			usleep(5000);
		} while ((--$retryCount > 0));

		throw new \RuntimeException('ApcuStorage: can\'t get lock.');
	}


	private function unlock(string $key): void
	{
		apcu_delete($key . '#lock');
	}


	private function getKey(): string
	{
		if (!$this->isActive()) {
			throw new \RuntimeException('ApcuStorage is not activated');
		}

		if ($this->key === null) {
			$this->key = self::NAMESPACE . '.' . self::getSessionId();
		}

		return $this->key;
	}
}
