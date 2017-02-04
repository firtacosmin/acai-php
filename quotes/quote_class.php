<?php


class Quote{
    
    public  $_quote;
    public  $_author;
    public  $_year;
    
    function __construct($quote, $author, $year){
        
        $this -> _quote = $quote;
        $this -> _author = $author;
        $this -> _year = $year;
        
    }

    public function getQuote(){
        return $this -> _quote;
    }
    public function getAuthor(){
        return $this -> _author;
    }
    public function getYear(){
        return $this -> _year;
    }
    
    
    public function toJSON(){
//        $ret = {"quote":$this->_quote, "author":$this -> _author, "year":$this -> _year};
        $ret -> quote = $this -> _quote;
        $ret -> author = $this -> _author;
        $ret -> year = $this -> _year;
        $retJson = json_encode($ret);
    }
    
    
    
    
}

?>

