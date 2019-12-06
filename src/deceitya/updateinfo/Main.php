<?php
namespace deceitya\updateinfo;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use deceitya\updateinfo\InfoFormClosedEvent;
use ORM;
use flowy\Flowy;

use function flowy\listen;

class Main extends PluginBase implements Listener
{
    public function onEnable()
    {
        require_once($this->getFile() . 'vendor/autoload.php');

        $this->reloadConfig();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        ORM::configure('sqlite:'. $this->getDataFolder() . 'version.db');
        ORM::configure('caching', true);
        ORM::configure('caching_auto_clear', true);

        ORM::get_db()->exec("CREATE TABLE IF NOT EXISTS history (id TEXT PRIMARY KEY, version INTEGER NOT NULL);");
    }

    private function readingFlow(Player $player)
    {
        Flowy::run($this, \Closure::fromCallable(function () use ($player) {
            $event = yield listen(PlayerJoinEvent::class)
                ->filter(function ($ev) use ($player) {
                    $history = ORM::for_table('history')->where('id', $ev->getPlayer()->getXuid())->find_one();
                    return $ev->getPlayer() === $player && ($history === false || (int) $history->version < (int) $this->getConfig()->get('version'));
                });

            $player->sendForm(new InfoForm($this->getConfig()->get('text')));

            $event = yield listen(InfoFormClosedEvent::class)
                ->filter(function ($ev) use ($player) {
                    return $ev->getPlayer() === $player;
                });

            $history = ORM::for_table('history')->where('id', $player->getXuid())->find_one();
            if ($history !== false) {
                $history->version = (int) $this->getConfig()->get('version');
            } else {
                $history = ORM::for_table('history')->create();
                $history->id = $player->getXuid();
                $history->version = (int) $this->getConfig()->get('version');
            }
            $history->save();
        }));
    }

    public function onPlayerLogin(PlayerLoginEvent $event)
    {
        $this->readingFlow($event->getPlayer());
    }
}
