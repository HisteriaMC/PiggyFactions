<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\commands\subcommands\claims\claim;

use DaPigGuy\PiggyFactions\commands\subcommands\FactionSubCommand;
use DaPigGuy\PiggyFactions\event\claims\ChunkOverclaimEvent;
use DaPigGuy\PiggyFactions\event\claims\ClaimChunkEvent;
use DaPigGuy\PiggyFactions\factions\Faction;
use DaPigGuy\PiggyFactions\players\FactionsPlayer;
use minicore\handler\Areas\AreaProtect;
use pocketmine\player\Player;

abstract class ClaimMultipleSubCommand extends FactionSubCommand
{
    protected ?string $parentNode = "claim";

    public function onNormalRun(Player $sender, ?Faction $faction, FactionsPlayer $member, string $aliasUsed, array $args): void
    {
        if (!in_array($sender->getWorld()->getFolderName(), $this->plugin->getConfig()->getNested("factions.claims.whitelisted-worlds"))) {
            $member->sendMessage("commands.claim.whitelisted-world");
            return;
        }
        if ($args["radius"] > 100) return; //This is so stupid so I just added this to prevent crashes, fuck messages

        $claimed = 0;
        $chunks = $this->getChunks($sender, $args);
        if (empty($chunks)) return;
        foreach ($chunks as $chunk) {
            $x = $chunk[0] * 16;
            $z = $chunk[1] * 16;
            if(!AreaProtect::canEdit($sender, $sender->getPosition()) ||
                (abs($x) <= 300 || abs($z) <= 300)) {
                $member->sendMessage("commands.claim.too-close-to-spawn");
                return;
            }

            if (!$member->isInAdminMode()) {
                if ($faction->getPower() / $this->plugin->getConfig()->getNested("factions.claim.cost", 1) < count($this->plugin->getClaimsManager()->getFactionClaims($faction)) + 1) {
                    break;
                }
                if (count($this->plugin->getClaimsManager()->getFactionClaims($faction)) >= ($max = $this->plugin->getConfig()->getNested("factions.claims.max", -1)) && $max !== -1) {
                    break;
                }
            }
            $claim = $this->plugin->getClaimsManager()->getClaim($chunk[0], $chunk[1], $sender->getWorld()->getFolderName());
            if ($claim !== null) {
                if (($claim->canBeOverClaimed() || $member->isInAdminMode()) && $claim->getFaction() !== $faction) {
                    $ev = new ChunkOverclaimEvent($faction, $member, $claim);
                    $ev->call();
                    if ($ev->isCancelled()) continue;

                    $claim->setFaction($faction);
                    $claimed++;
                    continue;
                }
            }

            $ev = new ClaimChunkEvent($faction, $member, $chunk[0], $chunk[1]);
            $ev->call();
            if ($ev->isCancelled()) continue;

            $this->plugin->getClaimsManager()->createClaim($faction, $sender->getWorld(), $chunk[0], $chunk[1]);
            $claimed++;
        }
        $member->sendMessage("commands.claim.claimed-multiple", ["{AMOUNT}" => $claimed, "{COMMAND}" => $this->getName()]);
    }

    abstract public function getChunks(Player $player, array $args): array;
}