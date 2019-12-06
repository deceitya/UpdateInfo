<?php
namespace deceitya\updateinfo;

require_once __DIR__ . '/../../../vendor/autoload.php';

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use deceitya\updateinfo\InfoFormClosedEvent;
use ORM;
use flowy\Flowy;

use function flowy\listen;

class Main extends PluginBase
{
    public function onEnable()
    {
        $this->reloadConfig();

        ORM::configure('sqlite:'. $this->getDataFolder() . 'version.db');
        ORM::configure('caching', true);
        ORM::configure('caching_auto_clear', true);
        $db = ORM::get_db();
        $db->exec("CREATE TABLE IF NOT EXISTS history (id TEXT PRIMARY KEY, version INTEGER NOT NULL);");

        Flowy::run($this, \Closure::fromCallable(function () {
            $event = yield listen(PlayerJoinEvent::class)
                ->filter(function ($ev) {
                    $history = ORM::for_table('history')->where('id', $ev->getPlayer()->getXuid())->find_one();
                    return $history === false || (int) $history->version < (int) $this->getConfig()->get('version');
                });
            $player = $event->getPlayer();
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
}
