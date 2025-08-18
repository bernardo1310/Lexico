<?php

class Parser {
    private array $tokens;
    private int $index;

    /**
     * Construtor para inicializar os tokens e o índice.
     */
    public function __construct(array $tokens) {
        echo "Inicializando o parser com os tokens...\n";
        $this->tokens = $tokens;
        $this->index = 0;
    }

    /**
     * Valida se o token atual é o esperado e avança o índice.
     */
    private function term(string $expectedToken): bool {
        if (!isset($this->tokens[$this->index])) {
            echo "Erro: Fim dos tokens alcançado antes de encontrar o token esperado: {$expectedToken}\n";
            return false;
        }

        if ($this->tokens[$this->index] === $expectedToken) {
            echo "Token válido: {$this->tokens[$this->index]}\n";
            $this->index++;
            return true;
        }

        echo "Erro: Token inválido. Esperado: {$expectedToken}, encontrado: {$this->tokens[$this->index]}\n";
        return false;
    }

    /**
     * EXP2 -> const
     */
    public function exp2(): bool {
        echo "Analisando EXP2...\n";
        return $this->term('const');
    }

    /**
     * DEC -> tipo id pv
     */
    public function dec(): bool {
        echo "Analisando DEC...\n";
        return $this->tipo() && $this->term('id') && $this->term('pv');
    }

    /**
     * TIPO -> tipo
     */
    public function tipo(): bool {
        echo "Analisando TIPO...\n";
        return $this->term('tipo');
    }

    /**
     * Método para reiniciar o índice (útil para novos testes).
     */
    public function reset(): void {
        echo "Reiniciando o parser...\n";
        $this->index = 0;
    }
}

// -------------------------------------------
// Testando a gramática

// Exemplos de listas de tokens
$tokens1 = ['id', 'atr', 'id', 'pv'];       // válido ATR
$tokens2 = ['tipo', 'id', 'pv'];            // válido DEC
$tokens3 = ['id', 'atr', 'const', 'pv'];    // válido ATR
$tokens4 = ['tipo', 'id'];                  // inválido (faltando pv)

// Criando o parser
$parser = new Parser($tokens2);

// Testando DEC
if ($parser->dec()) {
    echo "Tokens válidos para DEC.\n";
} else {
    echo "Tokens inválidos para DEC.\n";
}