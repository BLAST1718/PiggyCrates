<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyCrates;

use DaPigGuy\PiggyCrates\tiles\CrateTile;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Chest;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class EventListener implements Listener
{
    /** @var PiggyCrates */
    private $plugin;

    public function __construct(PiggyCrates $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $world = $block->getPos()->getWorld();
        $item = $player->getInventory()->getItemInHand();
        if ($block->getId() === BlockLegacyIds::CHEST) {
            $tile = $world->getTile($block->getPos());
            if ($tile instanceof CrateTile) {
                if ($tile->getCrateType() === null) {
                    $player->sendTip($this->plugin->getMessage("crates.error.invalid-crate"));
                } elseif ($tile->getCrateType()->isValidKey($item)) {
                    $tile->openCrate($player, $item);
                } elseif ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                    $tile->previewCrate($player);
                }
                $event->setCancelled();
                return;
            }
            if ($tile instanceof Chest) {
                if (($crate = $this->plugin->getCrateToCreate($player)) !== null) {
                    $newTile = new CrateTile($world, $block->getPos());
                    $newTile->setCrateType($crate);
                    $world->addTile($newTile);
                    $tile->close();
                    $player->sendMessage($this->plugin->getMessage("crates.success.crate-created", ["{CRATE}" => $crate->getName()]));
                    $this->plugin->setInCrateCreationMode($player, null);
                    $event->setCancelled();
                    return;
                }
            }
        }
        if ($item->getNamedTag()->getTag("KeyType") !== null) $event->setCancelled();
    }
}