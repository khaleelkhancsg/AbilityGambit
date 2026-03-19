<?php

namespace App\Services;

class AbilityService
{
    public const ABILITIES = [
        'super_pawn' => [
            'name' => 'Super Pawn',
            'type' => 'active',
            'icon' => '⚡',
            'description' => 'Your pawns can capture pieces directly in front of them for one turn.',
        ],
        'reinforced_walls' => [
            'name' => 'Reinforced Walls',
            'type' => 'passive',
            'icon' => '🛡️',
            'description' => 'Your Rooks are indestructible to enemy Pawns. (Passive: Always Active)',
        ],
        'teleport' => [
            'name' => 'Teleport',
            'type' => 'targeted',
            'icon' => '🌀',
            'description' => 'Move any of your Knights to any empty square on the board.',
        ],
    ];

    public function getRandomAbility(): string
    {
        $keys = array_keys(self::ABILITIES);
        return $keys[array_rand($keys)];
    }

    public function getAvailableAbilities(): array
    {
        return self::ABILITIES;
    }
}
