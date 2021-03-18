<?php declare(strict_types=1);

namespace Tracy;

abstract class TracySession implements IStorage
{
	private const COOKIE_NAME = 'tracy-session';

	private static ?string $sessionId = null;


	public function initialize(): void
	{
		if (self::$sessionId === null) {
			if (isset($_COOKIE[self::COOKIE_NAME])) {
				self::$sessionId = $_COOKIE[self::COOKIE_NAME];
			} else {
				self::$sessionId = uniqid();
				setcookie(self::COOKIE_NAME, self::$sessionId, time() + 7200, '/');
			}
		}
	}


	public function isActive(): bool
	{
		return self::$sessionId !== null;
	}


	protected static function getSessionId(): ?string
	{
		return self::$sessionId;
	}
}
