<?php

namespace bingbing;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use ojy\cash\Cash;

class BingBingCashShop extends PluginBase implements Listener{
    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->data = new Config($this->getDataFolder()."shopData.json", Config::YAML , []);
        $this->db = $this->data->getAll();
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool {
        if ($sender instanceof Player){
            if ($command == "ĳ������"){
                if ($sender->isOp()){
                    $pk = new ModalFormRequestPacket();
                    $pk->formId = 202003171;
                    $pk->formData = $this->shopOPJSON();
                    $sender->sendDataPacket($pk);
                    $sender->sendMessage("��");
                    return true;
                }
                else {
                    $pk = new ModalFormRequestPacket();
                    $pk->formId = 202003174;
                    $pk->formData = $this->shopJSON();
                    $sender->sendDataPacket($pk);
                    return true;
                    
                }      return true;
            }      return true;
        }      return true;
    }
    public function respose (DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        $player = $event->getPlayer();
        if ($pk instanceof ModalFormResponsePacket ){
            $result = json_decode($pk->formData , true);
            switch ($pk->formId){
                case 202003171:
                    if ($result === 0){
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202003172;
                        $pk->formData = $this->makeSHOP();
                        $player->dataPacket($pk);
                    }
                    else if($result === 1){
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202003173;
                        $pk->formData = $this->deleteSHOP();
                        $player->dataPacket($pk);
                    }
                    else if ($result === 2){
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202003174;
                        $pk->formData = $this->shopJSON();
                        $player->dataPacket($pk);
                    }
                    break;
                case 202003172:
                    if ( isset ( $result[0] ) && isset ( $result[1] ) && isset ( $result[2] )  ){
                        array_push($this->db , ["name" => $result[0] , "price" => $result[1] , "item" => $result[2]]);
                        $this->save();
                    }
                    else {
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202003175;
                        $pk->formData = $this->info("��� �� �Է��Ͻð� �����⸦ �����ּ���.");
                        $player->dataPacket($pk);
                        
                    }
                    break;
                case 202003173:
                    unset($this->db[$result]);
                    $this->db = array_values($this->db);
                    break;
                case 202003174:
                    $item = explode(":", $this->db[(int)$result]["item"]);
                    
                    
                    if (Cash::getCash($player->getName()) >= $this->db[(int)$result]["price"] ){
                        $i = new Item($item[0] , $item[1] , $item[2]);
                        $i->setCustomName($this->db[(int)$result]["name"]);
                        $player->getInventory()->addItem($i);
                        Cash::reduceCash($player->getName(), $this->db[(int)$result]["price"]);
                        $player->addTitle("���� �Ϸ� ");
                    }
                    break;
            }
        }
    }
    public function info ($string){
        $value = [
            
            "type" => "custom_form",
            "title" => "���� ĳ������ UI",
            "content" => [
                "type"=> "label",
                "text" => $string
                
            ]
            
        ];
        return json_encode($value);
    }
    public function shopOPJSON(){
        $value = [
            "type"=>"form" ,
            "title" => "ĳ������UI" ,
            "content"=> "�޴��� �� ������",
            "buttons" => [
                [
                    
                    'text' => '��ǰ ����'
                    
                ],
                [
                    
                    'text' => '��ǰ ����',
                ] ,
                [
                    "text" => "��ǰ ����"
                    
                ]
                
            ]
            
        ];
        return json_encode($value);
        
    }
    
    public function shopJSON(){
        $button = [];
        for ( $a =0; $a < count ( $this->db ); $a++){
            array_push($button ,  [
                
                "text" => $this->db[$a]["name"] ."\n ��ǰ ���� : ".$this->db[$a]["price"] ."��"
                
                
            ]);
        }
        $value = [
            "type"=>"form" ,
            "title" => "ĳ������UI" ,
            "content"=> "�޴��� �� ������",
            "buttons" =>
            $button
            
            
        ];
        return json_encode($value);
        
    }
    public function makeSHOP(){
        $value = [
            "type" => "custom_form",
            "title" => "ĳ������UI" ,
            "content"=> [
                [
                    'type' => 'input',
                    'text' => '��ǰ �̸�',
                    
                ],
                [
                    'type' => 'input',
                    'text' => '��ǰ ����',
                    
                ],
                [
                    'type' => 'input',
                    'text' => '��ǰ �������ڵ� ',
                    "default" => "339:0:1"
                    
                ],
            ]
        ];
        return json_encode($value);
    }
    public function deleteSHOP(){
        $button = [];
        for ( $a =0; $a < count ( $this->db ); $a++){
            array_push($button ,  [
                
                "text" => $this->db[$a]["name"]
                
                
            ]);
        }
        $value = [
            "type"=>"form" ,
            "title" => "ĳ������UI" ,
            "content"=> "��ư�� ������ �����˴ϴ�",
            "buttons" =>
            $button
            
            
            
        ];
        return json_encode($value);
    }
    public function onDisable(){
        $this->save();
    }
    public function save(){
        $this->data->setAll($this->db);
        $this->data->save();
    }
}