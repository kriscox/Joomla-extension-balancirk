<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Library
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Library;

use PHPUnit\Framework\TestCase;
use Joomlaology\Traits\ApiTools;

/**
 * Test class for the ApiTools shared trait.
 *
 * Covers prepErrMsgExPldFmt() — the payload-encoding helper used by API
 * controllers to produce human-readable error messages that describe the
 * expected request format.
 *
 * @since  1.2.29
 */
class ApiToolsTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        $this->subject = new class {
            use ApiTools;
        };
    }

    public function testLiteralModeReturnsPayloadUnchanged(): void
    {
        $result = $this->subject->prepErrMsgExPldFmt('test string', 'literal');

        $this->assertSame('test string', $result);
    }

    public function testOnlyEncUriModeWrapsPayloadInEncodeURIComponent(): void
    {
        $result = $this->subject->prepErrMsgExPldFmt('my payload', 'onlyEncUri');

        $this->assertSame("encodeURIComponent( 'my payload' )", $result);
    }

    public function testEncB64AndUriModeWrapsPayloadInBtoaAndEncodeURIComponent(): void
    {
        $result = $this->subject->prepErrMsgExPldFmt('my payload', 'encB64AndUri');

        $this->assertSame("encodeURIComponent( btoa( 'my payload' ) )", $result);
    }

    public function testUnknownModeDefaultsToReturningPayloadUnchanged(): void
    {
        $result = $this->subject->prepErrMsgExPldFmt('some value', 'unknownMode');

        $this->assertSame('some value', $result);
    }

    public function testNonStringPayloadReturnsNotSuppliedMessage(): void
    {
        $result = $this->subject->prepErrMsgExPldFmt(42, 'literal');

        $this->assertSame('Payload & Payload mode NOT supplied.', $result);
    }

    public function testNonStringModeReturnsNotSuppliedMessage(): void
    {
        $result = $this->subject->prepErrMsgExPldFmt('payload', 999);

        $this->assertSame('Payload & Payload mode NOT supplied.', $result);
    }

    public function testBothNonStringArgsReturnNotSuppliedMessage(): void
    {
        $result = $this->subject->prepErrMsgExPldFmt(42, ['array', 'mode']);

        $this->assertSame('Payload & Payload mode NOT supplied.', $result);
    }
}
