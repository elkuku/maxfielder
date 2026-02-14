<?php

namespace App\Tests\Service;

use App\Service\IngressHelper;
use App\Service\WayPointHelper;
use App\Type\WaypointMap;
use PHPUnit\Framework\TestCase;

class IngressHelperTest extends TestCase
{
    private IngressHelper $helper;

    protected function setUp(): void
    {
        $wayPointHelper = new WayPointHelper('/tmp/test', 'https://intel.ingress.com/intel');
        $this->helper = new IngressHelper($wayPointHelper);
    }

    public function testParseKeysStringWithNewlines(): void
    {
        $input = "Header\tCol2\tCol3\tCol4\tCol5\n"
            . "Portal A\tlink1\tguid1\t3\tcapsule1\n"
            . "Portal B\tlink2\tguid2\t5\tcapsule2";

        $keys = $this->helper->parseKeysString($input);

        self::assertCount(2, $keys);
        self::assertSame('Portal A', $keys[0]->name);
        self::assertSame('link1', $keys[0]->link);
        self::assertSame('guid1', $keys[0]->guid);
        self::assertSame(3, $keys[0]->count);
        self::assertSame('capsule1', $keys[0]->capsules);
        self::assertSame('Portal B', $keys[1]->name);
        self::assertSame(5, $keys[1]->count);
    }

    public function testParseKeysStringWithCarriageReturnNewlines(): void
    {
        $input = "Header\tCol2\tCol3\tCol4\tCol5\r\n"
            . "Portal C\tlink3\tguid3\t7\tcapsule3\r\n"
            . "Portal D\tlink4\tguid4\t2\tcapsule4";

        $keys = $this->helper->parseKeysString($input);

        self::assertCount(2, $keys);
        self::assertSame('Portal C', $keys[0]->name);
        self::assertSame('guid3', $keys[0]->guid);
        self::assertSame('Portal D', $keys[1]->name);
    }

    public function testParseKeysStringCleansNames(): void
    {
        $input = "Header\tCol2\tCol3\tCol4\tCol5\n"
            . "Café, Niño.\tlink\tguid\t1\tcap";

        $keys = $this->helper->parseKeysString($input);

        self::assertSame('Cafe Ninio', $keys[0]->name);
    }

    public function testParseKeysStringThrowsOnInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid keys string!');

        $input = "Header\n"
            . "only\ttwo\tcolumns";

        $this->helper->parseKeysString($input);
    }

    public function testParseKeysStringEmptyReturnsEmpty(): void
    {
        $input = "Header\tCol2\tCol3\tCol4\tCol5";

        $keys = $this->helper->parseKeysString($input);

        self::assertCount(0, $keys);
    }

    public function testGetExistingKeysForMaxfield(): void
    {
        $wp1 = new WaypointMap();
        $wp1->mapNo = 0;
        $wp1->dbId = 1;
        $wp1->guid = 'guid1';
        $wp1->name = 'Portal A';

        $wp2 = new WaypointMap();
        $wp2->mapNo = 1;
        $wp2->dbId = 2;
        $wp2->guid = 'guid3';
        $wp2->name = 'Portal C';

        $keysString = "Header\tCol2\tCol3\tCol4\tCol5\n"
            . "Portal A\tlink1\tguid1\t3\tcap1\n"
            . "Portal B\tlink2\tguid2\t5\tcap2\n"
            . "Portal C\tlink3\tguid3\t7\tcap3";

        $result = $this->helper->getExistingKeysForMaxfield([$wp1, $wp2], $keysString);

        self::assertCount(2, $result);
        self::assertSame('guid1', $result[0]->guid);
        self::assertSame('guid3', $result[1]->guid);
    }

    public function testGetExistingKeysForMaxfieldNoMatches(): void
    {
        $wp = new WaypointMap();
        $wp->mapNo = 0;
        $wp->dbId = 1;
        $wp->guid = 'no-match';
        $wp->name = 'No Match';

        $keysString = "Header\tCol2\tCol3\tCol4\tCol5\n"
            . "Portal A\tlink1\tguid1\t3\tcap1";

        $result = $this->helper->getExistingKeysForMaxfield([$wp], $keysString);

        self::assertCount(0, $result);
    }
}
