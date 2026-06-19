<?php

function image($url){
	$url2='https://guiaunai.com.br/wp-content/uploads/2026/02/news_unai.jpg';
    $url_components = parse_url($url);
    parse_str($url_components['query'], $params);
    libxml_use_internal_errors(true);
	if(@file_get_contents($params['url'])){
		$c = file_get_contents($params['url']);
		$d = new DomDocument();
		$d->loadHTML($c);
		$xp = new domxpath($d);
		//print_r($xp->query("//meta[@property='og:image']")->length);
		if($xp->query("//meta[@property='og:image']")->length!=0){
			foreach ($xp->query("//meta[@property='og:image']") as $el) {
				if(!empty($el) and preg_match( '/^(http|https):\\/\\/[a-z0-9]+([\\-\\.]{1}[a-z0-9]+)*\\.[a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i' ,$el->getAttribute("content"))){
					return $el->getAttribute("content");
				}else{
					return $url2;
				}
			}
		}else{
			return $url2;
		}
	}else {
		return $url2;
	}
}

function getImages($get_string){
    $string = $get_string;
    preg_match_all('/"([^"]+)"/', $string, $matches);
    foreach($matches[1] as $m) {
        if(preg_match('/http/',$m)){
            echo $m .= ',';
        }
    }
}

function getInsta($get_string){
    $string = $get_string;
    preg_match_all('/"([^"]+)"/', $string, $matches);
    foreach($matches[1] as $m) {
        if(preg_match('/http/',$m) and preg_match('/instagram/',$m) ){
            echo $m .= '';
        }
    }
}

/*function plans($get_string){
    if(preg_match('/https:/',$get_string)){
    	echo '03';
    }else{
        echo '10';
    }
}*/

function plans($cover,$gallery,$logo){
    if(preg_match('/https:/',$cover)){
    	echo '01';
    }else{
        if(preg_match('/https:/',$gallery)){
    	    echo '02';
        }else{
            if(preg_match('/https:/',$logo)){
    	        echo '03';
            }else{
    	     echo '10';
            }
        }
    }   
}

function strip($text){
	return strip_tags($text);
}

?>
