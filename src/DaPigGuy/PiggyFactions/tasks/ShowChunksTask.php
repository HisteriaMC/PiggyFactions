<?php

declare(strict_types=1);

namespace DaPigGuy\PiggyFactions\tasks;

use DaPigGuy\PiggyFactions\PiggyFactions;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\RedstoneParticle;

class ShowChunksTask extends Task
{
    public array $time = [];

    public function __construct(private PiggyFactions $plugin)
    {
    }

    public function onRun(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
            if (($member = $this->plugin->getPlayerManager()->getPlayer($p)) !== null && $member->canSeeChunks()) {
                if(!isset($this->time[$p->getName()])) $this->time[$p->getName()] = 0;
                $this->time[$p->getName()]++;
                if($this->time[$p->getName()] === 40){
                    $p->sendMessage($p->getLang()->ts("faction.showChunksTasks"));
                    $member->setCanSeeChunks(false);
                    unset($this->time[$p->getName()]);
                    break;
                }

                $chunkX = $p->getPosition()->getFloorX() >> Chunk::COORD_BIT_SIZE;
                $chunkZ = $p->getPosition()->getFloorZ() >> Chunk::COORD_BIT_SIZE;

                $minX = (float)$chunkX * 16;
                $maxX = (float)$minX + 16;
                $minZ = (float)$chunkZ * 16;
                $maxZ = (float)$minZ + 16;

                for ($x = $minX; $x <= $maxX; $x += 0.5) {
                    for ($z = $minZ; $z <= $maxZ; $z += 0.5) {
                        if ($x === $minX || $x === $maxX || $z === $minZ || $z === $maxZ) {
                            $p->getWorld()->addParticle(new Vector3($x, $p->getPosition()->y + 1.5, $z), new RedstoneParticle(), [$p]);
                        }
                    }
                }
            } else if (isset($member) && !$member->canSeeChunks() && isset($this->time[$p->getName()])){
                unset($this->time[$p->getName()]);
            }
        }
    }
}