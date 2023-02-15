<?php
  ini_set('log_errors','on');//ログを取るか
  ini_set('error_log','php.log');//ログの出力場所指定
  session_start();//セッションを使う

  //自分のHP
  define("MY_HP",500);
  //モンスター格納用
  $monsters = array();

  //クラス(設計図)の作成
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
        $_SESSION['history'] .= $this->getName().'のクリティカルヒット!!<br>';
      }
      $_SESSION['myhp'] -= $this->attack;
      $_SESSION['history'] .= $this->attack.'ダメージを受けた!<br>';
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
    // 魔法攻撃力が増えることはない前提として、セッターは作らない（読み取り専用）
    public function magicAttack(){
      $_SESSION['history'] .= $this->name.'の魔法攻撃!!<br>';
      $_SESSION['myhp'] -= $this->magicAttack;
      $_SESSION['history'] .= $this->magicAttack.'ポイントのダメージを受けた!<br>';
    }
  }

  //インスタンス作成
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
    $_SESSION['history'] .= $monster->getName().'が現れた!<br>';
    $_SESSION['monster'] = $monster;
  }

  function init(){
    $_SESSION['history'] .= '初期化します!<br>';
    $_SESSION['knockDownCount'] = 0;
    $_SESSION['myhp'] = MY_HP;
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
      $_SESSION['history'] = 'ゲーム開始!';
      init();
    }else{
      if($attackFlg){
        $_SESSION['history'] .= '攻撃した!<br>';

        //ランダムでモンスターに攻撃を与える
        $attackPoint = mt_rand(50,100);
        $_SESSION['monster']->setHp($_SESSION['monster']->getHp() - $attackPoint);
        $_SESSION['history'] .= $attackPoint.'ポイントのダメージを与えた!<br>';

        //モンスターから攻撃を受ける
        $_SESSION['monster'] ->attack();
        //魔法攻撃の行えるモンスターなら
        if($_SESSION['monster'] instanceof MagicMonster){
          if(!mt_rand(0,4)){//5分の1の確率で
            $_SESSION['monster'] -> magicAttack();
          }else{
            $_SESSION['monster']->attack();
          }

        }else{//普通のモンスターならただ攻撃するだけ
          $_SESSION['monster']->attack();
        }

        //hpが0以下になったらゲームオーバー
        if($_SESSION['myhp'] <=0){
          gameOver();
        }else{
          if($_SESSION['monster']->getHp() <=0){
            $_SESSION['history'] .= $_SESSION['monster']->getName().'を倒した!<br>';
            createMonster();
            $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
          }
        }



      }else{//逃げるを押した場合
        $_SESSION['history'] .= '逃げた!<br>';
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
      <p>勇者の残りHP:<?php echo $_SESSION['myhp'];?></p>
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
