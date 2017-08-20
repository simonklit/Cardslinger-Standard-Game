<?php
include('mapfunctions.php');

function keypress($clientID, $key)
{
	global $players;
	checkMobs();
	if($key == "VK_UP" or $key == "VK_DOWN" OR $key == "VK_LEFT" OR $key == "VK_RIGHT" OR $key == "VK_W" OR $key == "VK_S" OR $key == "VK_D" OR $key == "VK_A")
	{
		$players[$clientID]->escape();
		movePlayer($clientID, $key);
	}

	if($key == "VK_1" or $key == "VK_2" or $key == "VK_3" or $key == "VK_4" or $key == "VK_5" or $key == "VK_6" or $key == "VK_7" or $key == "VK_8" or $key == "VK_9")
	{
		$players[$clientID]->useInventory(substr($key, -1));
	}

	if($key == "VK_C")
	{
		$players[$clientID]->request('swap');
	}

	if($key == "VK_Q")
	{
		$players[$clientID]->useHealthpot();
	}

	if($key == "VK_E")
	{
		$players[$clientID]->useManapot();
	}

	if($key == "VK_ESCAPE")
	{
		$players[$clientID]->escape();
	}

	if($key == "VK_Z")
	{
		$players[$clientID]->request('describe');
	}
	if($key == "VK_X")
	{
		$players[$clientID]->request('drop');
	}
	bigBroadcast();
}

function chat($clientID, $message)
{
	global $Server, $players, $allow_cheats;
	$msg = ['type' => 'message', 'message' => $message, 'name' => $players[$clientID]->name];
	if($players[$clientID]->requestVar == null)
	{
	if(count($message) > 0 && $message != "\r")
	{
		if(substr($message, 0, 1) == "!") // This is a command.
		{
			preg_match("~!(.*?) ~", $message, $cmd);
 			$cmd = $cmd[1];
 			$arg = substr($message, strpos($message, " ") + 1);
			if($cmd == "cheats")
			{
				if($allow_cheats && $players[$clientID]->cheats == false)
				{
					$players[$clientID]->cheats = true;
					status($clientID, "Cheats are now enabled – please know that these can crash the server.", "#5CCC6B");
				} elseif($allow_cheats && $players[$clientID]->cheats == true)
				{
					$players[$clientID]->cheats = false;
					status($clientID, "Cheats are now disabled.", "#ff5c5c");
				}elseif(!$allow_cheats)
				{
					status($clientID, "Cheats are not allowed on this server.", "#ff5c5c");
				}
			} else {
				if($players[$clientID]->cheats)
				{
					switch ($cmd) {
						case 'tile':
							if($players[$clientID]->hardcheats)
							{
								$arg = explode(" ", $arg);
								if(isset($arg[1]))
								{
									try {
										$newtile = eval("return new " . ucfirst($arg[1]) . ";");
										if($arg[0] == "u")
										{
											setTile($players[$clientID]->x, $players[$clientID]->y - 1, new Tile($newtile));
										} elseif($arg[0] == "d")
										{
											setTile($players[$clientID]->x, $players[$clientID]->y + 1, new Tile($newtile));	
										} elseif($arg[0] == "l")
										{
											setTile($players[$clientID]->x - 1, $players[$clientID]->y, new Tile($newtile));
										} elseif($arg[0] == "r")
										{
											setTile($players[$clientID]->x + 1, $players[$clientID]->y, new Tile($newtile));
										} else
										{
											status($clientID, "The \"tile\" cheat works like this: \"!tile direction tiletospawn\".");
										}
										
									} catch (Exception $e) {
										status($clientID, "The \"tile\" cheat works like this: \"!tile direction tiletospawn\".");
									}
								}else{
									status($clientID, "The \"tile\" cheat works like this: \"!tile direction tiletospawn\".");
								}
							} else {
								status($clientID, "Hard cheats not allowed.");
							}

							break;
						case 'teleport':
							$arg = explode(" ", $arg);
							echo("teleport:\n");
							var_dump($arg);
							moveTile($players[$clientID]->x, $players[$clientID]->y, $arg[0], $arg[1], $players[$clientID]);
							$players[$clientID]->x = $arg[0];
							$players[$clientID]->y = $arg[1];
						case 'hold':
							if(!$players[$clientID]->hold)
							{
								$players[$clientID]->hold = true;
								status($clientID, "You're on hold.");
							} else {
								$players[$clientID]->hold = false;
								status($clientID, "You're off hold.");
							}
							break;
						case 'name':
							if($players[$clientID]->state == "lobby")
							{
								statusBroadcast($players[$clientID]->name . " changed their name to " . preg_replace('/\s+/', '', $arg) . ".", "#ffff00", false, $clientID);
								$players[$clientID]->name = preg_replace('/\s+/', '', $arg);
								status($clientID, "Your name has been set.");
							} else {
								status($clientID, "You can only set your name in the lobby.");
							}
							break;
						default:
							# code...
							break;
					}
					bigBroadcast();
				} else {
					status($clientID, "Cheats are not enabled.", "#ff5c5c");
				}
			}
		} else {
			foreach($Server->wsClients as $id => $client)
				{
						if($clientID == $id && $players[$id]->state == "lobby")
						{
							if (strpos($message, 'startgame') !== false) //If I type startgame, start game!
							{
								unsetLobby();
							} else {
								$Server->wsSend($id, json_encode($msg)); //If I don't, chat out what I wrote!
							}
						} else {
							$Server->wsSend($id, json_encode($msg));
						}
				}
			}
		}
	} else {
		if($players[$clientID]->{$players[$clientID]->requestVar . "Response"}(preg_replace('/\s+/', '', $message)))
		{
			$players[$clientID]->unsetRequest();
		}
	}
}

function setLobby($clientID)
{
	global $Server, $players;
	$playrs = [];
	foreach ($players as $key) {
		if($key->name != null)
		{
			$playrs[$key->clientid] = ["name" => $key->name, "ready" => $key->ready]; 
		}
	}
	$msg = ["type" => "lobby", "players" => $playrs];
	$players[$clientID]->state = "lobby";
	foreach($players as $key)
	{
		if($key->state == "lobby")
		{
			$Server->wsSend($key->clientid, json_encode($msg));
		}
	}
}

function unsetLobby()
{
	global $Server, $players, $ready, $vacant_rooms;
	$ready = true;
	foreach($players as $player)
	{
		$player->state = "game";
		$room = $vacant_rooms[array_rand($vacant_rooms, 1)];
		$xcoord = rand($room["_x1"], $room["_x2"]);
		$ycoord = rand($room["_y1"], $room["_y2"]);
		setTile($xcoord, $ycoord, $player);
		$player->x = $xcoord;
		$player->y = $ycoord;
	}
		foreach($Server->wsClients as $id => $client) {
			$Server->wsSend($id, json_encode(["type" => "unsetLobby"]));
	}
	bigBroadcast();

}

function mapPart($clientID, $receivedMappart)
{
	/*
		Allows us to send the map in chunks from client to server.
		This is necessary because the socket connection doesn't allow us to send maps
		with more than approximately 5000 tiles.

		Pass the following in $receivedMappart:
		$receivedMappart = ["map" => [...], "part" => $currentPart, "endpart" => $theFinalPart];
	*/
		return;
	global $map, $mapset;
	$receivedMap = $receivedMappart["map"];
	if(!$mapset)
	{
		foreach($receivedMap as $key => $value) {
	        $parts = explode(",", $key);
	        $x = $parts[0];
	        $y = $parts[1];
	        $parsedRep = parseRepresentation($receivedMap[$key]);
	        setTile($x, $y, $parsedRep);
	    }
	    if($receivedMappart['part'] == $receivedMappart['endpart'])
	    { //Received final chunk.
	    	$mapset = true;
	    	populateMap();
		}
	}
}


function map($clientID, $receivedMap)
{

	global $map, $mapset;
	if(!$mapset)
	{
		foreach($receivedMap as $key => $value)
		{
	        $parts = explode(",", $key);
	        $x = $parts[0];
	        $y = $parts[1];
	        $map[$x][$y] = parseRepresentation($receivedMap[$key]);
	    }
	    $mapset = true;
	}
	$ix = 0;
}

function bigBroadcast()
{

	global $Server, $broadcasting, $players;
	if(!$broadcasting)
	{
		$broadcasting = true;
		foreach($Server->wsClients as $id => $client)
		{
			broadcastState($id);
		}
		$broadcasting = false;
	}
}

function spawnMob($mob, $x, $y)
{
	global $map;
	$mob->x = $x;
	$mob->y = $y;
	setTile($x, $y, $mob);
}

function checkMobs()
{
	global $map, $players;
	foreach($players as $player)
	{
		$ystart = $player->y + 20;
		$yend = $player->y - 20;
		$xstart = $player->x - 20;
		$xend = $player->x + 20;
		if($ystart < 0)
		{
			$ystart = 0;
		}
		if($xstart < 0)
		{
			$xstart = 0;
		}

		for ($i=$yend; $i <= $ystart; $i++) {
			for($ix = $xstart; $ix <= $xend; $ix++)
			{
				if($map[$ix][$i] != null)
				{
					if($map[$ix][$i]->type() == "npc")
					{
						$map[$ix][$i]->tick();
					}
				}
			}
		}
	}
	return true;
}

function realBigBroadcast()
{

	global $Server, $broadcastqueue;
	//$current = microtime(true);
	//array_push($broadcastqueue, $current);
	if(count($broadcastqueue) == 0)
	{
		foreach($Server->wsClients as $id => $client)
		{
			broadcastState($id);
		}
	} else {
		array_push($broadcastqueue, "true");
	}
}

function broadcastState($clientID)
{
	global $Server, $map, $players, $broadcastqueue;
	if($players[$clientID]->state == "lobby")
	{
		setLobby($clientID);
	} else {
		$state = getState($clientID);
		$Server->wsSend($clientID, json_encode($state));
	}
	$broadcastqueue = [];
}

function requestName($clientID)
{
	global $Server;
	$message = ["type" => "requestName"];
	sleep(1);
	$Server->wsSend($clientID, json_encode($message));
}

function getState($clientID)
{
	global $players, $status;
	$state = [];
	$state['type'] = "state";
	$state['map'] = parseMap($clientID);
	$state['player'] = $players[$clientID]->parse();
	return $state;
}

function status($clientID, $stat, $color = "#ffffff", $expectresponse = false)
{
	global $Server, $status;
	$Server->wsSend($clientID, json_encode(['type' => 'status', 'status' => $stat, 'color' => $color, 'expectresponse' => $expectresponse]));
}

function statusBroadcast($message, $color = "#ffffff", $include_self = true, $player_clientid = null)
{
	global $Server;
	foreach($Server->wsClients as $id => $client)
	{
		if($include_self)
		{
			status($id, $message, $color);
		} else {
			if ( $id != $player_clientid)
			{
				status($id, $message, $color);
			}
		}
	}
}

function newPlayer($clientID)
{
	global $players, $max_players, $Server, $map, $ready;
	if(count($players) < $max_players)
	{
		if(!array_key_exists($clientID, $players)) //If we already have a character for this player.
		{
			$players[$clientID] = new Player($clientID);
			setLobby($clientID);
			$players[$clientID]->request('name');
			$players[$clientID]->addToInventory(new dagger(), false, false);
			//requestName($clientID);
		} else {
			bigBroadcast();
		}
	}
	
}


function movePlayer($clientID, $key)
{
	global $players, $map;
	switch ($key) {
		case 'VK_UP':
			$players[$clientID]->move(0, -1);
			break;
		case 'VK_DOWN':
			$players[$clientID]->move(0, 1);
			break;
		case 'VK_RIGHT':
			$players[$clientID]->move(1);
			break;
		case 'VK_LEFT':
			$players[$clientID]->move(-1);
			break;
		case 'VK_W':
			$players[$clientID]->move(0, -1);
			break;
		case 'VK_S':
			$players[$clientID]->move(0, 1);
			break;
		case 'VK_D':
			$players[$clientID]->move(1);
			break;
		case 'VK_A':
			$players[$clientID]->move(-1);
			break;
		default:
			break;
	}

}