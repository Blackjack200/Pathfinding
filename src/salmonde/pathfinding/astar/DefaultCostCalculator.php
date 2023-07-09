<?php
declare(strict_types=1);

namespace salmonde\pathfinding\astar;

use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds as Ids;

class DefaultCostCalculator extends CostCalculator {
	public function getCost(Block $block) : float {
		switch ($block->getTypeId()) {
			case Ids::LAVA:
				return 10.0;
			case Ids::WATER:
				//case Ids::STILL_WATER:
				return 2.0;
			case Ids::COBWEB:
				return 3.0;
			default:
				return 1.0;
		}
	}
}
