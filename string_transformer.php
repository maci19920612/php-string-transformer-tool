<?php
interface ICommand{
	function execute($params);
	function getCommandName();
}
abstract class ACommand implements ICommand{
	function execute($args){
		$parsedArgs = [];
		foreach($args as $arg){
			$explodedArg = explode("=",$arg);
			$key = array_shift($explodedArg);
			$parsedArgs[$key] = implode("=",$explodedArg);
		}
		$requiredParameters = $this->requiredParameters();
		if(is_array($requiredParameters)){
			foreach($requiredParameters as $parameter){
				if(!isset($parsedArgs[$parameter])){
					echo "A kovetkezo parameter ennel a parancsnal kotelezo: " . implode(",",$requiredParameters);
					return;
				}
			}
		}
		$this->executeCommand($parsedArgs);
	}
	abstract function requiredParameters();
	abstract function executeCommand($args);
}


class CommandExecuter{
	private static $_instance = null;
	public static function getInstance(){
		if(self::$_instance == null) self::$_instance = new CommandExecuter();
		return self::$_instance;
	}
	public static function registerCommand($executer){
		if(self::$_instance == null) self::$_instance = new CommandExecuter();
		if(!($executer instanceof ICommand)){
			echo "A parancs nem adhato hozza!";
			return;
		}
		self::$_instance->executers[$executer->getCommandName()] = $executer;
	}
	private function __construct(){}
	private function addCommand($command){
		if(!($command instanceof ICommand)){
			echo "A parancs nem adhato hozza!";
			return;
		}
		$this->executers[$command->getCommandName()] = $command;
	}

	private $executers = [];
	public function getRegisteredCommandExecuters(){
		return $this->executers;
	}
	public function execute($argArray){
		if(!is_array($argArray) || count($argArray) == 1){
			echo "A program nem indidhato el parameterek nelkul!";
			return;
		}
		array_shift($argArray);
		$command = array_shift($argArray);
		if(!isset($this->executers[$command])){
			echo "A kert parancs nem talalhato: " . $command;
			return;
		}
		$this->executers[$command]->execute($argArray);
	}
}


class MergeCommand extends ACommand{
	public function executeCommand($params){
		$source = json_decode(file_get_contents($params["source"]),true);
		$target = file_get_contents($params["target"]);	
		$localizations = $source["ClientLocalizations"];



		foreach($localizations as $loc){
			$key = strtolower($loc["ResName"]);
			$value = $loc["ResValue"];
			if(strlen(trim($value)) == 0){
				continue;
			}
			if(strpos($value,"DOCTYPE") !== false && strpos($value,"CDATA") === false){
				$value = "<![CDATA[" . $value . "]]>";
				//echo $value . "\n\n\n";
			}
			$count = 0;
			$target = preg_replace('/<string name="'.$key.'">.*<\/string>/misU','<string name="'.$key.'">'.$value.'</string>',$target,-1,$count);
			echo $key . " " .  $count . "\n";
		}

		file_put_contents($params["target"],$target);
	}
	public function requiredParameters(){
		return ["source","target"];
	}
	public function getCommandName(){
		return "merge";
	}
}
CommandExecuter::registerCommand(new MergeCommand());

CommandExecuter::getInstance()->execute($argv);

echo "\n\n";
?>
