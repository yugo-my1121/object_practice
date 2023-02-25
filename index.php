<?php
  ini_set('log_errors','on');//ログを取るか
  ini_set('error_log','php.log');//ログの出力場所指定
  session_start();//セッションを使う

  //モンスター格納用
  $monsters = array();

  //性別クラス
  class Sex{
    const MAN = 1;
    const WOMAN = 2;
    const OKAMA =3;
  }

  //抽象クラス(生き物クラス)
  abstract class Creature{
    protected $name;
    protected $maxHp;
    protected $hp;
    protected $attackMin;
    protected $attackMax;
    abstract public function sayCry();
    public function setName($str){
      $this->name = $str;
    }
    public function getName(){
      return $this->name;
    }
    public function setHp($num){
      $this->hp = $num;
    }
    public function getHp(){
      return $this->hp;
    }
    public function getMaxHp(){
      return $this->maxHp;
    }
    public function attack($targetObj){
      $attackPoint = mt_rand($this->attackMin,$this->attackMax);
      if(!mt_rand(0,9)){//10分の1の確率でクリティカル
        $attackPoint = $attackPoint*1.5;
        $attackPoint = (int)$attackPoint;
        History::set($this->getName().'のクリティカルヒット!!');
      }
      $targetObj->setHp($targetObj->getHp()-$attackPoint);
      History::set($attackPoint.'ポイントのダメージ!');
    }
  }



  //人クラス
  class Human extends Creature{
    protected $sex;
    public function __construct($name,$sex,$maxHp,$hp,$attackMin,$attackMax){
      $this->name = $name;
      $this->sex = $sex;
      $this->maxHp = $maxHp;
      $this->hp = $hp;
      $this->attackMin = $attackMin;
      $this->attackMax = $attackMax;
    }
    public function setSex($num){
      $this->sex = $num;
    }
    public function getSex(){
      return $this->sex;
    }
    public function sayCry(){
      History::set($this->name.'が叫ぶ');
      switch($this->sex){
        case Sex::MAN :
          History::set('ぐはぁっ!');
          break;
        case Sex::WOMAN :
          History::set('きゃっあ!');
          break;
        case Sex::OKAMA :
          History::set('もっと!');
          break;
      }
    }

  }

  //モンスタークラス
  class Monster extends Creature{
    //プロパティ
    protected $img;
    //コンストラクタ
    public function __construct($name,$hp,$img,$attackMin,$attackMax){
      $this->name = $name;
      $this->hp = $hp;
      $this->img = $img;
      $this->attackMin = $attackMin;
      $this->attackMax = $attackMax;
    }
    //ゲッター
    public function getImg(){
      return $this->img;
    }
    public function sayCry(){
      History::set($this->name.'が叫ぶ!');
      History::set('はうっ!');
    }
  }
  //空を飛べるモンスターの追加
  class skyMonster extends Monster{
    public function __construct($name,$hp,$img,$attackMin,$attackMax){
      $this->name = $name;
      $this->hp = $hp;
      $this->img = $img;
      $this->attackMin = $attackMin;
      $this->attackMax = $attackMax;
    }
    public function attack($targetObj){
      $attackPoint = mt_rand($this->attackMin,$this->attackMax);
      if(!mt_rand(0,2)){//3分の1の確率で
        $attackPoint = $attackPoint*1.2;
        $attackPoint = (int)$attackPoint;
        $targetObj->setHp($targetObj->getHp()-$attackPoint);
        $this->setHp($this->getHp()-20);//自分に20のダメージ
        History::set($this->name.'の空からの体当たり攻撃!');
        History::set($targetObj->name.'に'.$attackPoint.'ポイントダメージ!');
        History::set($this->name.'は反動のダメージ!');
      }else{
        parent::attack($targetObj);
      }
    }
  }

  //魔法を使えるモンスタークラス
  class MagicMonster extends Monster{
    private $magicAttack;
    function __construct($name,$hp,$img,$attackMin,$attackMax,$magicAttack){
      parent::__construct($name,$hp,$img,$attackMin,$attackMax);
      $this->magicAttack = $magicAttack;
    }
    public function getMagicAttack(){
      return $this->magicAttack;
    }
    public function attack($targetObj){
      if(!mt_rand(0,4)){//5分の1の確率で魔法攻撃
        History::set($this->name.'の魔法攻撃!!');
        $targetObj->setHp($targetObj->getHp()-$this->magicAttack);
        History::set($this->magicAttack.'ポイントのダメージを受けた!');
      }else{
        parent::attack($targetObj);
      }
    }
  }

  interface HistoryInterface{
    public function set();
    public function clear();
  }

  // 履歴管理クラス（インスタンス化して複数に増殖させる必要性がないクラスなので、staticにする）
  class History{
    public static function set($str){
      // セッションhistoryが作られてなければ作る
      if(empty($_SESSION['history'])) $_SESSION['history'] = '';
      //文字列をセッションhistoryへ格納
      $_SESSION['history'] .= $str.'<br>';
    }
    public static function clear(){
      unset($_SESSION['history']);
    }
  }

  //インスタンス作成
  $human = new Human('勇者見習い', Sex::MAN,500, 500, 40, 120);
  $monsters[] = new Monster('フランケン',100,'img/monster01.png',20,40);
  $monsters[] = new MagicMonster('フランケンNEO',300,'img/monster02.png',20,60,mt_rand(50,100));
  $monsters[] = new Monster('ドラキュラー',200,'img/monster03.png',30,50);
  $monsters[] = new MagicMonster('ドラキュラー男爵',400,'img/monster04.png',50,100,mt_rand(60,120));
  $monsters[] = new Monster('スカルフェイス',150,'img/monster05.png',30,60);
  $monsters[] = new Monster('毒ハンド',100,'img/monster06.png',10,30);
  $monsters[] = new Monster('泥ハンド',120,'img/monster07.png',20,30);
  $monsters[] = new Monster('血のハンド',180,'img/monster08.png',30,50);
  $monsters[] = new skyMonster('モーモン',200,'img/monster09.png',40,60);

  function createMonster(){
    global $monsters;
    $monster = $monsters[mt_rand(0,8)];
    History::set($monster->getName().'が現れた!');
    $_SESSION['monster'] = $monster;
  }
  function createHuman(){
    global $human;
    $_SESSION['human'] = $human;
  }

  function init(){
    History::clear();
    History::set('初期化します!');
    $_SESSION['knockDownCount'] = 0;
    $_SESSION['recoverCount'] = 0;
    createHuman();
    createMonster();
  }
  function gameOver(){
    $_SESSION = array();
  }

  //post送信されていた場合
  if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true : false;
    $startFlg = (!empty($_POST['start'])) ? true : false;
    $recoverFlg = (!empty($_POST['recover'])) ? true : false;
    error_log('POSTされた!');

    if($startFlg){
      History::set('ゲームスタート!');
      init();
    }else{
      if($attackFlg){
        //モンスターに攻撃を与える
        History::set($_SESSION['human']->getName().'の攻撃!');
        $_SESSION['human']->attack($_SESSION['monster']);
        $_SESSION['monster']->sayCry();

        //モンスターから攻撃を受ける
        History::set($_SESSION['monster']->getName().'の攻撃!');
        $_SESSION['monster'] ->attack($_SESSION['human']);
        $_SESSION['human']->sayCry();

        //hpが0以下になったらゲームオーバー
        if($_SESSION['human']->getHp() <= 0){
          gameOver();
        }else{
          if($_SESSION['monster']->getHp() <= 0){
            History::set($_SESSION['monster']->getName().'を倒した!');
            createMonster();
            $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
          }
        }

      }else if($recoverFlg){//回復するを押した場合
        if($_SESSION['human']->getHp() < $_SESSION['human']->getMaxHp()){//最大HP以下の時に回復を実行できる
          if($_SESSION['recoverCount']<3){
            //勇者を回復する
            $_SESSION['human']->setHp($_SESSION['human']->getHp()+mt_rand(10,100));
            if($_SESSION['human']->getHp()) $_SESSION['human']->setHp($_SESSION['human']->getMaxHp());
            //初期HPを超えていたら初期HPを入れる
            $_SESSION['recoverCount'] += 1;
          }
        }

      }else{//逃げるを押した場合
        History::set('逃げた!');
        createMonster();
      }
      $_POST = array();
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ゲーム</title>
  <style>
    body{
	    	margin: 0 auto;
	    	padding: 150px;
	    	width: 25%;
	    	background: #fbfbfa;
        color: white;
    	}
    	h1{ color: white; font-size: 20px; text-align: center;}
      h2{ color: white; font-size: 16px; text-align: center;}
    	form{
	    	overflow: hidden;
    	}
    	input[type="text"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
      input[type="password"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
    	input[type="submit"]{
	    	border: none;
	    	padding: 15px 30px;
	    	margin-bottom: 15px;
	    	background: black;
	    	color: white;
	    	float: right;
    	}
    	input[type="submit"]:hover{
	    	background: #3d3938;
	    	cursor: pointer;
    	}
    	a{
	    	color: #545454;
	    	display: block;
    	}
    	a:hover{
	    	text-decoration: none;
    	}
  </style>
</head>
<body>
  <h1 style="text-align:center; color:#333;">ゲーム 『ドラクエ』</h1>
  <div style="background:black; padding:15px; position:relative;">
    <?php if(empty($_SESSION)){?>
      <h2 style="margin-top:60px;">GAME START ?</h2>
      <form method="post">
        <input type="submit" name="start" value="▶︎ゲームスタート">
      </form>
    <?php }else{?>
      <h2><?php echo $_SESSION['monster']->getName().'が現れた!!';?></h2>
      <div style="height:150px;">
        <img src="<?php echo $_SESSION['monster']->getImg();?>" style="width:120px; height:auto; margin:40px auto 0 auto; display:block;">
      </div>
      <p style="font-size:14px; text-align:center; ">モンスターのHP:<?php echo $_SESSION['monster']->getHp();?></p>
      <p>倒したモンスター数:<?php echo  $_SESSION['knockDownCount'];?></p>
      <p>勇者の残りHP:<?php echo $_SESSION['human']->getHp();?></p>
      <p>回復数:<?php echo $_SESSION['recoverCount'];?></p>
      <form method="post">
        <input type="submit" name="attack" value="▶︎攻撃する">
        <input type="submit" name="recover" value="▶︎回復する">
        <input type="submit" name="escape" value="▶︎逃げる">
        <input type="submit" name="start" value="▶︎ゲームリスタート">
      </form>
    <?php }?>
    <div style="position:absolute; right:-300px; top:0; color:black; width: 250px;">
      <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
    </div>
  </div>
</body>
</html>
