<?php declare(strict_types=1);

namespace Tracy;


class SessionStorage implements IStorage
{

	public function initialize(): void
	{
		ini_set('session.use_cookies', '1');
		ini_set('session.use_only_cookies', '1');
		ini_set('session.use_trans_sid', '0');
		ini_set('session.cookie_path', '/');
		ini_set('session.cookie_httponly', '1');
		session_start();
	}


	public function isActive(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}


	public function save(?array $data, string ...$keys): void
	{
		$target = &$_SESSION['_tracy'];
		foreach ($keys as $key) {
			$target = &$target[$key];
		}
		$target = $data;
	}


	public function load(string ...$keys): array
	{
		$target = $_SESSION['_tracy'] ?? [];
		foreach ($keys as $key) {
			$target = $target[$key] ?? [];
		}
		return $target;
	}
}
