<?php

define('MAX_MANA', 10); // int : mana maximum
define('TURN_TIME', 75); // int : durée max d'un tour en secondes
define('TROPHIES', 60); // int : nombre de trophées échangés lors d'un match (gain maximum et perte maximum)
define('ELECTRIFY_DAMAGE', 10); // int : dégâts par tour au combattants électrisés
define('START_HP', 160); // int : hp des joueurs au début du match
define('MIN_MANA', 1); // int : mana de départ
define('FIRST_TURN_TIME', 90); // int : durée max du premier tour en secondes
define('MAX_BOT_LEVEL', 5); // int : niveau max possible du bot
define('BOT_LEVELS', ['Très facile','Facile','Moyen','Difficile','Très difficile','HARDCORE']); // String[] : niveaux du bot

class Script {
	public $script; // String : script sous forme textuelle
	public $trigger; // String : nom/déclencheur du script
	public $functions; // ScriptFunction[]
	public $condition; // String : condition sous forme textuelle
	public function __construct($script) {
		$this->script = $script;
		preg_match_all('/([a-z]+)({([^}]*)}|)(\[([^\]]*)\]|)/', $script, $matches);
		$this->trigger = isset($matches[1][0]) ? $matches[1][0] : '';
		$this->functions = [];
		if (count($matches[3])!=0 && $matches[3][0]!='')
			foreach (explode(' ', $matches[3][0]) as $function)
				if ($function != '')
					array_push($this->functions, new ScriptFunction($function));
		$this->condition = isset($matches[5][0]) ? $matches[5][0] : '';
	}
	public function toStd() {
		return $this->script;
	}
	public function needTargetOfYou() {
		$targets = 0;
		foreach ($this->functions as $f)
			$targets = max($targets, $f->needTargetOfYou());
		$targets = max($targets, strpos($this->condition,'targetofyou')!==false?1:0, strpos($this->condition,'target2ofyou')!==false?2:0);
		return $targets;
	}
	public function needTargetOfHim() {
		$targets = 0;
		foreach ($this->functions as $f)
			$targets = max($targets, $f->needTargetOfHim());
		$targets = max($targets, strpos($this->condition,'targetofhim')!==false?1:0, strpos($this->condition,'target2ofhim')!==false?2:0, $this->condition=='target'?1:0, strpos($this->condition,'target[')!==false?1:0);
		return $targets;
	}
}

class ScriptFunction {
	public $name; // String : nom de la fonction
	public $args; // String[] : arguments
	public $condition; // String : condition sous forme textuelle
	public function __construct($function) {
	    global $test;
		preg_match_all('/([a-z]+)(\(([^\)]*)\)|)(\[([^\]]*)\]|)/', $function, $matches);
		if ($test) {
		    print_r($matches);
		}
		$this->name = $matches[1][0];
		$this->args = [];
		foreach (explode(',', $matches[3][0]) as $arg)
			array_push($this->args, $arg);
		$this->condition = isset($matches[5][0]) ? $matches[5][0] : '';
	}
	public function needTargetOfYou() {
		$targets = 0;
		foreach (array_merge([$this->condition], $this->args) as $a)
			$targets = max($targets, strpos($a,'targetofyou')!==false?1:0, strpos($a,'target2ofyou')!==false?2:0);
		return $targets;
	}
	public function needTargetOfHim() {
		$targets = 0;
		foreach (array_merge([$this->condition], $this->args) as $a)
			$targets = max($targets, strpos($a,'targetofhim')!==false?1:0, strpos($a,'target2ofhim')!==false?2:0, $a=='target'?1:0, strpos($a,'target[')!==false?1:0);
		return $targets;
	}
}

class Card {
	public $id=0; // int : identifiant de la carte dans la bdd
	public $cost=0; // int : prix en mana de la carte
	public $scripts=[]; // Script[] : scripts de la carte
	public $vars; // stdClass : variables internes à la carte
	public $p=false; // boolean : est-elle en version prestige/fullart ?
	public $s=false; // boolean : est-elle en version shiny ?
	public $h=false; // boolean : est-elle en version holographique ?
	protected function __construct($id, $cost, $scripts) {
		$this->id = intval($id);
		$this->p = strpos($id, 'p') !== false;
		$this->s = strpos($id, 's') !== false;
		$this->h = strpos($id, 'h') !== false;
		$this->cost = $cost;
		$this->scripts = $scripts;
		$this->vars = new stdClass();
	}
	public static function fromStd($std) {
		if ($std->type == 'prof')
			return FighterCard::fromStd($std);
		if ($std->type == 'effect')
			return EffectCard::fromStd($std);
		if ($std->type == 'place')
			return PlaceCard::fromStd($std);
		throw new Exception('Unknow Card type : '.$std->type);
	}
	public function toStd() {
		$std = new stdClass();
		$std->id = $this->id.($this->p?'p':'').($this->s?'s':'').($this->h?'h':'');
		$std->cost = $this->cost;
		$std->scripts = [];
		foreach ($this->scripts as $script)
			array_push($std->scripts, $script->toStd());
		$std->vars = $this->vars;
		$std->type = $this instanceof FighterCard ? 'prof' : ($this instanceof EffectCard ? 'effect' : ($this instanceof PlaceCard ? 'place' : 'unknow'));
		return $std;
	}
	public static function fromId($id) {
		return Card::fromDBRow($id, sendRequest("SELECT * FROM CARDS WHERE id='", intval($id), "'")->fetch_assoc());
	}
	public static function fromDBRow($id, $row) {
		$cardinfos = json_decode($row['infos']);
		$scripts = array(new Script($row['script1']), new Script($row['script2']));
		if ($row['type']=='prof')
			return new FighterCard($id, intval($cardinfos->cost), intval($cardinfos->hp), $cardinfos->types, $scripts);
		if ($row['type']=='place')
			return new PlaceCard($id, intval($cardinfos->cost), $scripts);
		if ($row['type']=='effect')
			return new EffectCard($id, intval($cardinfos->cost), $scripts);
		throw new Exception('Unknow Card type : '.$row['type']);
	}
}

class FighterCard extends Card {
	public $hpmax=10; // int : pv max de la carte
	public $hp=10; // int : pv de la carte
	public $types=[]; // String[] : types de combattant
	public $t=0; // int : depuis combien de tour a-t-elle été posée ?
	public $eg=false; // boolean : la carte a-t-elle déjà attaqué ? 
	public $mi=false; // boolean : la carte vient d'être posée ?
	public $slp=0; // int :  combien de tour sera-t-elle encore endormie ?
	public $efr=0; // int : combien de tour sera-t-elle encore effrayée ?
	public $prl=0; // int : combien de tour sera-t-elle encore paralysée ?
	public $elc=0; // int : combien de tour sera-t-elle encore electrifiée ?
	public $strength=0; // int : force supplémentaire
	public $shield=0; // int : défense supplémentaire
	public $pvq=0; // int : combien de toue sera-t-elle provoquante ?
	public function __construct($id, $cost, $hpmax, $types, $scripts) {
		parent::__construct($id, $cost, $scripts);
		$this->hp = $this->hpmax = $hpmax;
		$this->types = $types;
	}
	public static function fromStd($std) {
		$scripts = [];
		foreach ($std->scripts as $script)
			array_push($scripts, new Script($script));
		$new = new self($std->id, $std->cost, $std->hpmax, $std->types??[$std->proftype??''], $scripts);
		$new->vars = isset($std->vars) ? $std->vars : new stdClass();
		$new->hp = $std->hp;
		$new->t = isset($std->t) ? $std->t : 0;
		$new->eg = isset($std->eg) ? $std->eg : false;
		$new->mi = isset($std->mi) ? $std->mi : false;
		$new->slp = isset($std->slp) ? $std->slp : 0;
		$new->efr = isset($std->efr) ? $std->efr : 0;
		$new->prl = isset($std->prl) ? $std->prl : 0;
		$new->elc = isset($std->elc) ? $std->elc : 0;
		$new->strength = isset($std->strength) ? $std->strength : 0;
		$new->shield = isset($std->shield) ? $std->shield : 0;
		$new->pvq = isset($std->pvq) ? $std->pvq : 0;
		return $new;
	}
	public function toStd() {
		$std = parent::toStd();
		$std->hpmax = $this->hpmax;
		$std->hp = $this->hp;
		$std->types = $this->types;
		$std->t = $this->t;
		$std->eg = $this->eg;
		$std->mi = $this->mi;
		$std->slp = $this->slp;
		$std->efr = $this->efr;
		$std->prl = $this->prl;
		$std->elc = $this->elc;
		$std->strength = $this->strength;
		$std->shield = $this->shield;
		$std->pvq = $this->pvq;
		return $std;
	}
	public function clear() {
	    $this->t = 0;
		$this->eg = $this->mi = false;
		$this->slp = $this->prl = $this->efr = $this->pvq = $this->elc = $this->shield = $this->strength = 0;
		$this->hp = $this->hpmax;
	}
}

class EffectCard extends Card {
	public function __construct($id, $cost, $scripts) {
		parent::__construct($id, $cost, $scripts);
	}
	public static function fromStd($std) {
		$scripts = [];
		foreach ($std->scripts as $script)
			array_push($scripts, new Script($script));
		$new = new self($std->id, $std->cost, $scripts);
		$new->vars = isset($std->vars) ? $std->vars : new stdClass();
		return $new;
	}
	public function toStd() {
		$std = parent::toStd();
		return $std;
	}
}

class PlaceCard extends Card {
	public $t=0; // int : depuis combien de demi-tour a-t-elle été posée ?
	public function __construct($id, $cost, $scripts) {
		parent::__construct($id, $cost, $scripts);
	}
	public static function fromStd($std) {
		$scripts = [];
		foreach ($std->scripts as $script)
			array_push($scripts, new Script($script));
		$new = new self($std->id, $std->cost, $scripts);
		$new->vars = isset($std->vars) ? $std->vars : new stdClass();
		$new->t = $std->t ?? 0;
		return $new;
	}
	public function toStd() {
		$std = parent::toStd();
		$std->t = $this->t;
		return $std;
	}
}

class Player {
	public $hp = START_HP; // int : pv du joueur
	public $mana = MIN_MANA; // int : quantité de mana du joueur
	public $discard = []; // Card[] : défausse du joueur
	public $fighters = []; // FighterCard[] : cartes combattantes du joueur
	public $deck = []; // Card[] : pioche du joueur
	public $hand = []; // Card[] : main du joueur
	public $historyIndex = 0; // int : index de la derniere action de l'historique récupérée
	public $baseDeck = []; // string[] : identifiants des cartes de tout le deck du joueur
	public function __construct() {}
	public static function fromStd($std) {
		$new = new self();
		$new->hp = $std->hp;
		$new->mana = $std->mana;
		$new->discard = [];
		foreach ($std->discard as $card)
			array_push($new->discard, Card::fromStd($card));
		$new->fighters = [];
		foreach ($std->profs as $prof)
			array_push($new->fighters, FighterCard::fromStd($prof));
		$new->deck = [];
		foreach ($std->deck as $card)
			array_push($new->deck, Card::fromStd($card));
		$new->hand = [];
		foreach ($std->hand as $card)
			array_push($new->hand, Card::fromStd($card));
		$new->historyIndex = isset($std->historyIndex) ? $std->historyIndex : 0;
		$new->baseDeck = $std->baseDeck ?? [];
		return $new;
	}
	public function toStd() {
		$std = new stdClass();
		$std->hp = $this->hp;
		$std->mana = $this->mana;
		$std->discard = [];
		foreach ($this->discard as $card)
			array_push($std->discard, $card->toStd());
		$std->profs = [];
		foreach ($this->fighters as $prof)
			array_push($std->profs, $prof->toStd());
		$std->deck = [];
		foreach ($this->deck as $card)
			array_push($std->deck, $card->toStd());
		$std->hand = [];
		foreach ($this->hand as $card)
			array_push($std->hand, $card->toStd());
		$std->historyIndex = $this->historyIndex;
		$std->baseDeck = $this->baseDeck; 
		return $std;
	}
	public function toStdPublic() {
		$std = new stdClass();
		$std->hp = $this->hp;
		$std->mana = $this->mana;
		$std->discard = [];
		foreach ($this->discard as $card)
			array_push($std->discard, $card->toStd());
		$std->profs = [];
		foreach ($this->fighters as $prof)
			array_push($std->profs, $prof->toStd());
		$std->deck = count($this->deck);
		$std->hand = count($this->hand);
		return $std;
	}
	public function toStdClient() {
		$std = new stdClass();
		$std->hp = $this->hp;
		$std->mana = $this->mana;
		$std->discard = [];
		foreach ($this->discard as $card)
			array_push($std->discard, $card->toStd());
		$std->profs = [];
		foreach ($this->fighters as $prof)
			array_push($std->profs, $prof->toStd());
		$std->deck = count($this->deck);
		$std->hand = [];
		foreach ($this->hand as $card)
		    //print_r($std->hand);
			array_push($std->hand, $card->toStd());
		return $std;
	}
	public function getRandomFighter($notIn=[], $preferProvoking=false) {
		$arr = [];
		foreach ($this->fighters as $f)
			if (!in_array($f, $notIn, true))
				array_push($arr, $f);
		if (count($arr)===0) return null;
		if ($preferProvoking) {
		    $hasProvoking = false;
		    foreach($arr as $f) {
		        if ($f->pvq>0)
		            $hasProvoking = true;        
		    }
		    if ($hasProvoking)
		        for($i=0; $i<count($arr); $i++)
		            if ($arr[$i]->pvq<=0) {
		                array_splice($arr, $i, 1);
		                $i--;
		            }
		}
		return $arr[random_int(0, count($arr)-1)];
	}
	
	//fonction Léo
	public function getWeakFighter($notIn=[], $preferProvoking=false) {
		$arr = [];
		$weakest=0;
		foreach ($this->fighters as $f)
			if (!in_array($f, $notIn, true))
				array_push($arr, $f);
		if (count($arr)===0) return null;
		if ($preferProvoking) {
		    $hasProvoking = false;
		    foreach($arr as $f) {
		        if ($f->pvq>0)
		            $hasProvoking = true;        
		    }
		    if ($hasProvoking)
		        for($i=0; $i<count($arr); $i++)
		            if ($arr[$i]->pvq<=0) {
		                array_splice($arr, $i, 1);
		                $i--;
		            }
		}
		$weakest=$arr[0];
		foreach ($arr as $target) {
		    if (($target->hp+$target->shield)<($weakest->hp+$weakest->shield)) {
		        $weakest=$target;
		    } else if ((($target->hp+$target->shield)==($weakest->hp+$weakest->shield))&&(random_int(0,1)==1)) {
		        $weakest=$target;
		    }
		}
		return $weakest;
	}
	public function getRandomActiveFighter($notIn=[], $preferProvoking=false) {
		$arr = [];
		$attacked=[];
		foreach ($this->fighters as $f) {
			if (!in_array($f, $notIn, true)) {
				array_push($arr, $f);
				if (($f->slp==0)&&($f->prl==0)&&($f->efr==0)) {
	                array_push($attacked,$f);
		        }   
	        }
	    }
		if (count($arr)===0) return null;
		if ($preferProvoking) {
		    $hasProvoking = false;
		    foreach($arr as $f) {
		        if ($f->pvq>0)
		            $hasProvoking = true;        
		    }
		    if ($hasProvoking)
		        for($i=0; $i<count($arr); $i++)
		            if ($arr[$i]->pvq<=0) {
		                array_splice($arr, $i, 1);
		                $i--;
		            }
		}
		if (count($attacked)===0) {
		    return $arr[random_int(0, count($arr)-1)];
		}
		return $attacked[random_int(0, count($attacked)-1)];
	}
	public function getWeakActiveFighter($notIn=[], $preferProvoking=false) {
		$arr = [];
		$attacked=[];
		$weakest=0;
		foreach ($this->fighters as $f) {
			if (!in_array($f, $notIn, true)) {
				array_push($arr, $f);
				if (($f->slp==0)&&($f->prl==0)&&($f->efr==0)) {
	                array_push($attacked,$f);
		        }   
	        }
	    }
		if (count($arr)===0) return null;
		if ($preferProvoking) {
		    $hasProvoking = false;
		    foreach($arr as $f) {
		        if ($f->pvq>0)
		            $hasProvoking = true;        
		    }
		    if ($hasProvoking)
		        for($i=0; $i<count($arr); $i++)
		            if ($arr[$i]->pvq<=0) {
		                array_splice($arr, $i, 1);
		                $i--;
		            }
		}
		if (count($attacked)===0) {
		    $weakest=$arr[0];
		    foreach ($arr as $target) {
		        if (($target->hp+$target->shield)<($weakest->hp+$weakest->shield)) {
		            $weakest=$target;
		        }
		    }
		    return $weakest;
		}
		$weakest=$attacked[0];
		foreach ($attacked as $target) {
		    if (($target->hp+$target->shield)<($weakest->hp+$weakest->shield)) {
		        $weakest=$target;
		    } else if ((($target->hp+$target->shield)==($weakest->hp+$weakest->shield))&&(random_int(0,1)==1)) {
		        $weakest=$target;
		    }
		}
		return $weakest;
	}
	public function getAllFighter($notIn=[], $preferProvoking=false, $random=true, $weak=false, $active=false) {
		$arr = [];
		$attacked=[];
		$weakest=0;
		$hasProvoking = false;
		if ($preferProvoking) {
		    foreach($this->fighters as $f) {
		        if ($f->pvq>0)
		            $hasProvoking = true;        
		    }
		}
		foreach ($this->fighters as $f) {
			if (!in_array($f, $notIn, true)) {
			    if ($hasProvoking) {
			        if ($f->pvq>0) {
			            array_push($arr, $f);
				        if (($f->slp==0)&&($f->prl==0)&&($f->efr==0)) {
	                        array_push($attacked,$f);
		                }   
			        }
			    } else {
				    array_push($arr, $f);
				    if (($f->slp==0)&&($f->prl==0)&&($f->efr==0)) {
	                    array_push($attacked,$f);
		            }   
			    }
	        }
	    }
		if (($active) && (count($attacked)!==0)) {
		    if ($weak) { 
		        //weakest active fighter
		        $weakest=$attacked[0];
	    	    foreach ($attacked as $target) {
    		        if (($target->hp+$target->shield)<($weakest->hp+$weakest->shield)) {
		                $weakest=$target;
		            } else if ((($target->hp+$target->shield)==($weakest->hp+$weakest->shield))&&(random_int(0,1)==1)) {
		                $weakest=$target;
		            }
	    	    }
    		    return $weakest;
		    } else {
		        //random active fighter
		        return $attacked[random_int(0, count($attacked)-1)];
		    }
		} else {
		    //Don't care about active or not
		    if ($weak) {
		        //weakest fighter
		        $weakest=$arr[0];
		        foreach ($arr as $target) {
		            if (($target->hp+$target->shield)<($weakest->hp+$weakest->shield)) {
		                $weakest=$target;
		            } else if ((($target->hp+$target->shield)==($weakest->hp+$weakest->shield))&&(random_int(0,1)==1)) {
		                $weakest=$target;
		            }
		        }
		        return $weakest;
		    }
		}
		//random of random of... random
		return $arr[random_int(0, count($arr)-1)];
	}
}

class Action extends stdClass {
	public $name;
	public function __construct($name, $data) {
		$this->name = $name;
		foreach ($data as $key=>$value)
			$this->$key = $value;
	}
	public static function fromStd($std) {
		return new self($std->name, $std);
	}
	public function toStd() {
		$std = new stdClass();
		foreach ($this as $key=>$value)
			$std->$key = $value;
		return $std;
	}
	public function toStdClient($playerId) {
		$std = new stdClass();
		foreach ($this as $key=>$value) {
			if ($this->name=='draw' && $key=='card' && $this->playerId!=$playerId)
				continue;
			if (($this->name=='seedraw'||$this->name=='seedrawhim') && $key=='cards' && $this->playerId!=$playerId)
				$std->$key = count($value);
			else
				$std->$key = $value;
		}
		$std->name = $this->name;
		return $std;
	}
}

class Match_ {
	public $playing = 0; // boolean : id du joueur qui joue
	public $start = 0; // long : temps où le tour à commencer
	public $end = 0; // long : temps où le tour se finit automatiquement
	public $place = []; // PlaceCard[] : liste des cartes lieu
	public $mL = MIN_MANA; // float : niveau de mana actuel de la partie (appartient à N/2)
	public $opponents = []; // Player[] : adveraires
	public $history = []; // Action[] : historique des actions au cours du match
	public $botDifficult = 0;
	public function __construct($opponents) {
		$this->start = time();
		$this->end = time()+FIRST_TURN_TIME;
		$this->opponents = $opponents;
		foreach ($this->opponents as $opponent) {
			$this->mL += .5;
			$opponent->mana = intval($this->mL);
		}
	}
	public static function fromStd($std) {
		$new = new self([]);
		$new->playing = $std->playing;
		$new->start = $std->start;
		$new->end = $std->end;
		$new->mL = $std->mL;
		$new->place = [];
		foreach ($std->place as $place)
			array_push($new->place, PlaceCard::fromStd($place));
		$new->opponents = [];
		foreach ($std->opponents as $opponent)
			array_push($new->opponents, Player::fromStd($opponent));
		$new->history = [];
		if (isset($std->history)) foreach ($std->history as $action)
			array_push($new->history, Action::fromStd($action));
		$new->botDifficult = $std->botDifficult ?? 0;
		return $new;
	}
	public function toStd() {
		$std = new stdClass();
		$std->playing = $this->playing;
		$std->start = $this->start;
		$std->end = $this->end;
		$std->mL = $this->mL;
		$std->place = [];
		foreach ($this->place as $place)
			array_push($std->place, $place->toStd());
		$std->opponents = [];
		foreach ($this->opponents as $opponent)
			array_push($std->opponents, $opponent->toStd());
		$std->history = [];
		foreach ($this->history as $action) {
			array_push($std->history, $action->toStd());
		}
		$std->botDifficult=$this->botDifficult;
		return $std;
	}
	public function toStdClient($playerId) {
		$std = new stdClass();
		$std->playing = $this->playing;
		if ($playerId!==null) {
			$std->playerId = $playerId;
		}
		$std->start = $this->start;
		$std->end = $this->end;
		$std->place = [];
		foreach ($this->place as $place)
			array_push($std->place, $place->toStd());
		$std->opponents = [];
		foreach ($this->opponents as $id=>$opponent)
			array_push($std->opponents, $id===$playerId ? $opponent->toStdClient() : $opponent->toStdPublic());
		$std->history = [];
		foreach ($this->history as $action)
			array_push($std->history, $action->toStdClient($playerId));
		$std->botDifficult = $this->botDifficult;
		return $std;
	}
	
	// Action joueur : Jouer une carte
	public function playCard(int $playerId, int $index, $context) { // faire jouer une carte
		if ($playerId != $this->playing)
			throw new Exception('not your turn');
		if ($index < 0 || $index >= count($this->opponents[$playerId]->hand))
			throw new Exception('invalid index');
		$card = $this->opponents[$playerId]->hand[$index];
		if ($card->cost > $this->opponents[$playerId]->mana) {
			throw new Exception('need mana');
		}
		foreach ($card->scripts as $script) {
			if ($script->trigger == 'onplaycard') {
				if (!$this->testScriptCondition($script->condition, $card, $playerId, $context))
					throw new Exception('unfilled playcondition');
			}
		}
		$this->opponents[$playerId]->mana -= $card->cost;
		array_splice($this->opponents[$playerId]->hand, $index, 1);
		if ($card instanceof FighterCard) {
			$history = $this->playFighterCard($card, $playerId, $index, $context);
		} else if ($card instanceof EffectCard) {
			$history = $this->applyEffectCard($card, $playerId, $index, $context);
		} else if ($card instanceof PlaceCard) {
			$history = $this->setNewPlace($card, $playerId, $index, $context);
		}
		$this->addToHistory($history);
		return $history;
	}
	
	// Action joueur : Faire attaquer un combattant
	public function attack(int $playerId, int $cardIndex, int $scriptIndex, $context) { // faire attaquer une carte
		if ($playerId != $this->playing)
			throw new Exception('not your turn');
		if ($cardIndex < 0 || $cardIndex >= count($this->opponents[$playerId]->fighters))
			throw new Exception('invalid index');
		$card = $this->opponents[$playerId]->fighters[$cardIndex];
		if ($scriptIndex < 0 || count($card->scripts) <= $scriptIndex)
			throw new Exception('this attack doesn\'t exist');
		if ($card->eg)
			throw new Exception('engaged');
		if ($card->mi)
			throw new Exception('mi');
		if ($card->slp>0)
			throw new Exception('sleeping');
		if ($card->prl>0)
			throw new Exception('paralysed');
		if ($card->efr>0)
			throw new Exception('affraid');
		if ($card->scripts[$scriptIndex]->trigger != 'onaction')
			throw new Exception('invalid script trigger');
		if(!$this->testScriptCondition($card->scripts[$scriptIndex]->condition, $card, $playerId, $context))
			throw new Exception('unfilled actioncondition');
		$card->eg = true;
		$history = [];
		array_push($history, new Action('engage', array('target'=>$this->getFighterIndex($card))));
		$history = array_merge($history, $this->applyScript($card->scripts[$scriptIndex], $card, $playerId, $context));
		$this->addToHistory($history);
		return $history;
	}
	
	// Action joueur : Faire finir le tour
	public function endTurn(int $playerId, $context) {
		if ($this->playing != $playerId)
			throw new Exception('not your turn');
		$history = $this->nextTurn($context, $playerId);
		$this->addToHistory($history);
		if ($context['playerIds'][$playerId==0?1:0] == -807) {
			$this->bot($playerId==0?1:0, $context);
		}
		return $history;
	}
	
	// Action joueur : Abandonner
	public function giveUp(int $playerId, $context) {
		$history = $this->playerWin($playerId==0?1:0, $playerId, $context, true);
		$this->addToHistory($history);
		return $history;
	}
	
	// Ajouter des actions à l'historique du match
	protected function addToHistory($actions) {
		if (!is_array($actions))
			array_push($this->history, $actions);
		else foreach ($actions as $action)
			array_push($this->history, $action);
	}
	
	// Jouer une carte combattant
	function playFighterCard($card, $playerId, $index, $context) {
		$history = [];
		$card->mi = true;
		$card->t = 0;
		array_push($this->opponents[$this->playing]->fighters, $card);
		array_push($history, new Action('playfightercard', array('playerId'=>$playerId,'index'=>$index,'card'=>$card->toStd())));
		foreach ($card->scripts as $script) {
			if ($script->trigger == 'onplaycard') {
				$history = array_merge($history, $this->applyScript($script, $card, $playerId, $context));
			}
		}
		$history = array_merge($history, $this->applyPlaceScript('onsummon', array('summoned'=>$card)));
		return $history;
	}
	
	// Jouer une carte effet
	function applyEffectCard($card, $playerId, $index, $context) {
		$history = [];
		array_push($history, new Action('playeffectcard', array('playerId'=>$playerId,'index'=>$index,'card'=>$card->toStd())));
		foreach ($card->scripts as $script) {
			if ($script->trigger == 'onplaycard') {
				$history = array_merge($history, $this->applyScript($script, $card, $playerId, $context));
			}
		}
		array_push($this->opponents[$this->playing]->discard, $card);
		return $history;
	}
	
	// Jouer une carte lieu
	function setNewPlace($card, $playerId, $index, $context) {
		$history = [];
		array_push($history, new Action('playplacecard', array('playerId'=>$playerId,'index'=>$index,'card'=>$card->toStd())));
		array_push($this->place, $card);
		foreach ($card->scripts as $script) {
			if ($script->trigger == 'onplaycard') {
				$history = array_merge($history, $this->applyScript($script, $card, $playerId, $context));
			}
		}
		return $history;
	}
	
	// Attaquer un combattant
	function attackFighter($victim, $attacker, $playerId, $damage, $ignoredef, $context) {
		$otherId = $playerId==0 ? 1 : 0; // en supposant qu'il n'y ait que 2 joueurs
		$history = [];
	    if ($damage<0) {
	        $damage=0;
		}
		if (!$ignoredef && ($victim instanceof Player)) // règles des défenseurs
			if (count($victim->fighters)>0)
				throw new Exception('defensors');
		array_push($history, new Action('attack', array('damage'=>$damage, 'target'=>$this->getFighterIndex($victim), 'agent'=>$this->getFighterIndex($attacker))));
		if (isset($victim->shield)) {
			if ($victim->shield <= $damage) {
				$damage -= $victim->shield;
				$victim->shield = 0;
			} else {
				$victim->shield -= $damage;
				$damage = 0;
			}
		}
		$victim->hp -= $damage;
		if ($victim->hp <= 0) {
			if ($victim instanceof Player) {
				$winner = $victim==$this->opponents[0] ? 1 : 0;
				$loser = $victim==$this->opponents[0] ? 0 : 1;
				$history = array_merge($history, $this->playerWin($winner, $loser, $context, false));
			} else {
				foreach ($this->opponents as $playerIndex => $player) {
					for ($i=0; $i<count($player->fighters); $i++) {
						$fighter = $player->fighters[$i];
						if ($fighter->hp <= 0) {
							array_push($history, new Action('eliminate', array('teamId'=>$playerIndex,'index'=>$i)));
							array_push($player->discard, array_splice($player->fighters, $i, 1)[0]);
							foreach ($fighter->scripts as $script)
								if ($script->trigger == 'ondie' && $this->testScriptCondition($script->condition, $fighter, $playerIndex, $context))
									$history = array_merge($history, $this->applyScript($script, $fighter, $playerIndex, $context));
							$i--;
						}
					}
				}
			}
		}
		return $history;
	}
	
	// Quand un joueur gagne
	protected function playerWin($winner, $loser, $context, $giveup=false) {
		$history = [];
		if ($context['playerIds'][$loser]<0 || $context['playerIds'][$winner]<0 || $this->mL<4) { // bot ou compte test ou partie trop courte
			$winnerReward = 4+$this->botDifficult;
			$loserReward = 0;
			$gain = $lost = 0;
		} else { // vrai match
			$gain = ceil(1/(1+exp((intval($context['trophies'][$winner])-intval($context['trophies'][$loser]))/100))*TROPHIES);
			$lost = floor(1/(1+exp((intval($context['trophies'][$winner])-intval($context['trophies'][$loser]))/100))*TROPHIES);
			$winnerReward = $giveup ? 13 : 15;
			$loserReward = $giveup ? 6 : 8;
		}
		array_push($history, new Action('endgame', array('winner'=>$winner, 'gain'=>$gain, 'lost'=>$lost, 'winnerReward'=>$winnerReward, 'loserReward'=>$loserReward)));
		return $history;
	} 
	
	// Récupérer l'index-id d'un combattant (à utiliser seulement pour les retours client)
	function getFighterIndex($card) {
		if (array_search($card, $this->place, true) !== false)
			return array('teamId'=>'place', 'index'=>array_search($card, $this->place));
		foreach ($this->opponents as $ti=>$opponent) {
			if ($card == $opponent)
				return array('teamId'=>$ti, 'index'=>'player');
			foreach ($opponent->fighters as $fi=>$fighter) {
				if ($card === $fighter)
					return array('teamId'=>$ti, 'index'=>$fi, 'in'=>count($opponent->fighters));
			}
			foreach ($opponent->discard as $di=>$dropcard) {
				if ($card === $dropcard)
					return array('teamId'=>$ti, 'index'=>$di, 'discard'=>true);
			}
		}
		return array('teamId'=>'unknow','index'=>-1);
	}
	
	// Appliquer un script
	function applyScript($script, $card, $playerId, $context) {
		$history = [];
		$otherId = $playerId==0 ? 1 : 0; // en supposant qu'il n'y ait que 2 joueurs
		//if ($script->trigger == 'onlyfirst' && $card->t > 1) return $history;
		foreach ($script->functions as $function) {
			if ($function->condition!='' && !$this->testScriptCondition($function->condition, $card, $playerId, $context)) continue;
			$args = $function->args;
			switch ($function->name) {
				case 'attackif': // profs, damage, condition, coeff
					if ($this->testScriptCondition($args[2], $card, $playerId, $context)) {
						$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
						foreach ($profs as $prof)
							$history = array_merge($history, $this->attackFighter($prof, $card, $playerId, $this->getScriptValue($args[1], $card, $playerId, $context)*$this->getScriptValue($args[3], $card, $playerId, $context)+($card instanceof FighterCard?$card->strength:0), false, $context));
						break;
					} // else ⬇
				case 'attack': // profs, damage, ?ignoreDef
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						$history = array_merge($history, $this->attackFighter($prof, $card, $playerId, $this->getScriptValue($args[1], $card, $playerId, $context)+($card instanceof FighterCard?$card->strength:0), isset($args[2])?$args[2]=='true':false, $context));
					}
					break;
				case 'heal': // profs, heal
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						$heal = $this->getScriptValue($args[1], $card, $playerId, $context);
						$prof->hp = min($prof->hp+$heal, $prof instanceof Player ? START_HP : $prof->hpmax);
						array_push($history, new Action('heal', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'heal'=>$heal)));
					}
					break;
				case 'sleep': // profs, time
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->slp = $this->getScriptValue($args[1], $card, $playerId, $context);
						array_push($history, new Action('sleep', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'slp'=>$prof->slp)));
					}
					break;
				case 'wakeup': // profs
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->slp = 0;
						array_push($history, new Action('wakeup', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card))));
					}
					break;
				case 'seedraw': // who, number
					$seeer = $args[0]=='him'?$otherId:$playerId;
					array_push($history, new Action('seedraw', array('playerId'=>$seeer,'cards'=>array_map(function($card){return $card->toStd();}, array_slice($this->opponents[$seeer]->deck, 0, $this->getScriptValue($args[1], $card, $playerId, $context))))));
					break;
				case 'seedrawhim': // who, number
					array_push($history, new Action('seedrawhim', array('playerId'=>$args[0]=='him'?$otherId:$playerId,'cards'=>array_map(function($card){return $card->toStd();}, array_slice($this->opponents[$args[0]=='him'?$playerId:$otherId]->deck, 0, $this->getScriptValue($args[1], $card, $playerId, $context))))));
					break;
				case 'kick': // profs
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$history = array_merge($history, $this->attackFighter($prof, $card, $playerId, 1000000, true, $context)); // TODO
						array_push($history, new Action('kick', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card))));
					}
					break;
				case 'paralyse': // profs, time
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->prl = $this->getScriptValue($args[1], $card, $playerId, $context);
						array_push($history, new Action('paralyse', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'prl'=>$prof->prl)));
					}
					break;
				case 'affraid': // profs, time
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->efr = $this->getScriptValue($args[1], $card, $playerId, $context);
						array_push($history, new Action('affraid', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'efr'=>$prof->efr)));
					}
					break;
				case 'courage': // profs
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->efr = 0;
						array_push($history, new Action('courage', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card))));
					}
					break;
				case 'electrify': // profs, time
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->elc = $this->getScriptValue($args[1], $card, $playerId, $context);
						array_push($history, new Action('electrify', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'elc'=>$prof->elc)));
					}
					break;
				case 'diselectrify': // profs
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->elc = 0;
						array_push($history, new Action('diselectrify', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card))));
					}
					break;
				case 'setvar': // varname, value
					$varname = $args[0];
					$card->vars->$varname = $this->getScriptValue($args[1], $card, $playerId, $context);
					array_push($history, new Action('setvar', array('card'=>$this->getFighterIndex($card), 'varname'=>$varname, 'value'=>$card->vars->$varname)));
					break;
				case 'leaveplace': //
					array_pop($this->place); // actuellement suppr et pas envoyé en défausse
					array_push($history, new Action('leaveplace', array()));
					break;
				case 'delmana': // who, value
					$mana = $this->getScriptValue($args[1], $card, $playerId, $context);
					$this->opponents[$args[0]=='you'?$playerId:$otherId]->mana = max(0, $this->opponents[$args[0]=='you'?$playerId:$otherId]->mana-$mana);
					array_push($history, new Action('delmana', array('playerId'=>$args[0]=='you'?$playerId:$otherId, 'agent'=>$this->getFighterIndex($card), 'mana'=>$mana)));
					break;
				case 'givemana': // who, value
					$mana = $this->getScriptValue($args[1], $card, $playerId, $context);
					if ($args[0] == 'you') $this->opponents[$args[0]=='him'?$otherId:$playerId]->mana += $mana;
					array_push($history, new Action('givemana', array('playerId'=>$args[0]=='him'?$otherId:$playerId, 'agent'=>$this->getFighterIndex($card), 'mana'=>$mana)));
					break;
				case 'draw': // who
					$drawer = ($args[0] == 'him') ? $otherId : $playerId;
					if (count($this->opponents[$drawer]->deck) > 0) {
						array_push($this->opponents[$drawer]->hand, $drawedCard=array_shift($this->opponents[$drawer]->deck));
						array_push($history, new Action('draw', array('playerId'=>$drawer, 'card'=>$drawedCard->toStd())));
					}
					break;
				case 'drop': // who, index // TODO : seul random est valable actuellement
					$dropper = ($args[0] == 'him') ? $otherId : $playerId;
					if (count($this->opponents[$dropper]->hand)==0) break;
					if ($args[1]=='random') $args[1]=random_int(0, count($this->opponents[$dropper]->hand)-1);
					if (count($this->opponents[$dropper]->hand) > 0) {
						array_push($this->opponents[$dropper]->discard, $droppedCard=array_splice($this->opponents[$dropper]->hand, intval($args[1]), 1)[0]);
						array_push($history, new Action('drop', array('playerId'=>$dropper, 'index'=>$args[1], 'card'=>$droppedCard->toStd())));
					}
					break;
				case 'addshield': // profs, amount
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					$shield = $this->getScriptValue($args[1], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->shield += $shield;
						array_push($history, new Action('addshield', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'shield'=>$shield)));
					}
					break;
				case 'removeshield': // profs, amount
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$unshield = $this->getScriptValue($args[1], $card, $playerId, $context);
						if ($prof->shield <= $unshield) $prof->shield = 0;
						else $prof->shield -= $unshield;
						array_push($history, new Action('removeshield', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'shield'=>$unshield)));
					}
					break;
				case 'addstrength': // profs, amount
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					$strength = $this->getScriptValue($args[1], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->strength += $strength;
						array_push($history, new Action('addstrength', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card), 'strength'=>$strength)));
					}
					break;
				case 'summon': // amount, cardId, proftype, pv, damage
					for ($i = 0; $i < $this->getScriptValue($args[0], $card, $playerId, $context); $i++) {
						$minion = new FighterCard(intval($args[1]), 0, $this->getScriptValue($args[3], $card, $playerId, $context), [$args[2]], array(new Script('onaction{attack(target,'.$this->getScriptValue($args[4], $card, $playerId, $context).')}'), new Script('')));
						$minion->mi = true;
						array_push($this->opponents[$playerId]->fighters, $minion);
						array_push($history, new Action('summon', array('teamId'=>$playerId, 'card'=>$minion->toStd())));
					}
					break;
				case 'convert': // profs
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $toConvert) {
						if ($toConvert instanceof Player) continue;
						foreach ($this->opponents as $opponentId=>$opponent)
							for ($i=0; $i<count($opponent->fighters); $i++) {
								if ($opponent->fighters[$i] !== $toConvert) continue;
								array_push($history, new Action('convert', array('index'=>$i, 'playerId'=>$playerId)));
								array_push($this->opponents[$opponentId==0?1:0]->fighters, array_splice($opponent->fighters, $i, 1)[0]);
								$i--;
								break 3;
							}
					}
					break;
				case 'disengage': // profs
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->eg = false;
						$prof->mi = false;
						array_push($history, new Action('disengage', array('target'=>$this->getFighterIndex($prof), 'agent'=>$this->getFighterIndex($card))));
					}
					break;
				case 'invoc': // player, cardId
					$card = Card::fromId($this->getScriptValue($args[1], $card, $playerId, $context));
					$card->mi = true;
					$invokerId = ($this->getScriptValue($args[0], $card, $playerId, $context)=='him')?$otherId:$playerId;
					array_push($this->opponents[$invokerId]->fighters, $card);
					array_push($history, new Action('summon', array('teamId'=>$invokerId, 'card'=>$card->toStd())));
					break;
				case 'rescue': // last|random
					if (count($this->opponents[$playerId]->discard) == 0)
						break;
					$discardIndex = count($this->opponents[$playerId]->discard)-1;
					if ($this->getScriptValue($args[0], $card, $playerId, $context)=='random')
						$discardIndex = random_int(0, $discardIndex);
					array_push($history, new Action('rescue', array('playerId'=>$playerId,'index'=>$discardIndex)));
					$card = array_splice($this->opponents[$playerId]->discard, $discardIndex, 1)[0];
					if ($card instanceof FighterCard) $card->clear();
					array_push($this->opponents[$playerId]->hand, $card);
					break;
				case 'givecard': // player, cardId
					$card = Card::fromId($this->getScriptValue($args[1], $card, $playerId, $context));
					$receiverId = ($this->getScriptValue($args[0], $card, $playerId, $context)=='him')?$otherId:$playerId;
					array_push($history, new Action('givecard', array('playerId'=>$receiverId, 'card'=>$card->toStd())));
					array_push($this->opponents[$receiverId]->hand, $card);
					break;
				case 'retreat': // profs
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $toRetreat) {
						if ($toRetreat instanceof Player) continue;
						foreach($this->opponents as $opponentId=>$opponent)
							for ($i=0; $i<count($opponent->fighters); $i++) {
							    $fighter = $opponent->fighters[$i];
								if ($fighter !== $toRetreat) continue;
								array_push($history, new Action('retreat', array('index'=>$i, 'playerId'=>$opponentId)));
								$fighter->clear();
								array_push($opponent->hand, $fighter);
								array_splice($opponent->fighters, $i, 1);
								$i--;
								break 3;
							}
					}
					break;
				case 'makeprovoking': // profs, number
					$profs = $this->getScriptProfs($args[0], $card, $playerId, $context);
					foreach ($profs as $prof) {
						if ($prof instanceof Player) continue;
						$prof->pvq = $this->getScriptValue($args[1], $card, $playerId, $context);
						array_push($history, new Action('makeprovoking', array('target'=>$this->getFighterIndex($prof), 'pvq'=>$prof->pvq, 'agent'=>$this->getFighterIndex($card))));
					}
					break;
			}
		}
		return $history;
	}
	
	// Appliquer l'éventuel script de la carte lieu actuelle
	protected function applyPlaceScript($scriptTrigger, $context=[]) {
		if (count($this->place) === 0) return [];
		$history = [];
		$theplace = $this->place[count($this->place)-1];
		foreach ($theplace->scripts as $script) {
			if ($script->trigger == $scriptTrigger) {
				$history = array_merge($history, $this->applyScript($script, $theplace, $this->playing, $context));
			}
		}
		return $history;
	}
	
	// Passer au tour suivant
	protected function nextTurn($context) {
		$ender = $this->playing;
		$starter = $this->playing+1 >= count($this->opponents) ? 0 : $this->playing+1;
		$this->playing = $starter;
		$this->start = time();
		$this->end = time()+TURN_TIME;
		$history = [];
		foreach ($this->opponents[$ender]->fighters as $prof) { // à la fin du tour
			if ($prof->mi) $prof->mi = false;
			if ($prof->elc>0)
				$history = array_merge($history, $this->attackFighter($prof, null, $ender, ELECTRIFY_DAMAGE, true, $context)); // dégâts de l'electrisation
			foreach (array('slp', 'prl', 'efr', 'elc', 'pvq') as $effect)
				if ($prof->$effect > 0) {
					$prof->$effect--;
				}
		}
		array_push($history, new Action('nextturn', array('playing'=>$this->playing,'start'=>$this->start,'end'=>$this->end)));
		$history = array_merge($history, $this->applyPlaceScript('onturn', $context));
		if (count($this->place)>0) $this->place[count($this->place)-1]->t++;
		foreach ($this->opponents[$starter]->fighters as $prof) { // au debut du tour
			$prof->eg = false;
			$prof->t++;
			foreach ($prof->scripts as $script) {
				if ($script->trigger == 'onturn' && $this->testScriptCondition($script->condition, $prof, $starter, [])) {
					$history = array_merge($history, $this->applyScript($script, $prof, $starter, []));
				}
			}
		}
		if ($this->mL < MAX_MANA) $this->mL += .5;
		$this->opponents[$ender]->mana = intval($this->mL);
		array_push($history, new Action('setmana', array('playerId'=>$ender,'mana'=>intval($this->mL))));
		if (count($this->opponents[$ender]->deck) > 0) {
			array_push($this->opponents[$ender]->hand, $card=array_shift($this->opponents[$ender]->deck));
			array_push($history, new Action('draw', array('playerId'=>$ender, 'card'=>$card->toStd())));
		} else {
			$history = array_merge($history, $this->attackFighter($this->opponents[$ender], null, $ender, 10, true, $context));
		}
		return $history;
	}
	
	// Récupérer les profs correspondant à un selecteur
	protected function getScriptProfs($expr, $card, $playerId, $context) {
		$prof=null;
		$otherPlayerId = $playerId==0 ? 1 : 0; // en supposant qu'il n'y a que 2 joueurs
		preg_match_all('/([^\[]+)(\[([^\]]*)\]|)/', $expr, $matches);
		$condition = isset($matches[3][0]) ? $matches[3][0] : '';
		switch($matches[1][0]) {
			case 'all':
				$profs = array();
				foreach ($this->opponents as $player)
					foreach ($player->fighters as $prof)
						array_push($profs, $prof);
				break;
			case 'allofhim':
				$profs = array_reverse($this->opponents[$otherPlayerId]->fighters);
				break;
			case 'allofyou':
				$profs = array_reverse($this->opponents[$playerId]->fighters);
				break;
			case 'target': // deprecated ???
			case 'targetofhim':
				if (!isset($context['targetsofhim'][0])) throw new Exception('need targetofhim');
			    if (!$this->isFighterTargetable($context['targetsofhim'][0])) throw new Exception('invalid target');
				$profs = [$context['targetsofhim'][0]];
				break;
			case 'target2': // deprecated
			case 'target2ofhim':
				if (!isset($context['targetsofhim'][0], $context['targetsofhim'][1]))
				    throw new Exception('need target2ofhim');
				if (!$this->isFighterTargetable($context['targetsofhim'][0]) && !$this->isFighterTargetable($context['targetsofhim'][0]))
				    throw new Exception('invalid target');
				$profs = [$context['targetsofhim'][0], $context['targetsofhim'][1]];
				break;
			case 'targetofyou':
				if (!isset($context['targetsofyou'][0])) throw new Exception('need targetofyou');
				//if (!$this->isFighterTargetable($context['targetsofyou'][0])) throw new Exception('invalid target');
				$profs = [$context['targetsofyou'][0]];
				break;
			case 'target2ofyou':
				if (isset($context['targetsofyou'][1]))
					$profs = [$context['targetsofyou'][0], $context['targetsofyou'][1]];
				else throw new Exception('need target2ofyou');
				break;
			case 'it':
				$profs = array($card);
				break;
			case 'you':
				$profs = array($this->opponents[$playerId]);
				break;
			case 'him':
				$profs = array($this->opponents[$otherPlayerId]);
				break;
			case 'randomofyou':
				$profs = [];
				if (count($this->opponents[$playerId]->fighters)>0) {
					$profs = [$this->opponents[$playerId]->fighters[random_int(0,count($this->opponents[$playerId]->fighters)-1)]];
				}
				break;
			case 'randomofhim':
				$profs = [];
				if (count($this->opponents[$otherPlayerId]->fighters)>0) {
					$profs = [$this->opponents[$otherPlayerId]->fighters[random_int(0,count($this->opponents[$otherPlayerId]->fighters)-1)]];
				}
				break;
			case 'summoned':
				$profs = [$context['summoned']];
				break;
			default:
				throw new Exception('malformed prof selector : '.$expr);
		}
		return array_filter($profs, function($prof) use ($condition, $playerId, $context) {
			return !$condition || $this->testScriptCondition($condition, $prof, $playerId, $context);
		});
	}
	
	// Tester une condition
	protected function testScriptCondition($expr, $card, $playerId, $context) {
		if ($expr=='') return true;
		if (strpos($expr, '|') !== false) {
			$result = false;
			foreach (preg_split('/\|/', $expr) as $value)
				$result = $result | $this->testScriptCondition($value, $card, $playerId, $context);
			return $result;
		} else if (strpos($expr, '&') !== false) {
			$result = true;
			foreach (preg_split('/\&/', $expr) as $value)
				$result = $result & $this->testScriptCondition($value, $card, $playerId, $context);
			return $result;
		} else if (strpos($expr, '=') !== false) {
			if (strpos($expr, '!=') !== false) {
				$values = preg_split('/!=/', $expr); //Modif de Léo
				return $this->getScriptValue($values[0], $card, $playerId, $context) != $this->getScriptValue($values[1], $card, $playerId, $context) ? 1 : 0;
			} else {
				$values = preg_split('/=/', $expr);
				return $this->getScriptValue($values[0], $card, $playerId, $context) == $this->getScriptValue($values[1], $card, $playerId, $context) ? 1 : 0;
			}
		} else if (strpos($expr, '!') !== false) {
			$values = preg_split('/!/', $expr); //Modif de Léo
			return $this->getScriptValue($values[0], $card, $playerId, $context) != $this->getScriptValue($values[1], $card, $playerId, $context) ? 1 : 0;
		} else if (strpos($expr, '>') !== false) {
			$values = preg_split('/>/', $expr);
			return $this->getScriptValue($values[0], $card, $playerId, $context) > $this->getScriptValue($values[1], $card, $playerId, $context);
		} else if (strpos($expr, '<') !== false) {
			$values = preg_split('/</', $expr);
			return $this->getScriptValue($values[0], $card, $playerId, $context) < $this->getScriptValue($values[1], $card, $playerId, $context);
		} else if ($expr == 'targetsleep') {
			$target = $this->getScriptProfs('target', null, $playerId, $context)[0];
			return !($target instanceof Player) && $target->slp>0;
		}
		$words = preg_split('/_/', $expr);
		switch ($words[0]) {
			case 'isplace': // cardId
				return $this->getScriptValue($words[1], $card, $playerId, $context) == $this->place[count($this->place)-1]->id;
			case 'in': // profs, cardId
				$profs = $this->getScriptProfs($words[1], $card, $playerId, $context);
				foreach ($profs as $prof)
					if ($prof->id == $this->getScriptValue($words[2], $card, $playerId, $context))
						return true;
				return false;
			case 'hastype':
			    if ($card instanceof FighterCard) return in_array($words[1], $card->types);
			    return $words[1]=='player';
			case 'hasnottype':
			    if ($card instanceof FighterCard) return !in_array($words[1], $card->types);
			    return $words[1]!='player';
		}
	}
	
	// Récuperer une valeur
	protected function getScriptValue($expr, $card, $playerId, $context) {
		if (is_numeric($expr)) return intval($expr);
		if (strpos($expr, '+') !== false) {
			$result = 0;
			foreach (preg_split('/\+/', $expr) as $value)
				$result += $this->getScriptValue($value, $card, $playerId, $context);
			return $result;
		} else if (strpos($expr, '-') !== false) {
			$result = 0;
			foreach (preg_split('/\-/', $expr) as $i=>$value) {
				if ($i==0)
					$result = $value=='' ? 0 : $this->getScriptValue($value, $card, $playerId, $context);
				else
					$result -= $this->getScriptValue($value, $card, $playerId, $context);
			}
			return $result;
		} else if (strpos($expr, '*') !== false) {
			$result = 1;
			foreach (preg_split('/\*/', $expr) as $value)
				$result *= $this->getScriptValue($value, $card, $playerId, $context);
			return $result;
		} else if (strpos($expr, '%') !== false) {
			$result = 1;
			foreach (preg_split('/%/', $expr) as $i=>$value) {
				if ($i==0)
					$result = $this->getScriptValue($value, $card, $playerId, $context);
				else
					$result %= $this->getScriptValue($value, $card, $playerId, $context);
			}
			return $result;
		}
		switch ($expr) {
			case 'type':
				if ($card instanceof FighterCard)
					return $card->types[0];
				else
					return 'player'; //Modif de Léo
			case 'place':
				if (count($this->place) == 0)
					return -1;
				return $this->place[count($this->place)-1]->id;
		}
		$words = preg_split('/_/', $expr);
		switch ($words[0]) {
			case 'getvar':
				$varname = $words[1];
				if (!isset($card->vars->$varname)) $card->vars->$varname = 0;
				return $card->vars->$varname;
			case 'random':
				return random_int(0, $this->getScriptValue(preg_replace('/'.preg_quote($words[0],'/').'_/', '', $expr, 1), $card, $playerId, $context));
			case 'count':
				return count($this->getScriptProfs(preg_replace('/'.preg_quote($words[0],'/').'_/', '', $expr, 1), $card, $playerId, $context));
		}
		if (isset($card->$expr)) return $card->$expr;
		return $expr;
	}
	
	function isFighterTargetable($fighter) {
	    if ($fighter instanceof Player || $fighter->pvq > 0) return true; // Règle de la provocation
		foreach ($this->opponents as $opponent)
			if (in_array($fighter, $opponent->fighters, true))
				foreach ($opponent->fighters as $f)
					if ($f->pvq > 0)
						return false;
		return true;
	}
	
	// Faire jouer le bot
	public function bot($botId, $previousContext) {
		$otherId = $botId==0 ? 1 : 0;
		//$this->opponents[$botId]->hand // main du bot
		//$this->opponents[$botId]->fighters // combattants sur le terrain du bot
		//$context['targetsofhim'] = array($z); // pour définir une cible $z lors d'une attaque
		//$context['targetsofyou'] = array($z, $y) // pour définir une double cible ennemi $z & $y (genre l'ancien abo grev.)
		//$this->playCard($botId, N, $context); // jouer la N-ième carte de la main du bot
		//$this->attack($botId, N, M, $context); // attaquer avec le N-ème combattant avec la M-ième attaque
		//count($this->opponents[$botId]->hand) // nombre de cartes en main
		//random_int($n, $m) // nombre entier aléatoire entre $n et $m inclus
		// si besoin d'une fonction php : https://www.php.net/manual/fr/ OU moteur de recherche genre Google
		// si besoin d'une fonction pokéProf, bah cf Discord ou text me
		
		// à toi de jouer
		
		$context = array();
		$context['playerIds'] = $previousContext['playerIds'];
		$context['trophies'] = $previousContext['trophies'];
		
		// le bot joue des cartes
		global $test;
		
		$this->botApplyStrat ($context,$botId, $otherId, $this->smartMana($botId, 1, $context));
		/*else {
			if (random_int(0, 1) == 0) {
				for ($i = 0; $i < count($this->opponents[$botId]->hand); $i++) {
					if ($this->opponents[$botId]->hand[$i]->cost <= $this->opponents[$botId]->mana) {
						if ($this->opponents[$botId]->mana <= 4 && $this->opponents[$botId]->hand[$i] instanceof EffectCard)
							continue;
						// test des scripts et choix des cibles
						$context['targetsofyou'] = [];
						$context['targetsofhim'] = [];
						foreach ($this->opponents[$botId]->hand[$i]->scripts as $script) {
							$context = $this->chooseTargets($context, $botId, $otherId, $script);
							if ($script->trigger=='onplaycard' && (!$this->testScriptCondition($script->condition,$this->opponents[$botId]->hand[$i],$botId,$context) || $script->needTargetOfYou()>count($context['targetsofyou']) || ($script->needTargetOfHim()>1 && $script->needTargetOfHim()>count($context['targetsofhim'])))) {
								continue 2;
							}
						}
						// joue la carte
						$this->playCard($botId, $i, $context);
					}
				}
			} else {
				for ($i = count($this->opponents[$botId]->hand)-1; $i >= 0; $i--) {
					if ($this->opponents[$botId]->hand[$i]->cost <= $this->opponents[$botId]->mana) {
						if ($this->opponents[$botId]->mana <= 4 && $this->opponents[$botId]->hand[$i] instanceof EffectCard)
							continue;
						// test des scripts et choix des cibles
						$context['targetsofyou'] = [];
						$context['targetsofhim'] = [];
						foreach ($this->opponents[$botId]->hand[$i]->scripts as $script) {
							$context = $this->chooseTargets($context, $botId, $otherId, $script);
							if ($script->trigger=='onplaycard' && (!$this->testScriptCondition($script->condition,$this->opponents[$botId]->hand[$i],$botId,$context) || $script->needTargetOfYou()>count($context['targetsofyou']) || ($script->needTargetOfHim()>1 && $script->needTargetOfHim()>count($context['targetsofhim'])))) {
								continue 2;
							}
						}
						// joue la carte
						$this->playCard($botId, $i, $context);
					}
				}
			}
		}*/
		
		// le bot fait attaquer ses combattants
		foreach ($this->opponents[$botId]->fighters as $fi=>$fighter) {
			if ($fighter->eg || $fighter->slp>0 || $fighter->prl>0 || $fighter->mi || $fighter->efr>0) // passage au combattant suivant car engagé, endormi, paralysé, récement invoqué ou effrayé
				continue;
			// choix de l'attaque
			$avalaibleAttacks = [];
			$context['targetsofyou'] = [];
			$context['targetsofhim'] = [];
			$randomattack=0;
			foreach ($fighter->scripts as $j=>$script) {
				if ($script->trigger!='onaction')
					continue;
				$context = $this->chooseTargets($context, $botId, $otherId, $script);
				if ($script->needTargetOfYou()<=count($context['targetsofyou']) && ($script->needTargetOfHim()<=count($context['targetsofhim'])) && $this->testScriptCondition($script->condition, $fighter, $botId, $context)) {
					array_push($avalaibleAttacks, $j);
				}
			}
			if (count($avalaibleAttacks) == 0)
				continue;
			$randomattack = $avalaibleAttacks[random_int(0, count($avalaibleAttacks)-1)];
		
			// choix des cibles et attaque
			$context['targetsofyou'] = [];
			$context['targetsofhim'] = [];
			$context = $this->chooseTargets($context, $botId, $otherId, $fighter->scripts[$randomattack]);
			$fighterIndex = array_search($fighter, $this->opponents[$botId]->fighters, true);
			if ($fighterIndex!==false)
    			$this->attack($botId, $fighterIndex, $randomattack, $context); // invalid index pour l'index du combattant
		}
		$this->endTurn($botId, $context);
	}
	
	//DEBUT fonctions Léo
	// Créer une stratégie de pose de cartes en optimisant la mana et le nombre de cartes jouées
	function smartMana($playerId, $order, $context) {
		$cards2play=array();
		$manaTmp=0;
		$j=0;
		$n=0;
		$finalStrat=array();
		$condition=1;
		if (true) { //on utilisera $order ici
			for ($i = 0; $i < count($this->opponents[$playerId]->hand); $i++) {
			    global $test;
			    if ($test) {
			        $condition=1;
			        foreach ($this->opponents[$playerId]->hand[$i]->scripts as $script) {
			            if (($script->trigger == 'onplaycard') && ($condition)) {
			                $condition=$this->testScriptCondition($script->condition, $this->opponents[$playerId]->hand[$i], $playerId, $context);
			            }
			        }
			    }
				if ($this->opponents[$playerId]->hand[$i]->cost <= $this->opponents[$playerId]->mana && $condition) {
					if ($this->opponents[$playerId]->mana <= 4 && $this->opponents[$playerId]->hand[$i] instanceof EffectCard)
						continue;
					$cards2play[$n]=array();
					$cards2play[$n][]=$i;
					$manaTmp=($this->opponents[$playerId]->mana)-($this->opponents[$playerId]->hand[$i]->cost);
					$j=$i+1;
					while ($j < count($this->opponents[$playerId]->hand) && $manaTmp > 0) {
						if ($this->opponents[$playerId]->hand[$j]->cost <= $manaTmp) {
							if (!($this->opponents[$playerId]->mana <= 4 && $this->opponents[$playerId]->hand[$j] instanceof EffectCard)) {
									$cards2play[$n][]=$j;
									$manaTmp=$manaTmp-($this->opponents[$playerId]->hand[$j]->cost);
							}
						}
						$j++;
					}
					$n+=1;
				}
			}
		}
		//print_r($cards2play);
		if (isset($cards2play[0])) {
		    if ($this->botDifficult<3) {
		        return ($cards2play[random_int(0,count($cards2play)-1)]);
		    }
			$j=$this->opponents[$playerId]->mana;
			$finalStrat=$cards2play[0];
			foreach ($cards2play as $strats) {
				$manaTmp=$this->opponents[$playerId]->mana;
				for ($i=0; $i < count($strats); $i++) {
					$manaTmp-=($this->opponents[$playerId]->hand[$strats[$i]]->cost);
				}
				if (count($finalStrat) <= count($strats)) {
					if ((count($finalStrat) == count($strats)) && ($j > $manaTmp)) {
						$finalStrat=$strats;
						$j=$manaTmp;
					}
					else if (count($finalStrat) < count($strats)) {
						$finalStrat=$strats;
						$j=$manaTmp;
					}
				}
			}
			return $finalStrat;
		}
		return NULL;
	}
	
	// Applique une stratégie de cartes à poser
	function botApplyStrat ($context,$botId, $otherId, $cards2play) {
		$indexCard=0;
		if ($cards2play!=NULL) {
			for ($i = count($cards2play)-1 ; $i >= 0 ; $i--) {
				$indexCard=$cards2play[$i];
				$context['targetsofyou'] = [];
				$context['targetsofhim'] = [];
				foreach ($this->opponents[$botId]->hand[$indexCard]->scripts as $script) {
					$context = $this->chooseTargets($context, $botId, $otherId, $script);
					if ($script->trigger=='onplaycard' && (!$this->testScriptCondition($script->condition,$this->opponents[$botId]->hand[$indexCard],$botId,$context) || $script->needTargetOfYou()>count($context['targetsofyou']) || ($script->needTargetOfHim()>1 && $script->needTargetOfHim()>count($context['targetsofhim'])))) {
							continue 2;
						}
					}
					// joue la carte
					$this->playCard($botId, $indexCard, $context);
			}
		}
	}
	//FIN fonctions Léo
	
	// Choisir des cibles aléatoires en fonction du script
	function chooseTargets($context, $playerId, $opponentId, $script) {
	    $choix=0;
		for ($j = count($context['targetsofyou']); $j < min($script->needTargetOfYou(), count($this->opponents[$playerId]->fighters)); $j++)
			$context['targetsofyou'][$j] = $this->opponents[$playerId]->getRandomFighter($context['targetsofyou'], true);
		for ($j = count($context['targetsofhim']); $j < min($script->needTargetOfHim(), count($this->opponents[$opponentId]->fighters)+1); $j++) {
		    $choix=0;
		    global $test;
		    switch ($this->botDifficult) {
		        case 0 :
		            $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, false, true, false) : $this->opponents[$opponentId];
		            break;
		        case 1 :
		            if (random_int(0,1)) {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, true, false, false) : $this->opponents[$opponentId];
		            } else {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, false, true, false) : $this->opponents[$opponentId];
		            }
		            break;
		        case 2 :
		            $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, true, false, false) : $this->opponents[$opponentId];
		            break;
		        case 3 :
		            foreach ($script->functions as $f) {
		                if (($f->name=='affraid')||(($f->name=='sleep')&&($f->args[0]!='it'))||($f->name=='paralyse')) {
		                    $choix=1;
		                }
		            }
		            if ($choix==1) {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, true, false, false) : $this->opponents[$opponentId];
		            } else {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, false, true, false) : $this->opponents[$opponentId];
		            }
		            break;
		        case 4 :
		            foreach ($script->functions as $f) {
		                if (($f->name=='affraid')||(($f->name=='sleep')&&($f->args[0]!='it'))||($f->name=='paralyse')) {
		                    $choix=1;
		                }
		            }
		            if ($choix==1) {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, true, false, true) : $this->opponents[$opponentId];
		            } else {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, false, true, false) : $this->opponents[$opponentId];
		            }
		            break;
		        case 5 :
		            foreach ($script->functions as $f) {
		                if (($f->name=='affraid')||(($f->name=='sleep')&&($f->args[0]!='it'))||($f->name=='paralyse')) {
		                    $choix=1;
		                }
		            }
		            if ($choix==1) {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, false, true, true) : $this->opponents[$opponentId];
		            } else {
		                $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getAllFighter($context['targetsofhim'], true, false, true, false) : $this->opponents[$opponentId];
		            }
		            break;
		        default :
		            $context['targetsofhim'][$j] = count($this->opponents[$opponentId]->fighters)>$j ? $this->opponents[$opponentId]->getRandomFighter($context['targetsofhim'], true) : $this->opponents[$opponentId];
		            break;
		            
		    }
		}
		return $context;
	}
	
}

?>