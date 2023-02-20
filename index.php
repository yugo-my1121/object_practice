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
  //人クラス
  class Human{
    protected $name;
    protected $sex;
    protected $hp;
    protected $attackMin;
    protected $attackMax;

    public function __construct($name,$sex,$hp,$attackMin,$attackMax){
      $this->name = $name;
      $this->sex = $sex;
      $this->hp = $hp;
      $this->attackMin = $attackMin;
      $this->attackMax = $attackMax;
    }
    public function setName($str){
      $this->name = $str;
    }
    public function getName(){
      return $this->name;
    }
    public function setSex($num){
      $this->sex = $num;
    }
    public function getSex(){
      return $this->sex;
    }
    public function setHp($num){
      $this->hp = $num;
    }
    public function getHp(){
      return $this->hp;
    }
    public function sayCry(){
      switch($this->sex){
        case Sex::MAN :
          History::set('ぐはっ');
          break;
        case Sex::WOMAN :
          History::set('きゃっ!');
          break;
        case Sex::MAN :
          History::set('もっとちょうだい!');
          break;
      }
    }
    public function attack(){
      $attackPoint = mt_rand($this->attackMin,$this->attackMax);
      if(!mt_rand(0,9)){//10分の1の確率
        $attackPoint = $attackPoint*1.5;
        $attackPoint = (int)$attackPoint;
        History::set($this->getName().'のクリティカルヒット!!');
      }
      $_SESSION['monster']->setHp($_SESSION['monster']->getHp()-$attackPoint);
      History::set($attackPoint.'ポイントのダメージを与えた！');
    }

  }

  //モンスタークラス
    class Monster{
    //プロパティ
    protected $name;//定義しただけだとnullが入る
    protected $hp;
    protected $img;
    protected $attack; //nullを入れたくない場合、空文字で初期化する

    //コンストラクト
    public function __construct($name,$hp,$img,$attack){
      $this -> name = $name;
      $this -> hp = $hp;
      $this -> img = $img;
      $this -> attack = $attack;
    }
    //メソッド
    public function attack(){
      $attackPoint = $this->attack;
      if(!mt_rand(0,9)){
        $attackPoint *= 1.5;
        $attackPoint = (int)$attackPoint;
        History::set($this->getName().'のクリティカルヒット!!');
      }
      $_SESSION['human']->setHp( $_SESSION['human']->getHp() - $attackPoint );
      History::set($this->attack.'ダメージを受けた!');
    }
    //セッター
    public function setHp($num){
      // セッターを使うことで、直接代入させずにバリデーションチェックを行ってから代入させることができる
      //filter_varは値に対して色々なパターンのバリデーションを行える便利関数
      $this -> hp = filter_var($num,FILTER_VALIDATE_INT);
    }
    public function setAttack($num){
      // $numには小数点が入る可能性がある。filter_var関数はバリデーションにひっかかるとfalseが返ってきて代入されてしまうので、float型かどうかのバリデーションにして、int型へキャスト
      // もしくは、FILTER_VALIDATE_FLOATを使う
      $this -> attack = (int)filter_var($num,FILTER_VALIDATE_FLOAT);

    }

    //ゲッター
    public function getName(){
      return $this -> name;
    }
    public function getHp(){
      return $this -> hp;
    }
    public function getImg(){
      // あとあとでimgが入ってなかったら、no-img画像を出そう！となった時でも、クラスを書き換えるだけ！
      // もし、ゲッターメソッドを使っていなければ、取得するコードの箇所全部を修正しなければいけない！
      // カプセル化をすることで、呼び出す側は「中で何をしているのか」を気にせずにただ呼び出せばいいだけになる（疎結合）
      if(empty($this->img)){
        return 'img/no-img.png';
      }
      return $this -> img;
    }
    public function getAttack(){
      return $this -> attack;
    }
  }

  //魔法を使えるモンスタークラス
  class MagicMonster extends Monster{
    private $magicAttack;
    function __construct($name,$hp,$img,$attack,$magicAttack){
      //親クラスのコンストラクタで処理する内容を継承したい場合には親コンストラクタを呼び出す
      parent::__construct($name,$hp,$img,$attack);
      $this -> magicAttack = $magicAttack;
    }
    public function getMagicAttack(){
      return $this -> magicAttack;
    }
    // attackメソッドをオーバーライドすることで、「ゲーム進行を管理する処理側」は単にattackメソッドを呼べばいいだけになる
    // 魔法を使えるモンスターは、自分で魔法を出すか普通に攻撃するかを判断する
    public function attack(){
      $attackPoint = $this->attack;
      if(!mt_rand(0,4)){//5分の1の確率で魔法攻撃
        History::set($this->name.'の魔法攻撃!!');
        $_SESSION['human']->setHp( $_SESSION['human']->getHp() - $this->magicAttack );
        History::set($this->magicAttack.'ポイントのダメージを受けた!');
      }else{
        // 通常の攻撃の場合は、親クラスの攻撃メソッドを使うことで、親クラスの攻撃メソッドが修正されてもMagicMonsterでも反映される
        parent::attack();
      }
    }
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
  $human = new Human('勇者見習い', Sex::MAN, 500, 40, 120);
  $monsters[] = new Monster('フランケン',100,'img/monster01.png',mt_rand(20,40));
  $monsters[] = new MagicMonster('フランケンNEO',300,'img/monster02.png',mt_rand(20,60),mt_rand(50,100));
  $monsters[] = new Monster('ドラキュラー',200,'img/monster03.png',mt_rand(30,50));
  $monsters[] = new MagicMonster('ドラキュラー男爵',400,'img/monster04.png',mt_rand(50,100),mt_rand(60,120));
  $monsters[] = new Monster('スカルフェイス',150,'img/monster05.png',mt_rand(30,60));
  $monsters[] = new Monster('毒ハンド',100,'img/monster06.png',mt_rand(10,30));
  $monsters[] = new Monster('泥ハンド',120,'img/monster07.png',mt_rand(20,30));
  $monsters[] = new Monster('血のハンド',180,'img/monster08.png',mt_rand(30,50));

  function createMonster(){
    global $monsters;
    $monster = $monsters[mt_rand(0,7)];
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
    error_log('POSTされた!');

    if($startFlg){
      History::set('ゲームスタート!');
      init();
    }else{
      if($attackFlg){
        //モンスターに攻撃を与える
        History::set('攻撃した!');
        $_SESSION['human']->attack();

        //モンスターから攻撃を受ける
        $_SESSION['monster'] ->attack();

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
      <form method="post">
        <input type="submit" name="attack" value="▶︎攻撃する">
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
