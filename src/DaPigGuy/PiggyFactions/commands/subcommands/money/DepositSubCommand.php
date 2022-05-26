<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\commands\subcommands\money;

use CortexPE\Commando\args\FloatArgument;
use DaPigGuy\PiggyFactions\commands\subcommands\FactionSubCommand;
use DaPigGuy\PiggyFactions\factions\Faction;
use DaPigGuy\PiggyFactions\players\FactionsPlayer;
use minicore\api\EconomyAPI;
use minicore\CustomPlayer;
use pocketmine\player\Player;

class DepositSubCommand extends FactionSubCommand
{
    public function onNormalRun(Player $sender, ?Faction $faction, FactionsPlayer $member, string $aliasUsed, array $args): void
    {
        if ($args["money"] < 0) {
            $member->sendMessage("economy.negative-money");
            return;
        }
        /** @var CustomPlayer $sender */
        $sender->getMoney(function(float $money, int $state) use ($args, $member, $sender, $faction) {
            if($state === EconomyAPI::SUCCESS) {
                if ($money < $args["money"]) {
                    $member->sendMessage("economy.not-enough-money", ["{DIFFERENCE}" => $args["money"] - $money]);
                    return;
                }
                $sender->takeMoney($args["money"], function (int $state) use ($member, $args, $faction) {
                    if ($state !== EconomyAPI::SUCCESS) {
                        $member->sendMessage("generic-error");
                        return;
                    }
                    $faction->addMoney($args["money"]);
                    $member->sendMessage("commands.deposit.success", ["{MONEY}" => $args["money"]]);
                });
            }
        });
        $this->plugin->getEconomyProvider()->getMoney($sender, function (float|int $balance) use ($args, $member, $sender, $faction) {


        });
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new FloatArgument("money"));
    }
}