<?php
/**
 * ARQUIVO DE TESTES PARA O ANALISADOR SINTÁTICO
 * Demonstra diferentes casos de uso da gramática implementada
 */

include_once 'lexico.php';
include_once 'descendenteRecursivo.php';

class TesteSintatico {
    private $lexico;
    private $sintatico;
    private $testesPassaram = 0;
    private $totalTestes = 0;

    public function __construct() {
        $this->lexico = new Lexico();
    }

    /**
     * Executa um teste individual
     */
    private function executarTeste($nome, $codigo, $esperado = true) {
        $this->totalTestes++;
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TESTE: $nome\n";
        echo "CÓDIGO: $codigo\n";
        echo str_repeat("-", 50) . "\n";

        try {
            $this->lexico->scan($codigo);
            $this->sintatico = new DescendenteRecursivo($this->lexico, true); // Debug habilitado
            
            $resultado = $this->sintatico->analisar();
            $status = $this->sintatico->getStatus();
            
            echo "\nRESULTADO: " . ($resultado ? "ACEITO" : "REJEITADO") . "\n";
            echo "STATUS: Processados {$status['tokens_processados']}/{$status['total_tokens']} tokens\n";
            
            if ($resultado == $esperado) {
                echo "✓ TESTE PASSOU\n";
                $this->testesPassaram++;
            } else {
                echo "✗ TESTE FALHOU - Esperado: " . ($esperado ? "ACEITO" : "REJEITADO") . "\n";
            }
            
            // Mostra tokens restantes se houver
            $restantes = $this->sintatico->getRemainingTokens();
            if (!empty($restantes)) {
                echo "TOKENS NÃO CONSUMIDOS: ";
                foreach ($restantes as $token) {
                    echo $token->getToken() . "(" . $token->getLexema() . ") ";
                }
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "ERRO: " . $e->getMessage() . "\n";
            echo "✗ TESTE FALHOU\n";
        }
    }

    /**
     * Executa todos os testes
     */
    public function executarTodosTestes() {
        echo "INICIANDO BATERIA DE TESTES DO ANALISADOR SINTÁTICO\n";
        
        // TESTE 1: Declaração simples de variável
        $this->executarTeste(
            "Declaração de Variável",
            "var x;",
            true
        );

        // TESTE 2: Atribuição simples
        $this->executarTeste(
            "Atribuição Simples",
            "x = 10;",
            true
        );

        // TESTE 3: Expressão aritmética simples
        $this->executarTeste(
            "Expressão Aritmética",
            "x = 10 + 5;",
            true
        );

        // TESTE 4: Expressão aritmética complexa
        $this->executarTeste(
            "Expressão Complexa",
            "x = 10 + 5 * 2 - 3;",
            true
        );

        // TESTE 5: Expressão com parênteses
        $this->executarTeste(
            "Expressão com Parênteses",
            "x = (10 + 5) * 2;",
            true
        );

        // TESTE 6: Comando de escrita
        $this->executarTeste(
            "Comando Print",
            "print(x);",
            true
        );

        // TESTE 7: Comando de leitura
        $this->executarTeste(
            "Comando Read",
            "read(y);",
            true
        );

        // TESTE 8: Comando IF simples
        $this->executarTeste(
            "Comando IF Simples",
            "if (x > 5) { y = 10; }",
            true
        );

        // TESTE 9: Comando IF com ELSE
        $this->executarTeste(
            "Comando IF com ELSE",
            "if (x > 5) { y = 10; } else { y = 0; }",
            true
        );

        // TESTE 10: Programa com múltiplos comandos
        $this->executarTeste(
            "Programa Múltiplos Comandos",
            "var x; x = 10; print(x);",
            true
        );

        // TESTE 11: Bloco com múltiplos comandos
        $this->executarTeste(
            "Bloco Múltiplos Comandos",
            "if (x > 0) { var y; y = x + 1; print(y); }",
            true
        );

        // TESTE 12: Expressão com múltiplas operações
        $this->executarTeste(
            "Expressão Múltiplas Operações",
            "result = a + b * c - d / e;",
            true
        );

        // TESTE 13: Comparações diferentes
        $this->executarTeste(
            "Diferentes Comparações",
            "if (a < b) { x = 1; }",
            true
        );

        // TESTE 14: Teste de erro - token inválido
        $this->executarTeste(
            "Erro Sintático",
            "var x y;", // Faltando operador entre x e y
            false
        );

        // TESTE 15: Teste de erro - parênteses não fechados
        $this->executarTeste(
            "Erro Parênteses",
            "if (x > 5 { y = 10; }",
            false
        );

        // RESUMO DOS TESTES
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "RESUMO DOS TESTES\n";
        echo str_repeat("=", 60) . "\n";
        echo "Testes executados: {$this->totalTestes}\n";
        echo "Testes que passaram: {$this->testesPassaram}\n";
        echo "Taxa de sucesso: " . number_format(($this->testesPassaram / $this->totalTestes) * 100, 1) . "%\n";
        
        if ($this->testesPassaram == $this->totalTestes) {
            echo "🎉 TODOS OS TESTES PASSARAM!\n";
        } else {
            echo "⚠️  Alguns testes falharam. Verifique a implementação.\n";
        }
    }

    /**
     * Executa teste interativo onde o usuário pode inserir código
     */
    public function testeInterativo() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "MODO TESTE INTERATIVO\n";
        echo "Digite 'sair' para encerrar\n";
        echo str_repeat("=", 50) . "\n";

        while (true) {
            echo "\nDigite o código para análise: ";
            $codigo = trim(fgets(STDIN));
            
            if (strtolower($codigo) === 'sair') {
                break;
            }
            
            if (empty($codigo)) {
                continue;
            }

            try {
                $this->lexico->scan($codigo);
                $this->sintatico = new DescendenteRecursivo($this->lexico, true);
                
                $resultado = $this->sintatico->analisar();
                
                echo "\nRESULTADO: " . ($resultado ? "✓ CÓDIGO ACEITO" : "✗ CÓDIGO REJEITADO") . "\n";
                
                $status = $this->sintatico->getStatus();
                echo "Tokens processados: {$status['tokens_processados']}/{$status['total_tokens']}\n";
                
            } catch (Exception $e) {
                echo "ERRO: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Execução dos testes
if (php_sapi_name() === 'cli') {
    $teste = new TesteSintatico();
    
    echo "Escolha uma opção:\n";
    echo "1 - Executar todos os testes automatizados\n";
    echo "2 - Modo teste interativo\n";
    echo "3 - Ambos\n";
    echo "Digite sua escolha (1-3): ";
    
    $escolha = trim(fgets(STDIN));
    
    switch ($escolha) {
        case '1':
            $teste->executarTodosTestes();
            break;
        case '2':
            $teste->testeInterativo();
            break;
        case '3':
            $teste->executarTodosTestes();
            $teste->testeInterativo();
            break;
        default:
            echo "Opção inválida. Executando todos os testes.\n";
            $teste->executarTodosTestes();
    }
} else {
    // Se executado via web, executa apenas os testes automáticos
    $teste = new TesteSintatico();
    $teste->executarTodosTestes();
}
?>