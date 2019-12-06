<?php
namespace deceitya\updateinfo;

use pocketmine\form\Form;
use pocketmine\Player;
use deceitya\updateinfo\InfoFormClosedEvent;

class InfoForm implements Form
{
    private $formData;

    public function __construct(array $texts)
    {
        $this->formData = [
            'type' => 'custom_form',
            'title' => 'UpdateInfo',
            'content' => []
        ];
        foreach ($texts as $text) {
            $this->formData['content'][] = ['type' => 'label', 'text' => $text];
        }
    }

    public function handleResponse(Player $player, $data): void
    {
        (new InfoFormClosedEvent($player))->call();
    }

    public function jsonSerialize()
    {
        return $this->formData;
    }
}
