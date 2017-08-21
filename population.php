<?php

/* Defines how and what to populate the dungeon with 

	In the items array, we add all the items that we want to be spawnable, and how many of them. When spawned, added as loot or the likes, we remove them from the items array.
*/
$rarity_ladder = ["common", "uncommon", "strong", "epic", "legendary"];

$limited_items=[
	new shardOfFjallrung1(),
	new shardOfFjallrung2(),
	new shardOfFjallrung3(),
];
$generic_items=[
	new permanentHealthboost(1),
	new shortBow(),
	new shortSword(),
	new longBow(),
	new chainBreastplate(),
	new leatherArmor(),
	new skullArmor(),
	new platemail(),
	new chainmail(),
	new longSword()
];

// Shuffle item arrays for extra randomization.
shuffle($limited_items);
shuffle($generic_items);

function getItem($min_rarity = "common", $max_rarity = "legendary", $limited_only = false, $generic_only = false, $loot_chance = 1)
{
	global $generic_items, $limited_items, $rarity_ladder;

	$min_rarity_key = array_search($min_rarity, $rarity_ladder);
	$max_rarity_key = array_search($max_rarity, $rarity_ladder);
	if($limited_only)
	{
		$limited_or_generic = 61;
	} elseif($generic_only)
	{
		$limited_or_generic = 59;
	} else {
		$limited_or_generic = rand(0,100); // 40% chance of limited items, 60% chance of generic items.
	}
	
	if($limited_or_generic > 60 && count($limited_items) != 0)
	{
		$item_key = array_rand($limited_items, 1);
		$rarity_key = array_search($limited_items[$item_key]->rarity, $rarity_ladder);

		while($rarity_key < $min_rarity_key OR $rarity_key > $max_rarity_key)
		{
			$item_key = array_rand($limited_items, 1);
			$rarity_key = array_search($limited_items[$item_key]->rarity, $rarity_ladder);
		}
		if(isset($limited_items[$item_key]))
		{
			$item = $limited_items[$item_key];
		} else {
			return getItem($min_rarity, $max_rarity, false, true);
		}
		unset($limited_items[$item_key]);
		$limited_items = array_values($limited_items);
	} elseif($limited_or_generic <= 60 || count($limited_items) == 0) {
		$item_key = array_rand($generic_items, 1);
		$rarity_key = array_search($generic_items[$item_key]->rarity, $rarity_ladder);

		while($rarity_key < $min_rarity_key OR $rarity_key > $max_rarity_key)
		{
			$item_key = array_rand($generic_items, 1);
			$rarity_key = array_search($generic_items[$item_key]->rarity, $rarity_ladder);
		}
		
		$item = $generic_items[$item_key];
	}
	if($loot_chance != 1)
	{
		$chance = rand(1,100);
		$chance = $chance/100;
		if($chance <= $loot_chance)
		{
			return clone $item;
		} else {
			return null;
		}
	}
	return clone $item;
}

$spawnable_mobs =[
	new archerBandit(),
	new bandit(),
	new oakOwl(),
	new wolfbat(),
	new wyvern(),
	new bannerBear()
];

function populateMap()
{

	/*
		Rules of room distribution.

		30% treasure rooms, with 8/22 distribution of great/common rooms
		60% mob rooms
		10% vacant for spawns, specialty and so on

	*/

	global $map, $rooms, $predefinedClasses, $spawnable_mobs;
	echo "Populating map.\n";
	foreach($rooms as &$room)
	{
		$dist = rand(0,100);
		if($dist <= 20)
		{
			$alsomob = rand(0,100);
			if($alsomob >= 50)
			{
				mobRoom($room);
			}
			treasureRoom($room);
		} elseif($dist > 20 && $dist <= 90)
		{
			mobRoom($room);
		} else {
			vacantRoom($room);
		}

	}
	echo "Map population done.\n\n----------------------------------------------------------\n|                                                        |\n|      __                          _                     |\n|     |  \    _  _  _ _  _  _   _ (_   /\  _      _      |\n|     |__/|_|| )(_)(-(_)| )_)  (_)|   /--\| \/|_||||     |\n|               _/                                       |\n|                                                        |\n|                                                        |\n|          The first real multiplayer roguelike          |\n|                                                        |\n|                by: Simon Klit-Johnson                  |\n|                                                        |\n----------------------------------------------------------\n\nReady to connect!\n\n";
}

function vacantRoom($room)
{
	global $vacant_rooms;
	array_push($vacant_rooms, $room);
	return true;
}

function treasureRoom($room)
{
	$itemdist = rand(0,100);
	if($itemdist < 60)
	{
		$xcoord = rand($room["_x1"], $room["_x2"]);
		$ycoord = rand($room["_y1"], $room["_y2"]);
		$spotorpot = rand(0,10);
		if($spotorpot >= 5)
		{
			setTile($xcoord, $ycoord, new Tile(new Treasure()));
		} else {
			setTile($xcoord, $ycoord, new Tile(new healthpotTile(rand(1,3))));
		}
	} elseif($itemdist > 60 && $itemdist < 75)
	{
		$xcoord = rand($room["_x1"], $room["_x2"]);
		$ycoord = rand($room["_y1"], $room["_y2"]);
		$spotorpot = rand(0,10);
		if($spotorpot > 7)
		{
			setTile($xcoord, $ycoord, new Tile(new Healspot()));
		} else {
			setTile($xcoord, $ycoord, new Tile(new healthpotTile(rand(1,5))));
		}
	} 
	elseif($itemdist >= 75)
	{
		$xcoord = rand($room["_x1"], $room["_x2"]);
		$ycoord = rand($room["_y1"], $room["_y2"]);
		$spotorpot = rand(0,10);
		if($spotorpot >= 5)
		{
			setTile($xcoord, $ycoord, new Tile(new Greattreasure()));
		} else {
			setTile($xcoord, $ycoord, new Tile(new healthpotTile(rand(1,10))));
		}
	}
}

function mobRoom($room)
{
	global $map, $rooms, $predefinedClasses, $spawnable_mobs;
		$mob_selected = false;
		while(!$mob_selected)
		{
			$mobtype = rand(0,100);
			if($mobtype <= 45) // Common
			{
				
				$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
				$ran = 0;
				while ($curmob->rarity != "common" and $ran < 30){
					$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
					$ran++;
				}
				$curmobclass = get_class($curmob);
				mobclass;
				$curmob = new $curmobclass;
				spawnMob($curmob, ($room["_x2"]-(($room["_x2"]-$room["_x1"])/2)), ($room["_y2"]-(($room["_y2"]-$room["_y1"])/2)));
			} elseif($mobtype <= 70 && $mobtype > 45) // Uncommon
			{
	            
				$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
				$ran = 0;
				while ($curmob->rarity != "uncommon" and $ran < 30){
					$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
					$ran++;
				}
				$curmobclass = get_class($curmob);
				mobclass;
				$curmob = new $curmobclass;
				spawnMob($curmob, ($room["_x2"]-(($room["_x2"]-$room["_x1"])/2)), ($room["_y2"]-(($room["_y2"]-$room["_y1"])/2)));
			} elseif($mobtype >= 70 && $mobtype < 85) // Strong
			{
	            
				$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
				$ran = 0;
				while ($curmob->rarity != "strong" and $ran < 30){
					$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
					$ran++;
				}
				$curmobclass = get_class($curmob);
				mobclass;
				$curmob = new $curmobclass;
				spawnMob($curmob, ($room["_x2"]-(($room["_x2"]-$room["_x1"])/2)), ($room["_y2"]-(($room["_y2"]-$room["_y1"])/2)));
			} elseif($mobtype >= 85 && $mobtype <= 95) // Epic
			{
	            
				$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
				$ran = 0;
				while ($curmob->rarity != "epic" and $ran < 30){
					$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
					$ran++;
				}
				$curmobclass = get_class($curmob);
				mobclass;
				$curmob = new $curmobclass;
				spawnMob($curmob, ($room["_x2"]-(($room["_x2"]-$room["_x1"])/2)), ($room["_y2"]-(($room["_y2"]-$room["_y1"])/2)));
			}elseif($mobtype <= 100 && $mobtype > 95) // Legendary
			{
	            
				$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
				$ran = 0;
				while ($curmob->rarity != "legendary" and $ran < 30){
					$curmob = $spawnable_mobs[array_rand($spawnable_mobs, 1)];
					$ran++;
				}
				$curmobclass = get_class($curmob);
				mobclass;
				$curmob = new $curmobclass;
				spawnMob($curmob, ($room["_x2"]-(($room["_x2"]-$room["_x1"])/2)), ($room["_y2"]-(($room["_y2"]-$room["_y1"])/2)));
			}
			$mob_selected = true;
		}
}