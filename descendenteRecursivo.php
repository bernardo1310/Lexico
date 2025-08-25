<?php
/***
 * ==============================
 *   GRAMÁTICA DO COMPILADOR
 * ==============================
 *
 * PROGRAMA   -> COMANDO PROGRAMA | ε
 *
 * ---------------------------------
 * DECLARAÇÃO DE FUNÇÃO
 * ---------------------------------
 * FUNCAO     -> tipo id ( PARAMS ) BLOCO
 * PARAMS     -> tipo id PARAMS' | ε
 * PARAMS'    -> , tipo id PARAMS' | ε
 *
 * ---------------------------------
 * DECLARAÇÃO DE VARIÁVEL
 * ---------------------------------
 * DEC        -> tipo id ;
 *
 * ---------------------------------
 * ATRIBUIÇÃO DE VALORES
 * ---------------------------------
 * ATR        -> id = EXPRESSAO ;
 *
 * ---------------------------------
 * EXPRESSÕES NUMÉRICAS
 * ---------------------------------
 * EXPRESSAO  -> EXPRESSAO + TERMO
 *             | EXPRESSAO - TERMO
 *             | TERMO
 *
 * TERMO      -> TERMO * FATOR
 *             | TERMO / FATOR
 *             | FATOR
 *
 * FATOR      -> id | const | ( EXPRESSAO )
 *
 * ---------------------------------
 * EXPRESSÕES LÓGICAS (COMPARAÇÃO)
 * ---------------------------------
 * EXP_LOG    -> EXPRESSAO OP_REL EXPRESSAO
 * OP_REL     -> > | < | >= | <= | == | !=
 *
 * ---------------------------------
 * COMANDO DE ESCRITA
 * ---------------------------------
 * ESCRITA    -> print ( EXPRESSAO ) ;
 *
 * ---------------------------------
 * COMANDO DE LEITURA
 * ---------------------------------
 * LEITURA    -> read ( id ) ;
 *
 * ---------------------------------
 * COMANDO DE SELEÇÃO (IF)
 * ---------------------------------
 * SELECAO    -> if ( EXP_LOG ) BLOCO
 *             | if ( EXP_LOG ) BLOCO else BLOCO
 *
 * ---------------------------------
 * BLOCO DE COMANDOS
 * ---------------------------------
 * BLOCO      -> { COMANDOS }
 * COMANDOS   -> COMANDO COMANDOS | ε
 * COMANDO    -> DEC | ATR | ESCRITA | LEITURA | SELECAO | FUNCAO
 *
 */

include_once "lexico.php";

/**
 * Classe principal do Analisador Sintático Descendente Recursivo
 * Implementa análise sintática completa seguindo a gramática LL especificada
 */
class DescendenteRecursivo {
    private $cont = 0;
    private $lexico;
    private $debug = false;
    private $indentLevel = 0;
    
    public function __construct(Lexico $lexico, bool $debug = false) {
        $this->lexico = $lexico;
        $this->debug = $debug;
    }

    /**
     * Habilita/desabilita modo debug
     */
    public function setDebug(bool $debug): void {
        $this->debug = $debug;
    }

    /**
     * Log de debug com indentação
     */
    private function debugLog(string $message): void {
        if ($this->debug) {
            echo str_repeat("  ", $this->indentLevel) . $message . "\n";
        }
    }

    /**
     * Incrementa indentação para debug
     */
    private function enterRule(string $rule): void {
        $this->debugLog("Entrando em: " . $rule);
        $this->indentLevel++;
    }

    /**
     * Decrementa indentação para debug
     */
    private function exitRule(string $rule, bool $success): void {
        $this->indentLevel--;
        $this->debugLog("Saindo de: " . $rule . " -> " . ($success ? "SUCESSO" : "FALHA"));
    }

    /**
     * Função para validar terminal esperado com a lista de tokens
     */
    private function term(string $token): bool {
        if ($this->cont >= count($this->lexico->getLista_tokens())) {
            return false;
        }
        
        $atual = $this->lexico->getLista_tokens()[$this->cont];
        $ret = $atual->getToken() == $token;
        
        if ($ret) {
            $this->debugLog("Consumiu token: " . $atual->getToken() . " (" . $atual->getLexema() . ")");
            $this->cont++;
        }
        
        return $ret;
    }

    /**
     * Método para salvar posição atual (backtracking)
     */
    private function savePosition(): int {
        return $this->cont;
    }

    /**
     * Método para restaurar posição (backtracking)
     */
    private function restorePosition(int $position): void {
        $this->cont = $position;
    }

    /**
     * PROGRAMA -> COMANDO PROGRAMA | ε
     */
    public function programa(): bool {
        $this->enterRule("PROGRAMA");
        $result = $this->programaImpl();
        $this->exitRule("PROGRAMA", $result);
        return $result;
    }

    private function programaImpl(): bool {
        // Se não há mais tokens, aceita (ε)
        if ($this->cont >= count($this->lexico->getLista_tokens())) {
            return true;
        }

        $pos = $this->savePosition();
        
        // Tenta COMANDO PROGRAMA
        if ($this->comando() && $this->programa()) {
            return true;
        }
        
        // Se falhou, restaura e aceita ε
        $this->restorePosition($pos);
        return true; // ε sempre aceita
    }

    /**
     * COMANDO -> DEC | ATR | ESCRITA | LEITURA | SELECAO | FUNCAO
     */
    public function comando(): bool {
        $this->enterRule("COMANDO");
        $result = $this->comandoImpl();
        $this->exitRule("COMANDO", $result);
        return $result;
    }

    private function comandoImpl(): bool {
        $pos = $this->savePosition();

        // Tenta cada alternativa
        $alternativas = [
            'dec', 'atr', 'escrita', 'leitura', 'selecao', 'funcao'
        ];

        foreach ($alternativas as $metodo) {
            $this->restorePosition($pos);
            if ($this->$metodo()) {
                return true;
            }
        }

        return false;
    }

    /**
     * FUNCAO -> tipo id ( PARAMS ) BLOCO
     */
    public function funcao(): bool {
        $this->enterRule("FUNCAO");
        
        $result = $this->tipo() && 
                 $this->term("ID") && 
                 $this->term("AP") && 
                 $this->params() && 
                 $this->term("FP") && 
                 $this->bloco();
        
        $this->exitRule("FUNCAO", $result);
        return $result;
    }

    /**
     * PARAMS -> tipo id PARAMS' | ε
     */
    private function params(): bool {
        $this->enterRule("PARAMS");
        
        $pos = $this->savePosition();
        
        // Tenta tipo id PARAMS'
        if ($this->tipo() && $this->term("ID") && $this->paramsLinha()) {
            $this->exitRule("PARAMS", true);
            return true;
        }
        
        // Se falhou, restaura e aceita ε
        $this->restorePosition($pos);
        $this->exitRule("PARAMS", true);
        return true; // ε
    }

    /**
     * PARAMS' -> , tipo id PARAMS' | ε
     */
    private function paramsLinha(): bool {
        $this->enterRule("PARAMS'");
        
        $pos = $this->savePosition();
        
        // Tenta , tipo id PARAMS'
        if ($this->term("VIRGULA") && $this->tipo() && $this->term("ID") && $this->paramsLinha()) {
            $this->exitRule("PARAMS'", true);
            return true;
        }
        
        // Se falhou, restaura e aceita ε
        $this->restorePosition($pos);
        $this->exitRule("PARAMS'", true);
        return true; // ε
    }

    /**
     * DEC -> tipo id ;
     */
    public function dec(): bool {
        $this->enterRule("DEC");
        
        $result = $this->tipo() && 
                 $this->term("ID") && 
                 $this->term("PV");
        
        $this->exitRule("DEC", $result);
        return $result;
    }

    /**
     * TIPO -> int | float | string | bool
     */
    private function tipo(): bool {
        $this->enterRule("TIPO");
        
        $tipos = ["INT", "FLOAT", "STRING", "BOOL", "VAR"]; // Incluindo VAR do seu léxico
        
        foreach ($tipos as $tipo) {
            $pos = $this->savePosition();
            if ($this->term($tipo)) {
                $this->exitRule("TIPO", true);
                return true;
            }
            $this->restorePosition($pos);
        }
        
        $this->exitRule("TIPO", false);
        return false;
    }

    /**
     * ATR -> id = EXPRESSAO ;
     */
    public function atr(): bool {
        $this->enterRule("ATR");
        
        $result = $this->term("ID") && 
                 $this->term("ATRIBUICAO") && 
                 $this->expressao() && 
                 $this->term("PV");
        
        $this->exitRule("ATR", $result);
        return $result;
    }

    /**
     * EXPRESSAO -> EXPRESSAO + TERMO | EXPRESSAO - TERMO | TERMO
     * Transformada para eliminar recursão à esquerda:
     * EXPRESSAO -> TERMO EXPRESSAO'
     * EXPRESSAO' -> + TERMO EXPRESSAO' | - TERMO EXPRESSAO' | ε
     */
    public function expressao(): bool {
        $this->enterRule("EXPRESSAO");
        
        $result = $this->termo() && $this->expressaoLinha();
        
        $this->exitRule("EXPRESSAO", $result);
        return $result;
    }

    /**
     * EXPRESSAO' -> + TERMO EXPRESSAO' | - TERMO EXPRESSAO' | ε
     */
    private function expressaoLinha(): bool {
        $this->enterRule("EXPRESSAO'");
        
        $pos = $this->savePosition();
        
        // Tenta + TERMO EXPRESSAO'
        if ($this->term("SOMA") && $this->termo() && $this->expressaoLinha()) {
            $this->exitRule("EXPRESSAO'", true);
            return true;
        }
        
        $this->restorePosition($pos);
        
        // Tenta - TERMO EXPRESSAO'
        if ($this->term("SUBTRACAO") && $this->termo() && $this->expressaoLinha()) {
            $this->exitRule("EXPRESSAO'", true);
            return true;
        }
        
        // Se falhou, restaura e aceita ε
        $this->restorePosition($pos);
        $this->exitRule("EXPRESSAO'", true);
        return true; // ε
    }

    /**
     * TERMO -> TERMO * FATOR | TERMO / FATOR | FATOR
     * Transformada para eliminar recursão à esquerda:
     * TERMO -> FATOR TERMO'
     * TERMO' -> * FATOR TERMO' | / FATOR TERMO' | ε
     */
    private function termo(): bool {
        $this->enterRule("TERMO");
        
        $result = $this->fator() && $this->termoLinha();
        
        $this->exitRule("TERMO", $result);
        return $result;
    }

    /**
     * TERMO' -> * FATOR TERMO' | / FATOR TERMO' | ε
     */
    private function termoLinha(): bool {
        $this->enterRule("TERMO'");
        
        $pos = $this->savePosition();
        
        // Tenta * FATOR TERMO'
        if ($this->term("MULTIPLICACAO") && $this->fator() && $this->termoLinha()) {
            $this->exitRule("TERMO'", true);
            return true;
        }
        
        $this->restorePosition($pos);
        
        // Tenta / FATOR TERMO'
        if ($this->term("DIVISAO") && $this->fator() && $this->termoLinha()) {
            $this->exitRule("TERMO'", true);
            return true;
        }
        
        // Se falhou, restaura e aceita ε
        $this->restorePosition($pos);
        $this->exitRule("TERMO'", true);
        return true; // ε
    }

    /**
     * FATOR -> id | const | ( EXPRESSAO )
     */
    private function fator(): bool {
        $this->enterRule("FATOR");
        
        $pos = $this->savePosition();
        
        // Tenta ID
        if ($this->term("ID")) {
            $this->exitRule("FATOR", true);
            return true;
        }
        
        $this->restorePosition($pos);
        
        // Tenta CONST (inteiro ou flutuante)
        if ($this->term("CONST") || $this->term("FLUTUANTEL")) {
            $this->exitRule("FATOR", true);
            return true;
        }
        
        $this->restorePosition($pos);
        
        // Tenta ( EXPRESSAO )
        if ($this->term("AP") && $this->expressao() && $this->term("FP")) {
            $this->exitRule("FATOR", true);
            return true;
        }
        
        $this->restorePosition($pos);
        $this->exitRule("FATOR", false);
        return false;
    }

    /**
     * EXP_LOG -> EXPRESSAO OP_REL EXPRESSAO
     */
    private function expLog(): bool {
        $this->enterRule("EXP_LOG");
        
        $result = $this->expressao() && 
                 $this->opRel() && 
                 $this->expressao();
        
        $this->exitRule("EXP_LOG", $result);
        return $result;
    }

    /**
     * OP_REL -> > | < | >= | <= | == | !=
     */
    private function opRel(): bool {
        $this->enterRule("OP_REL");
        
        $operadores = ["MAIOR", "MENOR", "MAIORIGUAL", "MENORIGUAL", "IGUAL", "DIFERENTE"];
        
        foreach ($operadores as $op) {
            $pos = $this->savePosition();
            if ($this->term($op)) {
                $this->exitRule("OP_REL", true);
                return true;
            }
            $this->restorePosition($pos);
        }
        
        $this->exitRule("OP_REL", false);
        return false;
    }

    /**
     * ESCRITA -> print ( EXPRESSAO ) ;
     */
    public function escrita(): bool {
        $this->enterRule("ESCRITA");
        
        $result = $this->term("PRINT") && 
                 $this->term("AP") && 
                 $this->expressao() && 
                 $this->term("FP") && 
                 $this->term("PV");
        
        $this->exitRule("ESCRITA", $result);
        return $result;
    }

    /**
     * LEITURA -> read ( id ) ;
     */
    public function leitura(): bool {
        $this->enterRule("LEITURA");
        
        $result = $this->term("READ") && 
                 $this->term("AP") && 
                 $this->term("ID") && 
                 $this->term("FP") && 
                 $this->term("PV");
        
        $this->exitRule("LEITURA", $result);
        return $result;
    }

    /**
     * SELECAO -> if ( EXP_LOG ) BLOCO | if ( EXP_LOG ) BLOCO else BLOCO
     */
    public function selecao(): bool {
        $this->enterRule("SELECAO");
        
        $pos = $this->savePosition();
        
        // Tenta if ( EXP_LOG ) BLOCO else BLOCO
        if ($this->term("IF") && 
            $this->term("AP") && 
            $this->expLog() && 
            $this->term("FP") && 
            $this->bloco() && 
            $this->term("ELSE") && 
            $this->bloco()) {
            $this->exitRule("SELECAO", true);
            return true;
        }
        
        $this->restorePosition($pos);
        
        // Tenta if ( EXP_LOG ) BLOCO
        if ($this->term("IF") && 
            $this->term("AP") && 
            $this->expLog() && 
            $this->term("FP") && 
            $this->bloco()) {
            $this->exitRule("SELECAO", true);
            return true;
        }
        
        $this->exitRule("SELECAO", false);
        return false;
    }

    /**
     * BLOCO -> { COMANDOS }
     */
    private function bloco(): bool {
        $this->enterRule("BLOCO");
        
        $result = $this->term("INIBLOCO") && 
                 $this->comandos() && 
                 $this->term("FIMBLOCO");
        
        $this->exitRule("BLOCO", $result);
        return $result;
    }

    /**
     * COMANDOS -> COMANDO COMANDOS | ε
     */
    private function comandos(): bool {
        $this->enterRule("COMANDOS");
        
        $pos = $this->savePosition();
        
        // Tenta COMANDO COMANDOS
        if ($this->comando() && $this->comandos()) {
            $this->exitRule("COMANDOS", true);
            return true;
        }
        
        // Se falhou, restaura e aceita ε
        $this->restorePosition($pos);
        $this->exitRule("COMANDOS", true);
        return true; // ε
    }

    /**
     * Método público para análise completa
     */
    public function analisar(): bool {
        $this->cont = 0;
        $result = $this->programa();
        
        // Verifica se todos os tokens foram consumidos
        if ($result && $this->cont < count($this->lexico->getLista_tokens())) {
            $this->debugLog("Erro: Nem todos os tokens foram consumidos");
            return false;
        }
        
        return $result;
    }

    /**
     * Retorna informações sobre o estado atual da análise
     */
    public function getStatus(): array {
        $total = count($this->lexico->getLista_tokens());
        return [
            'posicao_atual' => $this->cont,
            'total_tokens' => $total,
            'tokens_processados' => $this->cont,
            'tokens_restantes' => $total - $this->cont,
            'analise_completa' => $this->cont >= $total
        ];
    }

    /**
     * Retorna o token atual sem consumi-lo
     */
    public function getCurrentToken(): ?Token {
        if ($this->cont < count($this->lexico->getLista_tokens())) {
            return $this->lexico->getLista_tokens()[$this->cont];
        }
        return null;
    }

    /**
     * Retorna todos os tokens a partir da posição atual
     */
    public function getRemainingTokens(): array {
        return array_slice($this->lexico->getLista_tokens(), $this->cont);
    }
}
?>