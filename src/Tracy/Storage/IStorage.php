<?php declare(strict_types=1);

namespace Tracy;


interface IStorage
{

	function initialize(): void;


	function isActive(): bool;


	function save(?array $data, string ...$key): void;


	function load(string ...$keys): array;
}
