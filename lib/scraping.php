<?php
//スクレイピングクラスと正規表現による抜き出し、置き換えクラスのセット
//

//全体取得
class siteGet{
	public	$EC;
	function	go(){
		$EC=$this->EC;
		$scraping=new scraping();
		$documentExtraction=new DocumentExtraction();
		$result=array();

		$currentPage=$EC["startPageNo"];
		$nextPage=$EC["nextPageNo"];
		//$modification=new modification();	//今の所意味なし



		//一覧ページ(アウトラインもしくはディティールでfalseが出たらページ終了と判断して終わり)
		do{
			$scraping->url=str_replace("|",$currentPage,$EC["url"]);
			print $scraping->url."\r\n";

			$documentExtraction->subject=$scraping->go();
			$documentExtraction->outlinePattern=$EC["outlinePattern"];
			$documentExtraction->detailsPattern=$EC["detailsPattern"];
			$documentExtraction->changeIndexSarch=$EC["changeIndexSarch"];
			$documentExtraction->changeIndexName=$EC["changeIndexName"];
			$documentExtraction->replacePattern=$EC["replacePattern"];
			$documentExtraction->replacement=$EC["replacement"];
			$subject=$documentExtraction->pageGet();
			$pageResult=$subject;

			$currentPage=$currentPage+$nextPage;
			if(	$pageResult!==false	){
				$result=array_merge($result,$pageResult);
			}
		}while(	$subject!==false	);

		return	$result;
	}
}



//スクレイピングクラス
class scraping{

	//最終的にアクセスしたいページ	
	public	$url;	
	//クッキー取得のためのURL
	//ここにアクセスすればクッキーにフラグが立つというページ
	public	$cookie;

	//スクレイピング実行
	function	go(){



		if($this->url==null){
			print "error! no address!\r\n";
			return false;
		}else{
			$url=$this->url;
		}
		if($this->cookie==null){
			$cookie=$this->url;
		}else{
			$cookie=$this->cookie;
		}


		//クッキー取得のためのアクセス
		$ch=curl_init();//初期化
		curl_setopt($ch,CURLOPT_URL,$cookie);//cookieを取りに行く
		curl_setopt($ch,CURLOPT_HEADER,FALSE);//httpヘッダ情報は表示しない
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);//データをそのまま出力
		curl_setopt($ch,CURLOPT_COOKIEJAR,'cookie.txt');//$cookieから取得した情報を保存するファイル名
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);//Locationヘッダの内容をたどっていく
		curl_exec($ch);
		curl_close($ch);//いったん終了

		//見たいページにアクセス
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		curl_setopt($ch,CURLOPT_COOKIEFILE, 'cookie.txt');//cookie情報を読み取る
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
		$html=curl_exec($ch);
		curl_close($ch);

		mb_language('Japanese');
		$html=mb_convert_encoding($html,'utf8','auto');//UTF-8に変換

		$html=file_get_contents($url);
		//file_put_contents("all.html",var_export($html,true)	);

		return $html;
		
	}
}


//抽出用クラス(DocumentExtraction)
//正規表現で大枠、各項目みたいな感じで2回に分けて必要な情報を抽出し配列で返す
//指定するもの(使いまわし用)
//
//	大枠の前と後ろ
//	パターン例
//	$front="rwd-news-area-list-item"
//	$rear="<\/a>[\n]?<\/div>"
//	"/".$front-".[\s\S]*?".$rear."/iu"
//
//	各項目の前と後ろ
//
//大枠outline
//詳細details

class	DocumentExtraction{
	public	$subject;
	public	$outlinePattern;
	public	$detailsPattern;
	public	$replacePattern;
	public	$replacement;
	private	$changIndexName;
	private	$changIndexResult;
	private	$pattern;
	private	$detailsResult;
	private	$outlineResult;
	private	$replaceResult;
	function	pageGet(){
		if($this->outlinePattern==null){
			print "no outlinePattern!\r\n";
			return false;
		}
		if($this->detailsPattern==null){
			print "no detailsPattern!\r\n";
			return false;
		}
		if($this->subject==null){
			print "no subject!\r\n";
			return false;
		}
		if($this->changeIndexSarch==null){
			print "no IndexSarch!\r\n";
			return false;
		}
		if($this->changeIndexName==null){
			print "no IndexName!\r\n";
			return false;
		}
		if($this->replacePattern==null){
			print "no replacePattern!\r\n";
			return false;
		}
		if($this->replacement==null){
			print "no replacement!\r\n";
			return false;
		}

		//アウトライン
		$this->outlineResult=$this->getOutline();

		//アウトラインが取れなかったらfalseを返す
		if(	empty($this->outlineResult)	){
			return false;
		}

		//ディティール
		$this->detailsResult=$this->getDetails();
		
		//ディティールが取れなかったらfalseを返す(同上)
		if(	empty($this->detailsResult)	){
			return false;
		}

		//インデックス取得
		$this->changeIndexResult=$this->changeIndex();

		//インデックス置き換え
		$this->replaceResult=$this->getReplace();

		//$result=array_combine($this->changeIndexResult,$this->replaceResult);

		$result=$this->combine();

		return $result;
	}






	private function	getOutline(){
		$this->pattern=$this->outlinePattern;
		$result=$this->match();

		//file_put_contents("outLine.txt",var_export($result,true)	);
		return $result;
		
	}
	private function	getDetails(){
		$this->pattern=$this->detailsPattern;
		$this->subject=$this->outlineResult;

		$result=$this->matchAll();

		//file_put_contents("details.txt",var_export($result,true)	);
		return $result;
	}
	private	function	changeIndex(){
		$pattern=$this->changeIndexSarch;
		$replacement=$this->changeIndexName;
		$subject=$this->detailsResult;
		$result=preg_replace($pattern,$replacement,$subject);

		//file_put_contents("indexList.txt",var_export($result,true)	);

		return $result;
	}
	private function	getReplace(){
		$pattern=$this->replacePattern;
		$replacement=$this->replacement;
		$subject=$this->detailsResult;
		$result=preg_replace($pattern,$replacement,$subject);
		//file_put_contents("replace.txt",var_export($result,true)	);
		return $result;
	}
	private	function	combine(){
		$result=array();
		$index=$this->changeIndexResult;
		$values=$this->replaceResult;
		$array=array();
		$pointa=0;
		$flag=$index[0];

		//indexListとreplaceの結合
		//ここのループは再考したい(一応まわってるけど)
		foreach($index as $i => $key){

			if(	$flag==$key	&&	$i!=0	){
				//print_r($array);
				$result[$pointa]=$array;
				$array=array();
				++$pointa;
			}

			$array=$array+array(	$key=>$values[$i]	);

		}
		$result[$pointa]=$array;		//なんかここが邪魔くさい感じ


		//file_put_contents("result.txt",var_export($result,true)	);
	return $result;
	}
	private	function	match(){
		$subject=$this->subject;
		$pattern=$this->pattern;
 
		preg_match($pattern,$subject,$result);

		if(	!isset(	$result[0]	)	){
			print "error!!! not result[0]!!";
			print_r($result);
		}

		return $result[0];

	}
	private	function	matchAll(){
		$subject=$this->subject;
		$pattern=$this->pattern;


		//$result=preg_match_all($pattern,$subject,$match,PREG_UNMATCHED_AS_NULL);
		$result=preg_match_all($pattern,$subject,$match);
		if($result===false){
			print "match_all:error\r\n";
		}elseif($result===0){
			print "match_all:0\r\n";
		}

		return $match[0];

	}
}



?>
