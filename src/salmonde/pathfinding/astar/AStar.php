<?php
declare(strict_types=1);

namespace salmonde\pathfinding\astar;

use pocketmine\math\Vector3;
use pocketmine\world\World;
use salmonde\pathfinding\Algorithm;
use salmonde\pathfinding\astar\selector\NeighbourSelector;
use salmonde\pathfinding\astar\selector\NeighbourSelectorXYZ;
use salmonde\pathfinding\PathResult;
use function abs;

class AStar extends Algorithm {
	private NodeHeap $openListHeap;
	private array $openList;
	private array $closedList;

	private NeighbourSelector $neighbourSelector;
	private CostCalculator $costCalculator;

	public function __construct(World $world, Vector3 $startPos, Vector3 $targetPos) {
		parent::__construct($world, Node::fromVector3($startPos), Node::fromVector3($targetPos));
		$this->neighbourSelector = new NeighbourSelectorXYZ();
		$this->costCalculator = new DefaultCostCalculator();
	}

	public function resetPathResult() : void {
		parent::resetPathResult();
		$this->setTargetPos($this->getTargetPos());
		$this->setStartPos($this->getStartPos());
	}

	public function setTargetPos(Vector3 $targetPos) : void {
		$node = Node::fromVector3($targetPos);
		$node->setH(0.0);
		parent::setTargetPos($node);
	}

	public function setStartPos(Vector3 $startPos) : void { parent::setStartPos(Node::fromVector3($startPos)); }

	public function getCostCalculator() : CostCalculator { return $this->costCalculator; }

	public function setCostCalculator(CostCalculator $costCalculator) : void { $this->costCalculator = $costCalculator; }

	public function tick() : void {
		$currentNode = $this->openListHeap->extract();

		if ($currentNode->equals($this->getTargetPos())) {
			$this->getTargetPos()->setPredecessor($currentNode);
			$this->reset();
			$this->parsePath();
			return;
		}

		$hash = World::blockHash($currentNode->x, $currentNode->y, $currentNode->z);
		unset($this->openList[$hash]);
		$this->closedList[$hash] = $currentNode;

		$block = $this->getWorld()->getBlockAt($currentNode->x, $currentNode->y, $currentNode->z);

		foreach ($this->getNeighbourSelector()->getNeighbours($block) as $side => $neighbourBlock) {
			$neighbourBlockPos = $neighbourBlock->getPosition();
			if (!$this->isValidBlock($neighbourBlock, $side) || isset($this->closedList[$neighbourHash = World::blockHash($neighbourBlockPos->x, $neighbourBlockPos->y, $neighbourBlockPos->z)])) {
				continue;
			}

			$inOpenList = isset($this->openList[$neighbourHash]);
			$neighbourNode = $this->openList[$neighbourHash] ?? Node::fromVector3($neighbourBlockPos);

			$cost = $this->costCalculator->getCost($neighbourBlock);
			if (!$inOpenList || $currentNode->getG() + $cost < $neighbourNode->getG()) {
				$neighbourNode->setG($currentNode->getG() + $cost);
				$neighbourNode->setH($this->calculateEstimatedCost($neighbourBlockPos));
				$neighbourNode->setPredecessor($currentNode);

				if (!$inOpenList) {
					$this->openList[$neighbourHash] = $neighbourNode;
					$this->openListHeap->insert($neighbourNode);
				}
			}
		}
	}

	public function reset() : void {
		$this->openListHeap = new NodeHeap();
		$this->openList = [];
		$this->closedList = [];

		$startPos = $this->getStartPos();
		assert($startPos instanceof Node);
		$startPos->setG(0.0);
		$startPos->setH($this->calculateEstimatedCost($startPos));
		$this->openList[World::blockHash($startPos->x, $startPos->y, $startPos->z)] = $startPos;
		$this->openListHeap->insert($startPos);
	}

	public function calculateEstimatedCost(Vector3 $pos) : float {
		$targetPos = $this->getTargetPos();
		return abs($pos->x - $targetPos->x) + abs($pos->y - $targetPos->y) + abs($pos->z - $targetPos->z);
	}

	protected function parsePath() : void {
		$pathResult = new PathResult();
		$pos = $this->getTargetPos();
		assert($pos instanceof Node);
		$currentNode = $pos->getPredecessor() ?? throw new \RuntimeException(); // prevent duplicate entry

		do {
			$currentNode = $currentNode->getPredecessor();
			if ($currentNode instanceof Node) {
				$pathResult->unshift($currentNode);
			} else {
				break;
			}
		} while (true);

		$this->setPathResult($pathResult);
	}

	public function getNeighbourSelector() : NeighbourSelector { return $this->neighbourSelector; }

	public function setNeighbourSelector(NeighbourSelector $neighbourSelector) : void { $this->neighbourSelector = $neighbourSelector; }

	public function isFinished() : bool { return $this->getPathResult() instanceof PathResult or $this->openListHeap->isEmpty(); }
}
