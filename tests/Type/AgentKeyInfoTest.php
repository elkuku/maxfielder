<?php

declare(strict_types=1);

namespace App\Tests\Type;

use App\Type\AgentKeyInfo;
use PHPUnit\Framework\TestCase;

final class AgentKeyInfoTest extends TestCase
{
    public function testPropertyAssignment(): void
    {
        $info = new AgentKeyInfo();
        $info->guid = 'guid-123';
        $info->name = 'Portal Alpha';
        $info->link = 'https://example.com';
        $info->count = 7;
        $info->capsules = 'C001, C002';

        $this->assertSame('guid-123', $info->guid);
        $this->assertSame('Portal Alpha', $info->name);
        $this->assertSame('https://example.com', $info->link);
        $this->assertSame(7, $info->count);
        $this->assertSame('C001, C002', $info->capsules);
    }
}
