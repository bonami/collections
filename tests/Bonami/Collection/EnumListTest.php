<?php declare(strict_types = 1);

namespace Bonami\Collection;

use PHPUnit\Framework\TestCase;

class EnumListTest extends TestCase {

	public function testFromOnly(): void {
		$item = DummyEnum::create(DummyEnum::HI);

		$this->assertEquals(
			new EnumList([$item]),
			EnumList::of($item)
		);
	}

}

/* @codingStandardsIgnoreStart */
class DummyEnum extends Enum {
	public const HI = 'hi';
}
/* @codingStandardsIgnoreEnd */
