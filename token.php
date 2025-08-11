<?php

class Token{

    private $token, $lexema, $pos;

    public function __construct($tk,$lex, $pos){
        $this->token = $tk;
        $this->lexema = $lex;
        $this->pos = $pos;
    }

    public function __tostring(): String{
        return "<".$this->token.",".$this->lexema.">";
    }
}   