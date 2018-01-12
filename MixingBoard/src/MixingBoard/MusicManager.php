<?php
namespace MixingBoard;

# Basic
use pocketmine\Player;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\StopSoundPacket;


class MusicManager {

	const DATA_PLAYER = 0;
	const DATA_ENDTIME = 1;
	const DATA_SPEAKER_NO = 2;
	const DATA_MUSIC_NO = 3;
	const DATA_PLAY_FLAG = 4;


	/**
	*	スピーカー = 座標郡 から player　一人に対して 音を鳴らす
	*	@param Player
	*	@param Music 	Music::getMusic()
	*	@param Speaker 	Speaker::getSpeaker()
	*/
	public static function playIndividual(Player $player, Music $music = null, Speaker $speaker = null){

		if(!$music) $music = Music::getMusic(1);
		if(!$speaker) $speaker = Speaker::getSpeaker(1);

		// 現在プレイしていれば
		$name = strtolower($player->getName());
		if(isset(self::$playing[$name])){
			self::stopIndividual($player);
		}

		// スタートパケット
		$speakers = $speaker->getAllSpeakerInTheField();
		foreach($speakers as $s){
			$pk = new PlaySoundPacket;
			$pk->soundName = $music->getName();
			echo $music->getName(), " {$player->x} {$player->y} {$player->z}\n";
			/*
			$pk->x = $s[0];
			$pk->y = $s[1];
			$pk->z = $s[2];
			*/
			// とりあえずスピーカー関係なしに流す
			$pk->x = (int) $player->x;
			$pk->y = (int) $player->y;
			$pk->z = (int) $player->z;
			$pk->volume = (float) 400;
			$pk->pitch = (float) 1;
			$player->directDataPacket($pk);
		}

		$player->sendMessage("『".$music->getTitle()."』を再生中");

		// 情報記憶 時間、すぴーか、きょくめい
		$endTime = time() + $music->getMusicLength();
		self::$playing[$name] = [
			$player,
			$endTime,
			$speaker->getNo(),
			$music->getNo(),
			1, //プレイ中かどうか　1=プレイ中 0=一時停止
		];

		// こっちの曲のほうが終わるのはやければこっちの曲の時間を登録
		self::$nexttick = self::$nexttick === -1 ? $endTime : min($endTime, self::$nexttick);

	}


	/**
	*	プレイヤーを指定して流れている曲を止める
	*	退出時にはここを使ってもよいが、サバ川ではプレイヤーが消えると自動で止めるようにしてあるので
	*/
	public static function stopIndividual(Player $player){
		$name = strtolower($player->getName());
		if(isset(self::$playing[$name])){
			if(self::$playing[$name][self::DATA_PLAY_FLAG]){
				echo "stopped\n";
				$pk = new StopSoundPacket;
				$pk->soundName = Music::getMusic(self::$playing[$name][self::DATA_MUSIC_NO])->getName();
				$pk->stopAll = true;
				$player->directDataPacket($pk);

				self::$playing[$name][self::DATA_PLAY_FLAG] = 0;
			}
		}
	}


	/**
	*	そのスピーカーを無効化して音を消す
	*/
	public static function stopSpeaker($speakerNo){

	}



	/**
	*	ループ再生用の関数 これが Eard/Enemys/Spawn により数tick毎に実行される
	*/
	public static function tick(){
		// 次の曲が終わる時間を過ぎていたら
		if(time() < self::$nexttick) return;

		// 全プレイヤーでるーぷ
		$next = -1;
		foreach(self::$playing as $name => $playerData){
			// オフライン対策
			if(!$playerData[self::DATA_PLAYER]->isOnline()){
				unset(self::$playing[$name]);
			}else{
				if($playerData[self::DATA_PLAY_FLAG] === 1 && $playerData[self::DATA_ENDTIME] <= time()){
					// もっかい再生 曲を止める処理はplayIndividualの中で行っている
					//echo "playing again\n";
					self::playIndividual(
						$playerData[self::DATA_PLAYER],
						Music::getMusic($playerData[self::DATA_MUSIC_NO]),
						Speaker::getSpeaker($playerData[self::DATA_SPEAKER_NO])
					);
				}else{
					$next = $next === -1 ? $playerData[self::DATA_ENDTIME] : min($playerData[self::DATA_ENDTIME], $next);
				}
			}
		}
		self::$nexttick = $next;
	}

	public static $playing = []; // データ格納
	public static $nexttick = -1; // 次、曲のリセットがかかるのはいつか

}


class Music {
	// class ごとにわけようとおもったけどめんどくさいので


	public function __construct($musicNo){
		$this->musicNo = $musicNo;
	}

	// 曲データを取得
	public static function getMusic($musicNo){
		if($musicNo === 0) return null;
		if(!isset(self::$musics[$musicNo])){
			$m = new Music($musicNo);
			self::$musics[$musicNo] = $m;
		}
		return self::$musics[$musicNo];
	}

	// 曲番号
	public function getNo(){
		return $this->musicNo;
	}

	// 普通に曲名
	public function getTitle(){
		return self::$list[$this->musicNo][0];
	}

	// 曲の識別子 (パックの、musics/sound_definition)
	public function getName(){
		return self::$list[$this->musicNo][1];
	}

	// 曲の長さ
	public function getMusicLength(){
		return self::$list[$this->musicNo][2];
	}


	// データリスト
	public static $list = [
		0 => ["曲名","soundpackのなまえ","曲名を秒数で"],
		1 => ["エリーゼのために","music.erize", 162],
		2 => ["STELLAR ORDER","music.stellarorder", 187],
		3 => ["ULTIMATE ORDER","music.ultimateorder", 310],

	];
	public static $musics = [];

}



class Speaker {

	const FIELD_LOBBY = 1;
	const FIELD_STAGE_1 = 2;

	public function __construct($speakerNo){
		$this->speakerNo = $speakerNo;
	}

	// 曲データを取得
	public static function getSpeaker($speakerNo){
		if($speakerNo === 0) return null;
		if(!isset(self::$speakers[$speakerNo])){
			$m = new Speaker($speakerNo);
			self::$speakers[$speakerNo] = $m;
		}
		return self::$speakers[$speakerNo];
	}

	public function getNo(){
		return $this->speakerNo;
	}

	public function getAllSpeakerInTheField(){
		return self::$list[$this->speakerNo];
	}


	// データリスト
	public static $list = [
		[[]],
		[[]],
		//[[253,70,249], [245,70,249] ],
		[[252,70,232] ],
	];
	public static $speakers = []; // オブジェクト格納

}