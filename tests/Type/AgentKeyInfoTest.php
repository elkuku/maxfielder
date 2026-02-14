<?php

namespace App\Tests\Type;

use App\Type\AgentKeyInfo;
use PHPUnit\Framework\TestCase;

class AgentKeyInfoTest extends TestCase
{
    public function testPropertyAssignment(): void
    {
        $info = new AgentKeyInfo();
        $info->guid = 'guid-123';
        $info->name = 'Portal Alpha';
        $info->link = 'https://example.com';
        $info->count = 7;
        $info->capsules = 'C001, C002';

        self::assertSame('guid-123', $info->guid);
        self::assertSame('Portal Alpha', $info->name);
        self::assertSame('https://example.com', $info->link);
        self::assertSame(7, $info->count);
        self::assertSame('C001, C002', $info->capsules);
    }
}
