<?php

namespace App\Story;

use App\Factory\MaxfieldFactory;
use App\Factory\UserFactory;
use App\Factory\WaypointFactory;
use Zenstruck\Foundry\Story;

final class DefaultFixturesStory extends Story
{
    public function build(): void
    {
        $user = UserFactory::createOne(['identifier' => 'user']);

        UserFactory::new()->asAdmin()->create(['identifier' => 'admin']);

        WaypointFactory::createOne(['guid' => 'test']);

        MaxfieldFactory::createOne([
            'name' => 'test',
            'path' => 'test',
            'owner' => $user,
        ]);
    }
}
