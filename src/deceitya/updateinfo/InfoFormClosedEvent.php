<?php
namespace deceitya\updateinfo;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class InfoFormClosedEvent extends PlayerEvent
{
    public function __construct(Player $player)
    {
        $this->player = $player;
    }
}
