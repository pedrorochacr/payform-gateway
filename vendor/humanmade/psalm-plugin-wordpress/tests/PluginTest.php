<?php

namespace PsalmWordPress\Tests;

use PHPUnit\Framework\TestCase;
use PsalmWordPress\Plugin;
use Psalm\Plugin\RegistrationInterface;

class PluginTest extends TestCase {

	/**
	 * @var ObjectProphecy
	 */
	private $registration;

	/**
	 * @return void
	 */
	public function setUp() : void {
		$this->registration = $this->prophesize( RegistrationInterface::class );
	}

	/**
	 * @test
	 */
	public function hasEntryPoint() : void {
		$this->expectNotToPerformAssertions();
		$plugin = new Plugin();
		$plugin( $this->registration->reveal(), null );
	}
}
