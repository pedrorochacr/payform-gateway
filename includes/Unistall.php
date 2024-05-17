<?php

namespace LPT\Includes;

defined( 'ABSPATH' ) || exit;

class Unistall
{
	protected function __construct()
	{
	}

	public static function unistall()
	{
		flush_rewrite_rules();
	}
}